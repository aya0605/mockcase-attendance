@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/detail.css') }}">
@endsection

@section('content')
<div class="attendance-detail-container">
    <h1>勤怠詳細</h1>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(isset($application))
        <div class="form-group">
            <label for="name">名前</label>
            <input type="text" id="name" value="{{ $application->user->name }}" readonly>
        </div>

        <div class="form-group">
            <label for="date">日付</label>
            <input type="date" id="date" value="{{ $application->attendance->work_date->format('Y-m-d') }}" readonly>
        </div>

        <div class="form-group">
            <label>出勤・退勤</label>
            <div class="time-pair-group">
                <input type="time" value="{{ $application->applied_start_time }}" readonly>
                <span>〜</span>
                <input type="time" value="{{ $application->applied_end_time }}" readonly>
            </div>
        </div>

        @php
            $breaks = json_decode($application->applied_breaks, true);
        @endphp
        @if(!empty($breaks))
        <div class="form-group">
            <div class="break-input-group-vertical">
                @foreach($breaks as $index => $break)
                @if($index < 2)
                    <div class="break-time-pair">
                        @if($index === 0)
                            <label for="break_start_{{ $index + 1 }}">休憩</label>
                        @else
                            <label for="break_start_{{ $index + 1 }}">休憩{{ $index + 1 }}</label>
                        @endif
                        <div class="break-time-container">
                            <input type="time" id="break_start_{{ $index + 1 }}" value="{{ $break['start_time'] }}" readonly>
                            <span>〜</span>
                            <input type="time" value="{{ $break['end_time'] }}" readonly>
                        </div>
                    </div>
                @endif
                @endforeach
            </div>
        </div>
        @endif

        <div class="form-group">
            <label for="applied_note">備考</label>
            <textarea id="applied_note" rows="4" readonly>{{ $application->note }}</textarea>
        </div>

        @if($application->status === 'pending')
            <div class="submit-button-wrapper">
                <button id="approve-button" data-id="{{ $application->id }}" class="submit-button approve-button">
                    承認
                </button>
            </div>
        @else
            <div class="submit-button-wrapper">
                <button class="submit-button approved-button" disabled>
                    承認済み
                </button>
            </div>
        @endif
    @else
        <p class="pending-message">このIDに対応する修正申請はありません。</p>
    @endif
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const approveButton = document.getElementById('approve-button');

        // 承認ボタンのクリックイベント
        if (approveButton) {
            approveButton.addEventListener('click', function() {
                if (confirm('この申請を承認してもよろしいですか？')) {
                    const applicationId = this.dataset.id;
                    const url = `/admin/applications/${applicationId}/approve`;

                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({})
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.text().then(text => {
                                console.error('Server returned non-JSON response:', text);
                                throw new Error('サーバーエラーが発生しました。詳細はコンソールを確認してください。');
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        alert(data.message);
                        const buttonWrapper = document.querySelector('.submit-button-wrapper');
                        buttonWrapper.innerHTML = `
                            <button class="submit-button approved-button" disabled>
                                承認済み
                            </button>
                        `;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('エラーが発生しました: ' + error.message);
                    });
                }
            });
        }
    });
</script>
@endsection