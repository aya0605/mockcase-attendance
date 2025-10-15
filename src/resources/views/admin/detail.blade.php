@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/detail.css') }}">
@endsection

@section('content')
<div class="detail__content">
    
    <div class="detail__header">
        <h1 class="content__header--item" style="font-size: 28px; font-weight: bold;">勤怠詳細</h1>
    </div>

    <div id="statusMessage" class="form" style="max-width: 100vh; margin: 0 auto 20px;">
    </div>
    
    @if ($errors->any())
        <div class="form" style="max-width: 100vh; margin: 0 auto 20px;">
            <div class="alert alert-danger" style="padding: 15px; border-radius: 4px;">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form class="form" id="approveForm" action="{{ url('admin/applications/' . $application->id . '/approve') }}" method="POST">
        @csrf
        
        <div class="form__content">
            
            {{-- 1. 名前 --}}
            <div class="form__group">
                <p class="form__header">名前</p>
                <div class="form__input-group">
                    <input class="form__input form__input--name" type="text" id="name" value="{{ $application->user->name }}" readonly>
                </div>
            </div>

            {{-- 2. 日付 --}}
            <div class="form__group">
                <p class="form__header">日付</p>
                <div class="form__input-group">
                    <input class="form__input" type="text" id="date" 
                        value="{{ $application->attendance->work_date->format('Y年　　　　　　m月d日') }}" readonly>
                </div>
            </div>

            {{-- 3. 出勤・退勤 (申請された時間) --}}
            <div class="form__group">
                <p class="form__header">出勤・退勤</p>
                <div class="form__input-group">
                    <input class="form__input" type="time" id="start_time" name="start_time" 
                        value="{{ $application->applied_start_time ? \Carbon\Carbon::parse($application->applied_start_time)->format('H:i') : '' }}">
                    <p>〜</p>
                    <input class="form__input" type="time" id="end_time" name="end_time" 
                        value="{{ $application->applied_end_time ? \Carbon\Carbon::parse($application->applied_end_time)->format('H:i') : '' }}">
                </div>
            </div>

            {{-- 4. 休憩1 (申請された時間) --}}
            <div class="form__group">
                <p class="form__header">休憩1</p>
                <div class="form__input-group">
                    @php
                        $appliedBreaks = json_decode($application->applied_breaks, true);
                        
                        $break1_db_start = isset($appliedBreaks[0]['start_time']) ? \Carbon\Carbon::parse($appliedBreaks[0]['start_time'])->format('H:i') : '';
                        $break1_db_end = isset($appliedBreaks[0]['end_time']) ? \Carbon\Carbon::parse($appliedBreaks[0]['end_time'])->format('H:i') : '';
                        
                        $input1_start = old('break_start_1', $break1_db_start);
                        $input1_end = old('break_end_1', $break1_db_end);
                        
                        $start1_type = empty($input1_start) ? 'text' : 'time';
                        $end1_type = empty($input1_end) ? 'text' : 'time';
                    @endphp
                    <input class="form__input" type="{{ $start1_type }}" name="break_start_1" value="{{ $input1_start }}">
                    <p>〜</p>
                    <input class="form__input" type="{{ $end1_type }}" name="break_end_1" value="{{ $input1_end }}">
                </div>
            </div>

            {{-- 5. 休憩2 (申請された時間) --}}
            <div class="form__group">
                <p class="form__header">休憩2</p>
                <div class="form__input-group">
                    @php
                        $break2_db_start = isset($appliedBreaks[1]['start_time']) ? \Carbon\Carbon::parse($appliedBreaks[1]['start_time'])->format('H:i') : '';
                        $break2_db_end = isset($appliedBreaks[1]['end_time']) ? \Carbon\Carbon::parse($appliedBreaks[1]['end_time'])->format('H:i') : '';
                        
                        $input2_start = old('break_start_2', $break2_db_start);
                        $input2_end = old('break_end_2', $break2_db_end);

                        $start2_type = empty($input2_start) ? 'text' : 'time';
                        $end2_type = empty($input2_end) ? 'text' : 'time';
                    @endphp
                    <input class="form__input" type="{{ $start2_type }}" name="break_start_2" value="{{ $input2_start }}">
                    <p>〜</p>
                    <input class="form__input" type="{{ $end2_type }}" name="break_end_2" value="{{ $input2_end }}">
                </div>
            </div>

            {{-- 6. 備考 (申請された内容) --}}
            <div class="form__group">
                <p class="form__header">備考</p>
                <div class="form__input-group">
                    <textarea class="form__textarea" id="note" name="note" rows="4">{{ old('note', $application->note) }}</textarea>
                </div>
            </div>
        </div>

        {{-- 4. 承認ボタン --}}
        <div class="form__button">
            <button type="submit" class="form__button--submit">承認</button>
        </div>
        
    </form>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {

        const approveForm = document.getElementById('approveForm');
        const submitButton = approveForm.querySelector('button[type="submit"]');
        const statusMessageContainer = document.getElementById('statusMessage');

        if ('{{ $application->status }}' === 'approved') {
            submitButton.textContent = '承認済み';
            submitButton.disabled = true;
            submitButton.style.backgroundColor = '#ccc'; 
        }


        if (!approveForm) {
            console.error('エラー: フォームID「approveForm」が見つかりませんでした。');
            return;
        }

        approveForm.addEventListener('submit', async function(event) {
            event.preventDefault();

            if (submitButton.disabled && submitButton.textContent === '承認済み') {
                return;
            }

            submitButton.disabled = true;
            submitButton.textContent = '承認中...';
            statusMessageContainer.innerHTML = '';

            const formData = new FormData(approveForm);
            
            const data = {};
           
            const breaks = [];
            let currentBreak = {};

            for (let pair of formData.entries()) {
                const key = pair[0];
                const value = pair[1];

                if (key.startsWith('break_start_')) {
                    const index = parseInt(key.split('_')[2]) - 1;
                    if (!breaks[index]) breaks[index] = {};
                    breaks[index]['start_time'] = value;
                } else if (key.startsWith('break_end_')) {
                    const index = parseInt(key.split('_')[2]) - 1;
                    if (!breaks[index]) breaks[index] = {};
                    breaks[index]['end_time'] = value;
                } else {
                    data[key] = value;
                }
            }
            
            data['breaks'] = breaks;
            
            try {
                const response = await fetch(approveForm.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                if (response.status === 422) { 
                    const errorData = await response.json();
                    let errorMessage = '<div class="alert alert-danger" style="padding: 15px; border-radius: 4px;"><ul>';
                    
                    for (const field in errorData.errors) {
                        errorData.errors[field].forEach(msg => {
                            errorMessage += `<li>${msg}</li>`;
                        });
                    }
                    errorMessage += '</ul></div>';
                    statusMessageContainer.innerHTML = errorMessage;
                    submitButton.disabled = false;
                    submitButton.textContent = '承認';
                    return;
                }

                if (!response.ok) {
                    const errorBody = await response.json().catch(() => ({ message: 'サーバーエラーが発生しました。' }));
                    statusMessageContainer.innerHTML = `<div class="alert alert-danger">${errorBody.message || '予期せぬエラー'}</div>`;
                    submitButton.disabled = false;
                    submitButton.textContent = '承認';
                    return;
                }

                const result = await response.json();

                if (result.success) {
                    statusMessageContainer.innerHTML = `<div class="alert alert-success" style="padding: 15px; border-radius: 4px;">修正申請を承認しました。</div>`;
                    submitButton.textContent = '承認済み';
                    submitButton.disabled = true;
                    submitButton.style.backgroundColor = '#ccc'; 
                    
                } else {
                    statusMessageContainer.innerHTML = `<div class="alert alert-danger" style="padding: 15px; border-radius: 4px;">${result.message || '承認に失敗しました。'}</div>`;
                    submitButton.disabled = false;
                    submitButton.textContent = '承認';
                }

            } catch (error) {
                console.error('Fetchリクエスト中にエラーが発生:', error);
                statusMessageContainer.innerHTML = `<div class="alert alert-danger">ネットワークエラーが発生しました。</div>`;
                submitButton.disabled = false;
                submitButton.textContent = '承認';
            }
        });
    });
</script>
@endsection