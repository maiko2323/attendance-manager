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

            // 休憩は「両方入ってるか」をまずチェック
            foreach ([1, 2] as $no) {
                $bs = data_get($this->input('breaks'), "$no.start");
                $be = data_get($this->input('breaks'), "$no.end");

                if (($bs && !$be) || (!$bs && $be)) {
                    // 片方だけ入力
                    $v->errors()->add("breaks.$no.start", '休憩時間が不適切な値です');
                }
            }

            // 時刻の前後比較用（H:i → 分に変換）
            $toMinutes = function (?string $t): ?int {
                if (!$t) return null;
                [$h, $m] = array_map('intval', explode(':', $t));
                return $h * 60 + $m;
            };

            $inMin  = $toMinutes($in);
            $outMin = $toMinutes($out);

            // 出勤・退勤・休憩のいずれかが入力された場合は、出勤・退勤を両方必須にする
            $hasAnyBreak = false;
            foreach ([1, 2] as $no) {
                $bs = data_get($this->input('breaks'), "$no.start");
                $be = data_get($this->input('breaks'), "$no.end");
                if ($bs || $be) {
                    $hasAnyBreak = true;
                    break;
                }
            }
            $hasAnyTime = ($in || $out || $hasAnyBreak);

            $messageBoth = '出勤時間もしくは退勤時間が不適切な値です';
            $messageIn   = '出勤時間が不適切な値です';

            // 出勤・退勤が片方だけ（休憩だけ入力も含む）
            if ($hasAnyTime && (!$in || !$out)) {
                $v->errors()->add('clock_in_at', $messageBoth);
                $v->errors()->add('clock_out_at', $messageBoth);
                return;
            }

            // 出勤 > 退勤 → 出勤側だけにエラー（仕様：出勤時間が不適切）
            if ($inMin !== null && $outMin !== null && $inMin > $outMin) {
                $v->errors()->add('clock_in_at', $messageIn);
                return;
            }

            // 休憩開始 > 退勤、休憩終了 > 退勤（両方入力がある休憩だけ）
            foreach ([1, 2] as $no) {
                $bs = data_get($this->input('breaks'), "$no.start");
                $be = data_get($this->input('breaks'), "$no.end");

                if (!$bs || !$be) continue;

                $bsMin = $toMinutes($bs);
                $beMin = $toMinutes($be);

                // 休憩開始 > 退勤
                if ($bsMin !== null && $outMin !== null && $bsMin > $outMin) {
                    $v->errors()->add("breaks.$no.start", '休憩時間が不適切な値です');
                    continue;
                }

                // 休憩終了 > 退勤
                if ($beMin !== null && $outMin !== null && $beMin > $outMin) {
                    $v->errors()->add("breaks.$no.end", '休憩時間もしくは退勤時間が不適切な値です');
                    continue;
                }
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
