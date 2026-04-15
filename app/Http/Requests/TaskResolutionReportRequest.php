<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

class TaskResolutionReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;// TODO Implement authorize() method
    }

    public function rules(): array
    {
        return [
            'startDate' => ['required', 'date', 'date_format:Y-m-d'],
            'endDate' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:startDate'],
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        $unexpectedParams = array_diff(array_keys($this->query()), config('reporting.allowed_params'));

        if (! empty($unexpectedParams)) {
            $validator->after(function ($validator) use ($unexpectedParams) {
                $validator->errors()->add(
                    'parameters',
                    'Unexpected parameters found: '.implode(', ', $unexpectedParams)
                );
            });
        }
    }

    public function after(): array
    {
        return [
            function ($validator) {
                if ($validator->errors()->any()) {
                    return;
                }

                // prevent users from grabbing too much data can ultimately store this in env
                $startDate = new \DateTimeImmutable($this->input('startDate'));
                $endDate = new \DateTimeImmutable($this->input('endDate'));
                $days = $startDate->diff($endDate)->days;

                $maxDays = config('reporting.max_range_days');

                if ($days > $maxDays) {
                    $validator->errors()->add(
                        'endDate',
                        "Date range must not exceed {$maxDays} days."
                    );
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'startDate.required' => 'A start date is required.',
            'startDate.date' => 'Start date must be a valid date.',
            'startDate.date_format' => 'Start date must be in YYYY-MM-DD format.',
            'endDate.required' => 'An end date is required.',
            'endDate.date' => 'End date must be a valid date.',
            'endDate.date_format' => 'End date must be in YYYY-MM-DD format.',
            'endDate.after_or_equal' => 'End date must be on or after the start date.'
        ];
    }
}
