<?php

namespace App\Http\Requests\Admin;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
            'format' => ['nullable', 'string', 'in:html,csv'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $fromStr = $this->input('date_from');
            $toStr = $this->input('date_to');

            if ($fromStr && $toStr) {
                $tz = config('hoyos.report_timezone', 'Asia/Phnom_Penh');
                try {
                    $cFrom = Carbon::createFromFormat('Y-m-d', $fromStr, $tz)->startOfDay();
                    $cTo = Carbon::createFromFormat('Y-m-d', $toStr, $tz)->endOfDay();

                    if ($cFrom->gt($cTo)) {
                        $validator->errors()->add('date_from', 'Start date must be before or equal to end date.');
                    } elseif ($cFrom->diffInDays($cTo) > 366) {
                        $validator->errors()->add('date_to', 'Date range cannot exceed 366 days.');
                    }
                } catch (\Throwable $e) {
                    $validator->errors()->add('date_from', 'Invalid date supplied.');
                }
            }
        });
    }

    /**
     * Resolve the normalized Carbon start and end dates in local timezone.
     * Default: Last 30 days.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function dateRange(): array
    {
        $tz = config('hoyos.report_timezone', 'Asia/Phnom_Penh');
        $now = Carbon::now($tz);

        if ($this->filled('date_from') && $this->filled('date_to')) {
            $from = Carbon::createFromFormat('Y-m-d', $this->input('date_from'), $tz)->startOfDay();
            $to = Carbon::createFromFormat('Y-m-d', $this->input('date_to'), $tz)->endOfDay();
        } else {
            // Default last 30 days (inclusive of today)
            $to = $now->copy()->endOfDay();
            $from = $now->copy()->subDays(29)->startOfDay();
        }

        return [$from, $to];
    }
}
