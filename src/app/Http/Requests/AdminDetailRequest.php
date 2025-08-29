<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminDetailRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->check() && auth()->user()->is_admin;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'break_start_1' => [
                'nullable',
                'date_format:H:i',
                'after:start_time',
                'before:end_time',
            ],
            'break_end_1' => [
                'nullable',
                'date_format:H:i',
                'after:break_start_1',
                'before:end_time',
            ],
            'note' => 'required|string|max:255',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'end_time.after' => '出勤時間もしくは退勤時間が不適切な値です。',
            'break_start_1.after' => '休憩時間が勤務時間外です。',
            'break_start_1.before' => '休憩時間が勤務時間外です。',
            'break_end_1.after' => '休憩時間が勤務時間外です。',
            'break_end_1.before' => '休憩時間が勤務時間外です。',
            'note.required' => '備考を記入してください。'
        ];
    }
}
