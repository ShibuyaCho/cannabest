<?php
namespace App\Support;

class MetrcSig
{
    public static function normLabel(string $s): string {
        $s = strtoupper(trim($s));
        return preg_replace('/\s+/', '', $s);
    }
    public static function q($n): string { return number_format((float)$n, 3, '.', ''); } // qty
    public static function p($n): string { return number_format((float)$n, 2, '.', ''); } // price

    public static function lineStrict(string $label, float $qty, float $preTax): string {
        return self::normLabel($label) . '|' . self::q($qty) . '|' . self::p($preTax);
    }
    public static function lineRelaxed(string $label, float $qty): string {
        return self::normLabel($label) . '|' . self::q($qty);
    }
    public static function saleStrict(array $lineSigs): string {
        sort($lineSigs, SORT_STRING);
        return implode(';', $lineSigs);
    }
    public static function saleRelaxed(array $lineSigs): string {
        sort($lineSigs, SORT_STRING);
        return implode(';', $lineSigs);
    }
}
