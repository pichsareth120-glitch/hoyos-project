<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DateBucketHelper
{
    /**
     * Get the configured reporting timezone.
     */
    public static function getTimezone(): string
    {
        return config('hoyos.report_timezone', 'Asia/Phnom_Penh');
    }

    /**
     * Convert local datetime boundary to UTC for database querying.
     */
    public static function toUtc(Carbon $localDate): Carbon
    {
        return $localDate->copy()->utc();
    }

    /**
     * Returns a database driver-aware SQL expression that formats a UTC column
     * into a local date or month bucket string.
     *
     * @param string $column Column name (e.g., 'created_at' or 'orders.created_at')
     * @param string $interval 'day' (YYYY-MM-DD) or 'month' (YYYY-MM)
     * @return string
     */
    public static function sqlDateBucket(string $column = 'created_at', string $interval = 'day'): string
    {
        $tz = self::getTimezone();
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            $safeTz = preg_replace('/[^a-zA-Z0-9_\-\/]/', '', $tz);
            $format = $interval === 'month' ? 'YYYY-MM' : 'YYYY-MM-DD';
            return "to_char(({$column} AT TIME ZONE 'UTC') AT TIME ZONE '{$safeTz}', '{$format}')";
        }

        // SQLite & in-memory test driver
        $offsetHours = Carbon::now($tz)->offsetHours;
        $offsetModifier = sprintf('%+d hours', $offsetHours);
        $format = $interval === 'month' ? '%Y-%m' : '%Y-%m-%d';

        return "strftime('{$format}', datetime({$column}, '{$offsetModifier}'))";
    }

    /**
     * Prevent CSV formula injection by prepending a single quote to dangerous leading characters.
     */
    public static function sanitizeCsvValue(mixed $value): string
    {
        $str = (string) $value;
        if ($str !== '' && in_array($str[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $str;
        }

        return $str;
    }

    /**
     * Format numeric currency string cleanly without floating-point precision errors.
     */
    public static function formatMoney(string|float|int|null $amount): string
    {
        $val = (float) ($amount ?? 0);
        return number_format($val, 2, '.', '');
    }
}
