<?php

namespace App\Http\Requests;


use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class AdminUpdateRequest extends FormRequest
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

            'note' => ['required', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($v) {
            $in  = $this->input('clock_in_at');
            $out = $this->input('clock_out_at');

            // 1) 出勤・退勤（要件①）
            if ($in && $out && $out < $in) {
                $v->errors()->add('clock_out_at', '出勤時間もしくは退勤時間が不適切な値です');
            }

            foreach ([1, 2] as $no) {
                $bs = data_get($this->input('breaks'), "$no.start");
                $be = data_get($this->input('breaks'), "$no.end");

                // 休憩開始と終了の前後
                if ($bs && $be && $be < $bs) {
                    $v->errors()->add("breaks.$no.end", '休憩時間が不適切な値です');
                }

                // 2) 休憩開始が出勤より前 or 退勤より後
                if ($bs && $in && $bs < $in) {
                    $v->errors()->add("breaks.$no.start", '休憩時間が不適切な値です');
                }
                if ($bs && $out && $bs > $out) {
                    $v->errors()->add("breaks.$no.start", '休憩時間が不適切な値です');
                }

                // 3) 休憩終了が退勤より後
                if ($be && $out && $be > $out) {
                    $v->errors()->add("breaks.$no.end", '休憩時間もしくは退勤時間が不適切な値です');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'note.required' => '備考を記入してください',
        ];
    }
}
