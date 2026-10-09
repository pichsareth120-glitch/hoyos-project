<?php

namespace App\Support;

class LabelHelper
{
    /**
     * Humanize database tokens and acronyms for UI presentation.
     */
    public static function humanize(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return match (strtolower($value)) {
            'cod' => 'COD',
            'aba_khqr' => 'ABA KHQR',
            default => ucfirst(str_replace('_', ' ', $value)),
        };
    }
}
