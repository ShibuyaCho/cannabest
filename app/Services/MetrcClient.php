<?php
// File: app/Services/MetrcClient.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;

class MetrcClient
{
    private const BASE_URL     = 'https://api-or.metrc.com'; // Oregon example; override via MetrcApi raw when needed
    public  const SOFTWARE_KEY = '-wEp3jabxlsIYVhclDdbdRmMvazx557w7P5TEeYN2OeAOyWV';

    /** Keep constructor private to enforce factory usage */
    private function __construct() {}

    /** ✅ Factory so other code can safely build an instance */
    public static function make(): self
    {
        return new self();
    }

    /** Build a configured HTTP client bound to a specific user API key */
    private function http(string $userApiKey)
    {
        return Http::withBasicAuth(self::SOFTWARE_KEY, $userApiKey)
            ->acceptJson()
            ->withHeaders(['Content-Type' => 'application/json'])
            ->timeout(30)
            ->retry(3, 300, function ($exception /*, $request */) {
                // Network or transport error? retry
                if (!($exception instanceof RequestException)) return true;
                $res = $exception->response;           // Illuminate\Http\Client\Response|null
                if (!$res) return true;                // e.g., connect timeout
                $s = $res->status();
                return $s === 429 || $s >= 500;        // retry on 429/5xx
            });
    }

    private function url(string $path, array $qs = []): string
    {
        $qs = array_filter($qs, fn($v) => $v !== null && $v !== '');
        $q  = $qs ? ('?' . http_build_query($qs)) : '';
        return rtrim(self::BASE_URL, '/') . $path . $q;
    }

    private function unwrap(array $json): array
    {
        if (isset($json['Data']) && is_array($json['Data'])) return $json['Data'];
        return $json;
    }

    /** Shared pager for list endpoints */
    private function listPaged(string $path, string $license, array $params, string $userApiKey): array
    {
        $size = (int)($params['pageSize'] ?? 20);
        if ($size < 1 || $size > 20) $size = 20;
        $page = max(1, (int)($params['pageNumber'] ?? 1));
        unset($params['pageNumber'], $params['pageSize']);

        $base = array_merge($params, ['licenseNumber' => $license]);
        $out  = [];

        while (true) {
            $qs   = array_merge($base, ['pageNumber' => $page, 'pageSize' => $size]);
            $resp = $this->http($userApiKey)->get($this->url($path, $qs));
            $resp->throw();

            $json  = $resp->json();
            $chunk = $this->unwrap(is_array($json) ? $json : []);
            if (empty($chunk)) break;

            $out = array_merge($out, $chunk);

            $totalPages    = (int)($json['TotalPages'] ?? 0);
            $recordsOnPage = (int)($json['RecordsOnPage'] ?? count($chunk));

            if ($totalPages > 0) {
                if ($page >= $totalPages) break;
                $page++;
            } else {
                if ($recordsOnPage < $size) break;
                $page++;
            }
        }

        return $out;
    }

    /* -------- Receipts v2 -------- */

    public function listReceiptsActive(string $license, array $params, string $userApiKey): array
    {
        return $this->listPaged('/sales/v2/receipts/active', $license, $params, $userApiKey);
    }

    public function listReceiptsInactive(string $license, array $params, string $userApiKey): array
    {
        return $this->listPaged('/sales/v2/receipts/inactive', $license, $params, $userApiKey);
    }

    /** GET /sales/v2/receipts?… — generic listing/search passthrough */
    public function listReceipts(array $params, string $userApiKey): Response
    {
        $license = $params['licenseNumber'] ?? '';
        return $this->http($userApiKey)->get($this->url('/sales/v2/receipts', $params + ['licenseNumber'=>$license]));
    }

    /** Back-compat alias */
    public function getReceipts(array $params, string $userApiKey): Response
    {
        return $this->listReceipts($params, $userApiKey);
    }

    public function getReceiptByExternal(string $license, string $external, string $userApiKey): ?array
    {
        $resp = $this->http($userApiKey)->get(
            $this->url('/sales/v2/receipts/external/' . rawurlencode($external), [
                'licenseNumber' => $license,
            ])
        );
        if ($resp->status() === 404) return null;
        $resp->throw();
        return $resp->json();
    }

    /** Returns Illuminate\Http\Client\Response */
    public function createReceipts(string $license, array $receipts, string $userApiKey): Response
    {
        return $this->http($userApiKey)->post(
            $this->url('/sales/v2/receipts', ['licenseNumber' => $license]),
            $receipts
        );
    }

    /** PUT /sales/v2/receipts/unfinalize */
    public function unfinalizeReceipts(string $license, array $rows, string $userApiKey): Response
    {
        return $this->http($userApiKey)->put(
            $this->url('/sales/v2/receipts/unfinalize', ['licenseNumber' => $license]),
            $rows
        );
    }

    /** DELETE /sales/v2/receipts/{id} */
    public function deleteReceipt(string $license, int $id, string $userApiKey): Response
    {
        return $this->http($userApiKey)->delete(
            $this->url("/sales/v2/receipts/{$id}", ['licenseNumber' => $license])
        );
    }
}
