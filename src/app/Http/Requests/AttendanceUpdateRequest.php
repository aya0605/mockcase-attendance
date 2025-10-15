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
                // after_or_equal:start_time は、カスタムロジックでメッセージを統合するため、
                // messages()から削除し、ここではそのままにしておきます。
            ],
            'breaks' => ['array'],
            
            'breaks.*.start_time' => [
                'nullable',
                'date_format:H:i',
                // 休憩開始時間に関するカスタムバリデーション
                $this->getBreakTimeValidator('start'),
            ],
            
            'breaks.*.end_time' => [
                'nullable',
                'date_format:H:i',
                // 休憩終了時間に関するカスタムバリデーション
                $this->getBreakTimeValidator('end'),
            ],
            
            // FN029-3: 備考欄が未入力の場合にエラーを出す (修正申請時は必須を想定)
            'note' => ['required', 'string', 'max:500'], 
        ];
    }
    
    /**
     * 休憩時間に関するカスタムバリデーションルールを生成
     * @param string $type 'start' or 'end'
     * @return \Closure
     */
    protected function getBreakTimeValidator(string $type): \Closure
    {
        return function ($attribute, $value, $fail) use ($type) {
            if (empty($value)) return; 

            $index = explode('.', $attribute)[1];
            
            // 打刻時間の取得
            $attendanceStartTime = Carbon::parse($this->input('start_time'));
            $attendanceEndTime = Carbon::parse($this->input('end_time'));
            
            $breakTime = Carbon::parse($value);
            
            // FN029-2: 休憩開始/終了時間が出勤・退勤時間を超えていないか
            // 休憩開始時間が勤務開始より前、または休憩終了時間が勤務終了より後かをチェック
            $isOutOfWorkTime = ($type === 'start' && $breakTime->lt($attendanceStartTime)) ||
                               ($type === 'end' && $breakTime->gt($attendanceEndTime));

            if ($isOutOfWorkTime) {
                $fail('休憩時間が勤務時間外です');
            }
            
            // 休憩開始と終了の順序チェック (start_timeのバリデーションでのみ実行すればOK)
            if ($type === 'start') {
                $breakEndTimeInput = $this->input("breaks.{$index}.end_time");
                if (!empty($breakEndTimeInput)) {
                    $breakEndTime = Carbon::parse($breakEndTimeInput);
                    // 休憩開始が休憩終了より後であればエラー
                    if ($breakTime->gt($breakEndTime)) { 
                        $fail('休憩開始時間が不適切な値です。'); 
                    }
                }
            }
        };
    }

    public function messages()
    {
         return [
            'start_time.required' => '出勤時間は必須です。',
            'end_time.required' => '退勤時間は必須です。',
            
            // FN029-1: 出勤時間 vs 退勤時間のエラーメッセージ統合
            'end_time.after_or_equal' => '出勤時間もしくは退勤時間が不適切な値です。', 
            
            // FN029-3 の対応
            'note.required' => '備考を記入してください。',
            
            // 休憩の標準エラーメッセージ
            'breaks.*.start_time.date_format' => '休憩開始時間の形式が不適切です。',
            'breaks.*.end_time.date_format' => '休憩終了時間の形式が不適切です。',
        ];
    }

    // FN029-1 のエラーメッセージ統合のため、withValidatorで最終チェック
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $startTime = $this->input('start_time');
            $endTime = $this->input('end_time');
            
            if ($startTime && $endTime) {
                $start = Carbon::parse($startTime);
                $end = Carbon::parse($endTime);
                
                // 勤務開始が勤務終了より後の場合 (after_or_equal で弾かれるが出勤と退勤が逆の場合を考慮)
                if ($start->gt($end)) {
                    // end_timeにエラーを追加し、FN029-1のメッセージを出す
                    $validator->errors()->add('end_time', '出勤時間もしくは退勤時間が不適切な値です');
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        // 休憩開始/終了の片方のみ入力されているレコードを抽出（バリデーション対象とする）
        // ただし、全ての休憩の start_time と end_time が空の場合は配列から除外
        $breaks = collect($this->input('breaks'))->filter(function ($break) {
            return !empty($break['start_time']) || !empty($break['end_time']);
        })->values()->all();

        $this->merge([
            'breaks' => $breaks,
            // 備考がnullの場合に空文字列に変換し、'required'を機能させる
            'note' => $this->input('note') ?? '',
        ]);
    }
}