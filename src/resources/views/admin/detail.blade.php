@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/detail.css') }}">
@endsection

@section('content')
<div class="attendance-detail-container">
    <h1>勤怠詳細</h1>

    <div id="statusMessage" class="alert-container">
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="approveForm" action="{{ url('admin/applications/' . $application->id . '/approve') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="name">名前</label>
            <input type="text" id="name" value="{{ $application->user->name }}" readonly>
        </div>

        <div class="form-group">
            <label for="date">日付:</label>
            <input type="date" id="date" value="{{ $application->attendance->work_date->format('Y-m-d') }}" readonly>
        </div>

        <div class="form-group">
            <label>出勤・退勤</label>
            <div class="time-pair-group">
                <input type="time" id="start_time" name="start_time" value="{{ $application->applied_start_time ? \Carbon\Carbon::parse($application->applied_start_time)->format('H:i') : '' }}">
                <span>〜</span>
                <input type="time" id="end_time" name="end_time" value="{{ $application->applied_end_time ? \Carbon\Carbon::parse($application->applied_end_time)->format('H:i') : '' }}">
            </div>
        </div>

        <div class="form-group">
            <label>休憩1</label>
            <div class="break-input-group">
                @php
                    $appliedBreaks = json_decode($application->applied_breaks, true);
                    $break1_start = isset($appliedBreaks[0]) ? \Carbon\Carbon::parse($appliedBreaks[0]['start_time'])->format('H:i') : '';
                    $break1_end = isset($appliedBreaks[0]) ? \Carbon\Carbon::parse($appliedBreaks[0]['end_time'])->format('H:i') : '';
                @endphp
                <input type="time" name="break_start_1" value="{{ $break1_start }}">
                <span>〜</span>
                <input type="time" name="break_end_1" value="{{ $break1_end }}">
            </div>
        </div>

        <div class="form-group">
            <label>休憩2</label>
            <div class="break-input-group">
                @php
                    $break2_start = isset($appliedBreaks[1]) ? \Carbon\Carbon::parse($appliedBreaks[1]['start_time'])->format('H:i') : '';
                    $break2_end = isset($appliedBreaks[1]) ? \Carbon\Carbon::parse($appliedBreaks[1]['end_time'])->format('H:i') : '';
                @endphp
                <input type="time" name="break_start_2" value="{{ $break2_start }}">
                <span>〜</span>
                <input type="time" name="break_end_2" value="{{ $break2_end }}">
            </div>
        </div>

        <div class="form-group">
            <label for="note">備考</label>
            <textarea id="note" name="note" rows="4">{{ old('note', $application->note) }}</textarea>
        </div>

        <div class="submit-button-wrapper">
            <button type="submit" class="submit-button">承認</button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOMContentLoaded event fired.');

        const approveForm = document.getElementById('approveForm');
        const submitButton = approveForm.querySelector('button[type="submit"]');
        const statusMessageContainer = document.getElementById('statusMessage');

        if (!approveForm) {
            console.error('エラー: フォームID「approveForm」が見つかりませんでした。');
            return;
        }

        approveForm.addEventListener('submit', async function(event) {
            console.log('フォーム送信イベントが発火しました。デフォルト動作を阻止します。');
            event.preventDefault();

            submitButton.disabled = true;
            submitButton.textContent = '承認中...';
            statusMessageContainer.innerHTML = '';

            const formData = new FormData(approveForm);

            const data = {};
            formData.forEach((value, key) => data[key] = value);

            console.log('送信データ:', data);

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

                console.log('レスポンス受信:', response);

                if (!response.ok) {
                    console.error('HTTPエラー:', response.status, response.statusText);
                    statusMessageContainer.innerHTML = '';
                    submitButton.disabled = false;
                    submitButton.textContent = '承認';
                    return;
                }

                const result = await response.json();
                console.log('JSONデータ解析成功:', result);

                if (result.success) {
                    statusMessageContainer.innerHTML = '';
                    submitButton.textContent = '承認済み';
                    submitButton.disabled = true;
                    submitButton.style.backgroundColor = '#ccc'; 
                } else {
                    statusMessageContainer.innerHTML = '';
                    submitButton.disabled = false;
                    submitButton.textContent = '承認';
                }

            } catch (error) {
                console.error('Fetchリクエスト中にエラーが発生:', error);
                statusMessageContainer.innerHTML = '';
                submitButton.disabled = false;
                submitButton.textContent = '承認';
            }
        });
    });
</script>
@endsection
