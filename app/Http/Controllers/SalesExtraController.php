<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SalesExtraController extends Controller
{
    /**
     * Return lightweight sale items for a sale, in a schema-safe way.
     * We select si.* (and inv.* if inventories exists) then map best-guess
     * fields (name, qty, unit_price, label) without assuming column names.
     */
    public function items($saleId)
    {
        // 1) Which table contains line items?
        $itemTable = Schema::hasTable('sale_items')
            ? 'sale_items'
            : (Schema::hasTable('sale_lines') ? 'sale_lines' : null);

        if (!$itemTable) {
            return response()->json(['items' => []]);
        }

        // 2) Try to join inventories/products if possible (to mine label-ish fields)
        $joinCol = Schema::hasColumn($itemTable, 'inventory_id') ? 'inventory_id'
                 : (Schema::hasColumn($itemTable, 'product_id')   ? 'product_id'   : null);

        $invTable = Schema::hasTable('inventories') ? 'inventories'
                  : (Schema::hasTable('products')    ? 'products'    : null);

        $q = DB::table("$itemTable as si")->where('si.sale_id', $saleId);

        if ($joinCol && $invTable) {
            $q->leftJoin("$invTable as inv", 'inv.id', '=', "si.$joinCol")
              ->select('si.*', 'inv.*')
              ->addSelect(DB::raw('si.id as si_id'))
              ->addSelect(DB::raw('inv.id as inv_id'));
        } else {
            $q->select('si.*')->addSelect(DB::raw('si.id as si_id'));
        }

        $rows = $q->get();

        // Heuristic field sets
        $nameCols   = ['name','item_name','product_name','variant_name','description','title','line_text','comment','notes'];
        $qtyCols    = ['quantity','qty','units','count','unit_qty','quantity_sold','sold_qty'];
        $priceCols  = ['price','unit_price','unitprice','unit_cost','unitcost','price_each','unit_amount'];
        $totalCols  = ['line_total','extended_total','extended_price','subtotal','amount','total','row_total'];

        $labelColsSi  = ['package_label','metrc_package_label','metrc_package','metrc_tag','metrc_label','package_tag','label','tag','package','pkg','metrc_pkg','package_number','trace_id','sku','barcode','serial'];
        $labelColsInv = ['package_label','metrc_package_label','metrc_package','metrc_tag','metrc_label','package_tag','label','tag','package','pkg','metrc_pkg','package_number','trace_id','sku','barcode','serial'];

        $deletedCols  = ['deleted','is_deleted','voided','canceled','removed','refunded'];
        $receiptCols  = ['on_receipt','print_on_receipt','show_on_receipt','include_on_receipt'];

        $looksLikeMetrc = function ($s) {
            $s = strtoupper(trim((string)$s));
            if ($s === '') return false;
            // canonical plant/package tag shape (>=24 chars, begins with 1)
            if (preg_match('/\b(1[A-Z0-9]{23,})\b/', $s)) return true;
            // “PKG:” hints on receipts/text
            if (preg_match('/\bPKG\b|PKG\s*[:#-]/i', $s)) return true;
            return false;
        };

        $items = $rows->map(function ($r) use (
            $nameCols, $qtyCols, $priceCols, $totalCols,
            $labelColsSi, $labelColsInv, $deletedCols, $receiptCols, $looksLikeMetrc
        ) {
            $arr = (array)$r; // easier to probe keys

            // name
            $name = '';
            foreach ($nameCols as $c) {
                if (array_key_exists($c, $arr) && trim((string)$arr[$c]) !== '') { $name = (string)$arr[$c]; break; }
                $invKey = "inv_$c"; // if your join aliased, support this too
                if (array_key_exists($invKey, $arr) && trim((string)$arr[$invKey]) !== '') { $name = (string)$arr[$invKey]; break; }
            }

            // qty
            $qty = 0.0;
            foreach ($qtyCols as $c) {
                if (array_key_exists($c, $arr) && is_numeric($arr[$c])) { $qty = (float)$arr[$c]; break; }
            }
            if ($qty <= 0) $qty = 1.0; // sane fallback

            // unit price (try unit price; else derive from totals)
            $unit = 0.0;
            foreach ($priceCols as $c) {
                if (array_key_exists($c, $arr) && is_numeric($arr[$c])) { $unit = (float)$arr[$c]; break; }
            }
            if ($unit <= 0.0) {
                foreach ($totalCols as $tc) {
                    if (array_key_exists($tc, $arr) && is_numeric($arr[$tc])) {
                        $tot = max(0.0, (float)$arr[$tc]);
                        $unit = $qty > 0 ? round($tot / $qty, 4) : $tot;
                        break;
                    }
                }
            }

            // label-ish sniffing: prefer sale-item columns; then inventory-side
            $label = null;
            foreach ($labelColsSi as $c) {
                if (array_key_exists($c, $arr) && trim((string)$arr[$c]) !== '') { $label = (string)$arr[$c]; break; }
            }
            if ($label === null) {
                foreach ($labelColsInv as $c) {
                    // support both "inv_foo" and plain "foo" when we selected inv.*
                    if (array_key_exists("inv_$c", $arr) && trim((string)$arr["inv_$c"]) !== '') { $label = (string)$arr["inv_$c"]; break; }
                    if (array_key_exists($c, $arr)       && trim((string)$arr[$c])       !== '') { $label = (string)$arr[$c];       break; }
                }
            }
            // also peek into text fields if label still missing
            if ($label === null) {
                foreach (array_merge($nameCols, ['notes','line_text','comment','description']) as $t) {
                    if (array_key_exists($t, $arr) && trim((string)$arr[$t]) !== '') { $label = (string)$arr[$t]; if ($label) break; }
                }
            }

            // deleted?
            $deleted = false;
            foreach ($deletedCols as $c) {
                if (array_key_exists($c, $arr)) { $deleted = (bool)$arr[$c]; break; }
            }

            // show on receipt?
            $onReceipt = true;
            foreach ($receiptCols as $c) {
                if (array_key_exists($c, $arr)) { $onReceipt = (bool)$arr[$c]; break; }
            }

            // inventory exists?
            $invExists = true;
            foreach (['inv_deleted', 'inv_deleted_at', 'deleted_at'] as $k) {
                if (array_key_exists($k, $arr) && $arr[$k]) { $invExists = false; break; }
            }

            // quick flag for UI: does any text look like a PKG/METRC tag?
            $has_pkg_hint = $looksLikeMetrc($label) || $looksLikeMetrc($name);

            return [
                // prefer the sale-item id; fall back to plain id if alias missing
                'id'               => $arr['si_id'] ?? ($arr['id'] ?? null),
                'name'             => $name,
                'qty'              => (float)$qty,
                'unit_price'       => (float)$unit,
                'label'            => $label,          // may be null
                'has_pkg_hint'     => $has_pkg_hint,   // helper for client
                'deleted'          => (bool)$deleted,
                'on_receipt'       => (bool)$onReceipt,
                'inventory_exists' => (bool)$invExists,
            ];
        });

        return response()->json(['items' => $items]);
    }
}
