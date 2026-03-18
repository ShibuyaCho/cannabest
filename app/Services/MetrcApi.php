<?php
// File: app/Services/MetrcApi.php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use RuntimeException;

/**
 * Thin wrapper around MetrcClient with raw-HTTP fallbacks so we can
 * create receipts, unfinalize (activate), and delete (void) even if
 * the SDK is missing specific endpoints.
 */
class MetrcApi
{
    private string $licenseNumber;
    private int    $orgId;
    private string $userApiKey;
    private MetrcClient $client;

    /** Build from a sale id + optional license override */
    public static function fromSale(int $saleId, ?string $licenseOverride = null): self
    {
        // 1) read sale (only columns that exist)
        $saleCols = ['id','user_id','created_at'];
        if (Schema::hasColumn('sales','organization_id')) $saleCols[] = 'organization_id';
        if (Schema::hasColumn('sales','org_id'))          $saleCols[] = 'org_id';

        $sale = DB::table('sales')->select($saleCols)->where('id', $saleId)->first();
        if (!$sale) throw new RuntimeException("Sale {$saleId} not found.");

        // 2) resolve org id
        $orgId = $sale->organization_id ?? $sale->org_id ?? null;
        if (!$orgId) {
            $userCols = ['id'];
            if (Schema::hasColumn('users','organization_id')) $userCols[] = 'organization_id';
            if (Schema::hasColumn('users','org_id'))          $userCols[] = 'org_id';
            $user = DB::table('users')->select($userCols)->where('id', $sale->user_id)->first();
            if (!$user) throw new RuntimeException("Cashier user {$sale->user_id} not found.");
            $orgId = $user->organization_id ?? $user->org_id ?? null;
            if (!$orgId) throw new RuntimeException('Organization id could not be resolved from user.');
        }

        // 3) find an org admin (prefer role_id=2)
        $orgCol = Schema::hasColumn('users','organization_id') ? 'organization_id' : 'org_id';
        $admin  = DB::table('users')
            ->where($orgCol, $orgId)
            ->when(Schema::hasColumn('users','role_id'), fn($q)=>$q->where('role_id', 2))
            ->first()
            ?: DB::table('users')->where($orgCol, $orgId)->first();

        if (!$admin) throw new RuntimeException("No user found for org {$orgId}.");

        // 4) pull creds & license
        $license = self::firstNonEmpty($admin, ['metrc_license_number','license_number','metrc_license','license']);
        $userKey = self::firstNonEmpty($admin, ['metrc_api_key','api_key','apiKey']);

        if ($licenseOverride) $license = $licenseOverride;
        if (!$userKey) throw new RuntimeException('Missing METRC API credentials (user key).');
        if (!$license) throw new RuntimeException('Missing METRC license number.');

        $inst = new self();
        $inst->orgId         = (int)$orgId;
        $inst->licenseNumber = (string)$license;
        $inst->userApiKey    = (string)$userKey;
        $inst->client        = MetrcClient::make();   // ✅ uses factory

        return $inst;
    }

    public function getLicenseNumber(): string
    {
        return $this->licenseNumber;
    }

    /* ===================== Receipt create ===================== */
    /** POST a receipt to METRC. Return normalized array: id, receipt_number, external, sales_date_time, total */
    public function createReceipt(array $payload): array
    {
        // Prefer SDK client if available
        if (method_exists($this->client, 'createReceipts')) {
            $resp = $this->client->createReceipts($this->licenseNumber, [$payload], $this->userApiKey);
            $resp->throw();
            return $this->normalizeReceiptFromCreate($resp->json());
        }
        // Raw fallback
        $resp = $this->rawCreate([$payload]);
        $resp->throw();
        return $this->normalizeReceiptFromCreate($resp->json());
    }

    private function normalizeReceiptFromCreate($json): array
    {
        $obj = is_array($json) && array_is_list($json) ? ($json[0] ?? []) : (is_array($json) ? $json : []);
        $id           = $obj['Id'] ?? $obj['id'] ?? null;
        $receiptNo    = $obj['ReceiptNumber'] ?? $obj['receipt_number'] ?? $id;
        $external     = $obj['ExternalReceiptNumber'] ?? $obj['external'] ?? $obj['External'] ?? null;
        $salesDateUtc = $obj['SalesDateTime'] ?? $obj['salesDateTime'] ?? $obj['SalesDateTimeUtc'] ?? $obj['salesDateTimeUtc'] ?? null;
        $total        = (float)($obj['TotalAmount'] ?? $obj['total'] ?? $obj['Total'] ?? 0);

        if (!$id) throw new RuntimeException('METRC did not return an id.');

        return [
            'id'               => (int)$id,
            'receipt_number'   => (string)$receiptNo,
            'external'         => (string)($external ?? ''),
            'sales_date_time'  => $salesDateUtc,
            'total'            => $total,
        ];
    }

    /* ===================== Unfinalize (activate) / Delete (void) ===================== */
    /** Unfinalize (activate) a receipt by id (idempotent). */
    public function unfinalizeReceipt(int $id): void
    {
        if (method_exists($this->client, 'unfinalizeReceipts')) {
            $resp = $this->client->unfinalizeReceipts($this->licenseNumber, [['Id'=>(int)$id]], $this->userApiKey);
            $resp->throw();
            return;
        }
        foreach (['activateReceipts','setReceiptsActive','putReceiptsUnfinalize','unfinalizeSalesReceipts'] as $m) {
            if (method_exists($this->client, $m)) {
                $resp = $this->client->{$m}($this->licenseNumber, [['Id'=>(int)$id]], $this->userApiKey);
                $resp->throw();
                return;
            }
        }
        // Raw HTTP fallback
        $this->rawUnfinalize([$id])->throw();
    }

