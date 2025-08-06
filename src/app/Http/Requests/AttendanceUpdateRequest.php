<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class AttendanceUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => [
                'required',
                'date_format:H:i',
                'after_or_equal:start_time',
            ],
            'breaks' => ['array'],
            'breaks.*.start_time' => [
                'nullable',
                'date_format:H:i',

                function ($attribute, $value, $fail) {
                    if (empty($value)) return; // 値がなければチェックしない

                    $index = explode('.', $attribute)[1]; // breaks.0.start_time の 0 を取得

                    $breakStartTime = Carbon::parse($value);
                    $attendanceStartTime = Carbon::parse($this->input('start_time'));
                    $attendanceEndTime = Carbon::parse($this->input('end_time'));
                    $breakEndTimeInput = $this->input("breaks.{$index}.end_time");

                    // 休憩開始が勤務時間外 (出勤前か退勤後)
                    if ($breakStartTime->lessThan($attendanceStartTime) || $breakStartTime->greaterThan($attendanceEndTime)) {
                        $fail('休憩時間が不適切な値です。');
                    }

                    // 休憩開始が休憩終了より後 (かつ、休憩終了が入力されている場合のみ)
                    if (!empty($breakEndTimeInput)) {
                        $breakEndTime = Carbon::parse($breakEndTimeInput);
                        if ($breakStartTime->greaterThan($breakEndTime)) {
                            $fail('休憩開始時間が不適切な値です。'); // 具体的なメッセージ
                        }
                    }
                },
            ],
            'breaks.*.end_time' => [
                'nullable',
                'date_format:H:i',
                // 休憩終了時間が出勤時間より前、または退勤時間より後でないこと
                // 休憩終了時間が対応する休憩開始時間より後であること (カスタムルールで処理済み)
                function ($attribute, $value, $fail) {
                    if (empty($value)) return; // 値がなければチェックしない

                    $index = explode('.', $attribute)[1];
                    $breakEndTime = Carbon::parse($value);
                    $attendanceStartTime = Carbon::parse($this->input('start_time'));
                    $attendanceEndTime = Carbon::parse($this->input('end_time'));
                    $breakStartTimeInput = $this->input("breaks.{$index}.start_time");

                    // 休憩終了が勤務時間外 (出勤前か退勤後)
                    if ($breakEndTime->lessThan($attendanceStartTime) || $breakEndTime->greaterThan($attendanceEndTime)) {
                        $fail('休憩時間が不適切な値です。');
                    }

                    if (!empty($breakStartTimeInput)) {
                        $breakStartTime = Carbon::parse($breakStartTimeInput);
                        if ($breakEndTime->lessThan($breakStartTime)) {
                             
                        }
                    }
                },
            ],
            'note' => ['nullable', 'string', 'max:500'], 
        ];
    }

    public function messages()
    {
         return [
            'start_time.required' => '出勤時間は必須です。',
            'end_time.required' => '退勤時間は必須です。',
            'end_time.after_or_equal' => '出勤時間もしくは退勤時間が不適切な値です。',
            'note.required' => '備考を記入してください。',
        ];
    }

    protected function prepareForValidation(): void
    {
        $breaks = collect($this->input('breaks'))->filter(function ($break) {
            return !empty($break['start_time']) || !empty($break['end_time']);
        })->values()->all();

        $this->merge([
            'breaks' => $breaks,
        ]);
    }
}
