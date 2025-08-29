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
                    if (empty($value)) return; 

                    $index = explode('.', $attribute)[1]; 

                    $breakStartTime = Carbon::parse($value);
                    $attendanceStartTime = Carbon::parse($this->input('start_time'));
                    $attendanceEndTime = Carbon::parse($this->input('end_time'));
                    $breakEndTimeInput = $this->input("breaks.{$index}.end_time");

                    if ($breakStartTime->lessThan($attendanceStartTime) || $breakStartTime->greaterThan($attendanceEndTime)) {
                        $fail('休憩時間が不適切な値です。');
                    }

                    if (!empty($breakEndTimeInput)) {
                        $breakEndTime = Carbon::parse($breakEndTimeInput);
                        if ($breakStartTime->greaterThan($breakEndTime)) {
                            $fail('休憩開始時間が不適切な値です。'); 
                        }
                    }
                },
            ],
            'breaks.*.end_time' => [
                'nullable',
                'date_format:H:i',

                function ($attribute, $value, $fail) {
                    if (empty($value)) return; 

                    $index = explode('.', $attribute)[1];
                    $breakEndTime = Carbon::parse($value);
                    $attendanceStartTime = Carbon::parse($this->input('start_time'));
                    $attendanceEndTime = Carbon::parse($this->input('end_time'));
                    $breakStartTimeInput = $this->input("breaks.{$index}.start_time");

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
