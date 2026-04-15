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
            'from' => ['required', 'date', 'date_format:Y-m-d'],
            'to' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:from'],
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
                $startDate = new \DateTimeImmutable($this->input('from'));
                $endDate = new \DateTimeImmutable($this->input('to'));
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
            'from.required' => 'From date is required.',
            'from.date' => 'From date must be a valid date.',
            'from.date_format' => 'From date must be in YYYY-MM-DD format.',
            'to.required' => 'To date is required.',
            'to.date' => 'To date must be a valid date.',
            'to.date_format' => 'To date must be in YYYY-MM-DD format.',
            'to.after_or_equal' => 'To date must be on or after the from date.'
        ];
    }
}
