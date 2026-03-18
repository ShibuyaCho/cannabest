<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class MetrcReceipt extends Model
{
    protected $table = 'metrc_receipts';

    protected $fillable = [
        'organization_id',
        'user_id',
        'metrc_id',
        'receipt_number',
        'external_receipt_number',
        'license_number',
        'sales_date_time',   // METRC DateSold stored in UTC
        'total_price',
        'is_final',
        'raw',               // controller persists raw METRC JSON here
    ];

    protected $casts = [
        'metrc_id'         => 'integer',
        'sales_date_time'  => 'datetime',
        'total_price'      => 'decimal:2',
        'is_final'         => 'boolean',
        'raw'              => 'array',
    ];

    /** The POS sale linked to this receipt (via sales.metrc_receipt_id). */
    public function sale(): HasOne
    {
        // IMPORTANT: your Sale is App\Sale (not App\Models\Sale)
        return $this->hasOne(\App\Sale::class, 'metrc_receipt_id', 'id');
    }

    /** Scope: only an org */
    public function scopeForOrg(Builder $q, $orgId): Builder
    {
        return $q->when($orgId, fn($qq) => $qq->where('organization_id', $orgId));
    }

    /**
     * Scope: DateSold window (expects $start/$end are store-TZ 'Y-m-d' or Carbon).
     * Converts to UTC and filters against sales_date_time (stored UTC).
     */
    public function scopeSalesDateWindow(Builder $q, $start, $end, string $storeTz = 'UTC'): Builder
    {
        $s = $start instanceof Carbon ? $start->copy() : Carbon::parse($start, $storeTz);
        $e = $end   instanceof Carbon ? $end->copy()   : Carbon::parse($end,   $storeTz);
        $sUtc = $s->startOfDay()->utc();
        $eUtc = $e->endOfDay()->utc();
        return $q->whereBetween('sales_date_time', [$sUtc, $eUtc]);
    }
}
