<?php

namespace App\Http\Requests;


use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class AttendanceUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'clock_in_at'  => ['nullable', 'date_format:H:i'],
            'clock_out_at' => ['nullable', 'date_format:H:i'],

            'breaks.1.start' => ['nullable', 'date_format:H:i'],
            'breaks.1.end'   => ['nullable', 'date_format:H:i'],

            'breaks.2.start' => ['nullable', 'date_format:H:i'],
            'breaks.2.end'   => ['nullable', 'date_format:H:i'],

            'reason' => ['required', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($v) {

            $in  = $this->input('clock_in_at');
            $out = $this->input('clock_out_at');

            $hasAnyBreak = false;
            foreach ([1, 2] as $no) {
                $bs = data_get($this->input('breaks'), "$no.start");
                $be = data_get($this->input('breaks'), "$no.end");
                if (($bs && !$be) || (!$bs && $be)) {
                    $v->errors()->add("breaks.$no.start", '休憩時間が不適切な値です');
                    continue;
                }
            }

            $hasAnyTime = ($in || $out || $hasAnyBreak);

            if ($hasAnyTime && (!$in || !$out)) {
                $v->errors()->add('clock_out_at', '出勤時間もしくは退勤時間が不適切な値です');
                return;
            }
        });
    }

    public function messages(): array
    {
        return [
            'reason.required' => '備考を記入してください',
        ];
    }
}
