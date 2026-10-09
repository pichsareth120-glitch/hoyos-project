<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\HtmlString;

class Time
{
    /**
     * Get the configured administrative timezone.
     */
    public static function timezone(): string
    {
        return config('hoyos.timezone') ?? config('hoyos.report_timezone', 'Asia/Phnom_Penh');
    }

    /**
     * Format a timestamp in ICT with an "ICT" label and UTC tooltip.
     *
     * @param DateTimeInterface|string|null $date
     * @param string $format
     * @param bool $showLabel
     * @param string|null $class
     * @return HtmlString
     */
    public static function format($date, string $format = 'd M Y, H:i', bool $showLabel = true, ?string $class = null): HtmlString
    {
        if (!$date) {
            return new HtmlString('—');
        }

        $carbon = $date instanceof DateTimeInterface ? Carbon::instance($date) : Carbon::parse($date);
        $local = $carbon->copy()->setTimezone(self::timezone());
        $utc = $carbon->copy()->setTimezone('UTC');

        $utcTooltip = e($utc->format('Y-m-d H:i:s') . ' UTC');
        $localText = e($local->format($format) . ($showLabel ? ' ICT' : ''));
        $iso = e($utc->toIso8601String());

        $classes = trim('cursor-help ' . ($class ?? ''));

        return new HtmlString("<time datetime=\"{$iso}\" title=\"{$utcTooltip}\" class=\"{$classes}\">{$localText}</time>");
    }

    /**
     * Format date portion in ICT with UTC tooltip.
     */
    public static function date($date, string $format = 'd M Y', ?string $class = null): HtmlString
    {
        return self::format($date, $format, false, $class);
    }

    /**
     * Format time portion in ICT with ICT label and UTC tooltip.
     */
    public static function time($date, string $format = 'H:i', bool $showLabel = true, ?string $class = null): HtmlString
    {
        return self::format($date, $format, $showLabel, $class);
    }

    /**
     * Relative diff with exact timestamps in tooltip.
     */
    public static function diffForHumans($date): HtmlString
    {
        if (!$date) {
            return new HtmlString('—');
        }

        $carbon = $date instanceof DateTimeInterface ? Carbon::instance($date) : Carbon::parse($date);
        $local = $carbon->copy()->setTimezone(self::timezone());
        $utc = $carbon->copy()->setTimezone('UTC');

        $tooltip = e($local->format('d M Y, H:i:s') . ' ICT (' . $utc->format('Y-m-d H:i:s') . ' UTC)');
        $diff = e($carbon->diffForHumans());
        $iso = e($utc->toIso8601String());

        return new HtmlString("<time datetime=\"{$iso}\" title=\"{$tooltip}\" class=\"cursor-help\">{$diff}</time>");
    }
}