    /** Unfinalize many receipts by id (idempotent). */
    public function unfinalizeReceipts(array $ids): void
    {
        if (method_exists($this->client, 'unfinalizeReceipts')) {
            $rows = array_map(fn($i)=>['Id'=>(int)$i], $ids);
            $resp = $this->client->unfinalizeReceipts($this->licenseNumber, $rows, $this->userApiKey);
            $resp->throw();
            return;
        }
        $this->rawUnfinalize($ids)->throw();
    }

    /** Void (delete) a receipt by id (idempotent). */
    public function deleteReceipt(int $id): void
    {
        if (method_exists($this->client, 'deleteReceipt')) {
            $resp = $this->client->deleteReceipt($this->licenseNumber, (int)$id, $this->userApiKey);
            $resp->throw();
            return;
        }
        foreach (['voidReceipt','deleteSalesReceipt','deleteReceipts'] as $m) {
            if (method_exists($this->client, $m)) {
                $resp = $this->client->{$m}($this->licenseNumber, (int)$id, $this->userApiKey);
                $resp->throw();
                return;
            }
        }
        // Raw HTTP fallback
        $this->rawDelete($id)->throw();
    }

    /* ===================== Lookups (optional helpers) ===================== */
    /** Generic search pass-through; returns array (list or object). */
    public function searchReceipts(array $query = [])
    {
        foreach (['searchReceipts','listReceipts','getReceipts'] as $m) {
            if (method_exists($this->client, $m)) {
                $resp = $this->client->{$m}($query + ['licenseNumber'=>$this->licenseNumber], $this->userApiKey);
                return $resp->json();
            }
        }
        // Raw fallback
        $resp = $this->http()->get($this->baseUrl().'/sales/v2/receipts', $query);
        return $resp->json();
    }

    /** Convenience: try to find by external number. Returns normalized array or null. */
    public function findReceiptByExternal(string $external): ?array
    {
        $resp = $this->http()->get($this->baseUrl().'/sales/v2/receipts', [
            'externalReceiptNumber' => $external,
        ]);
        if ($resp->successful()) {
            $j = $resp->json();
            $row = null;
            if (is_array($j)) {
                if (isset($j['Id']) || isset($j['id'])) $row = $j;
                elseif (isset($j[0])) $row = $j[0];
            }
            if ($row) {
                $id           = $row['Id'] ?? $row['id'] ?? null;
                $receiptNo    = $row['ReceiptNumber'] ?? $row['receipt_number'] ?? $id;
                $salesDateUtc = $row['SalesDateTime'] ?? $row['salesDateTime'] ?? null;
                $total        = (float)($row['TotalAmount'] ?? $row['total'] ?? 0);
                if ($id) return [
                    'id'              => (int)$id,
                    'receipt_number'  => (string)$receiptNo,
                    'external'        => $external,
                    'sales_date_time' => $salesDateUtc,
                    'total'           => $total,
                ];
            }
        }
        return null;
    }

    /* ----------------------- RAW FALLBACKS ----------------------- */
    /** POST /sales/v2/receipts */
    private function rawCreate(array $rows): Response
    {
        $url  = $this->baseUrl().'/sales/v2/receipts';
        return $this->http()->post($url, $rows);
    }

    /** PUT /sales/v2/receipts/unfinalize */
    private function rawUnfinalize(array $ids): Response
    {
        $rows = array_map(fn($i)=>['Id'=>(int)$i], $ids);
        $url  = $this->baseUrl().'/sales/v2/receipts/unfinalize';
        return $this->http()->put($url, $rows);
    }

    /** DELETE /sales/v2/receipts/{id} */
    private function rawDelete(int $id): Response
    {
        $url = $this->baseUrl().'/sales/v2/receipts/'.((int)$id);
        return $this->http()->delete($url);
    }

    private function http()
    {
        $licenseParam = ['licenseNumber' => $this->licenseNumber];
        $vendorKey    = config('services.metrc.vendor_key') ?? config('metrc.vendor_key');
        $headers = [
            'Accept'            => 'application/json',
            'Content-Type'      => 'application/json',
            'x-metrc-api-key'   => $this->userApiKey,
        ];
        if ($vendorKey) $headers['x-metrc-vendor-key'] = $vendorKey;
        return Http::withHeaders($headers)
            ->asJson()
            ->withOptions([
                'query' => $licenseParam, // appends ?licenseNumber=...
                'http_errors' => false,
            ]);
    }

    private function baseUrl(): string
    {
        return rtrim(
            config('services.metrc.base_url')
            ?? config('metrc.base_url')
            ?? 'https://api.metrc.com'
        , '/');
    }

    private static function firstNonEmpty(object $row, array $candidates): ?string
    {
        foreach ($candidates as $c) {
            if (isset($row->{$c}) && trim((string)$row->{$c}) !== '') return (string)$row->{$c};
        }
        return null;
    }
}
