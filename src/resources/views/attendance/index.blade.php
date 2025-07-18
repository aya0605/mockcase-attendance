@extends('layouts.app') 

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
<div class="attendance-container">

    <div class="status-display">
        <span id="attendance-status" class="status-text">勤務外</span>
    </div>

    <div class="current-time">
        <p id="current-date"></p> {{-- 日付表示用 --}}
        <p id="current-time"></p> {{-- 時刻表示用 --}}
    </div>

    <div class="punch-buttons">
        <form id="start-work-form" action="/attendance/start-work" method="POST">
            @csrf
            <button id="start-work-button" type="submit" class="button primary">出勤</button>
        </form>

        <form id="end-work-form" action="/attendance/end-work" method="POST" style="display: none;">
        @csrf
        <button id="end-work-button" type="submit" class="button danger">退勤</button>
    </form>

    <form id="start-break-form" action="/attendance/start-break" method="POST" style="display: none;">
        @csrf
        <button id="start-break-button" type="submit" class="button secondary">休憩入</button>
    </form>

    <form id="end-break-form" action="/attendance/end-break" method="POST" style="display: none;">
        @csrf
        <button id="end-break-button" type="submit" class="button secondary">休憩戻</button>
    </form>
    </div>

    <div id="message-area" class="message-area"></div>
</div>
@endsection

@section('scripts')
<script>
    // 日付と時刻を更新する関数
    function updateCurrentDateTime() {
        const now = new Date();

        const year = now.getFullYear();
        const month = (now.getMonth() + 1).toString().padStart(2, '0');
        const date = now.getDate().toString().padStart(2, '0');
        const hours = now.getHours().toString().padStart(2, '0');
        const minutes = now.getMinutes().toString().padStart(2, '0');
        // const seconds = now.getSeconds().toString().padStart(2, '0'); // この行は削除済みでOK

        // 各要素を取得し、存在すれば内容を更新
        const currentDateElement = document.getElementById('current-date');
        if (currentDateElement) {
            currentDateElement.textContent = `${year}年${month}月${date}日`;
        } else {
            console.warn("Element with ID 'current-date' not found.");
        }

        const currentTimeElement = document.getElementById('current-time');
        if (currentTimeElement) {
            // ★ここを修正しました★ 秒（:${seconds}）を削除
            currentTimeElement.textContent = `${hours}:${minutes}`; 
        } else {
            console.warn("Element with ID 'current-time' not found.");
        }
    }

    // ページが読み込まれてから初期化を実行
    document.addEventListener('DOMContentLoaded', function() {
        updateCurrentDateTime(); // 初回表示
        setInterval(updateCurrentDateTime, 1000); // 1秒ごとに更新
        const initialStatus = "{{ $status ?? '勤務外' }}"; 
        updateButtonsAndStatus(initialStatus); // 初期ステータスでボタン表示を更新


        const startWorkForm = document.getElementById('start-work-form');
        if (startWorkForm) {
            startWorkForm.addEventListener('submit', function(e) {
                e.preventDefault();
                punchAction('/attendance/start-work', '出勤中');
            });
        }

        const endWorkForm = document.getElementById('end-work-form');
        if (endWorkForm) {
            endWorkForm.addEventListener('submit', function(e) {
                e.preventDefault();
                punchAction('/attendance/end-work', 'お疲れ様でした。');
            });
        }

        const startBreakForm = document.getElementById('start-break-form');
        if (startBreakForm) {
            startBreakForm.addEventListener('submit', function(e) {
                e.preventDefault();
                punchAction('/attendance/start-break');
            });
        }

        const endBreakForm = document.getElementById('end-break-form');
        if (endBreakForm) {
            endBreakForm.addEventListener('submit', function(e) {
                e.preventDefault();
                punchAction('/attendance/end-break');
            });
        }
    });

    // ボタンの表示/非表示を更新 (変更なし)
    function updateButtonsAndStatus(status) {
        const statusElement = document.getElementById('attendance-status');
        if (statusElement) {
            statusElement.textContent = status;
        }

        // 各ボタンのフォームとボタン要素を取得
        const startWorkForm = document.getElementById('start-work-form');
        const endWorkForm = document.getElementById('end-work-form');
        const startBreakForm = document.getElementById('start-break-form');
        const endBreakForm = document.getElementById('end-break-form');

        // 初期状態では全て非表示にしておき、必要なものだけ表示する
        if (startWorkForm) startWorkForm.style.display = 'none';
        if (endWorkForm) endWorkForm.style.display = 'none';
        if (startBreakForm) startBreakForm.style.display = 'none';
        if (endBreakForm) endBreakForm.style.display = 'none';

        // 勤務状態に応じたボタンの表示ロジック
        if (status === '勤務外') {
            if (startWorkForm) startWorkForm.style.display = 'block'; // 出勤ボタンのみ表示
        } else if (status === '出勤中') {
            if (endWorkForm) endWorkForm.style.display = 'block';     // 退勤ボタンを表示
            if (startBreakForm) startBreakForm.style.display = 'block'; // 休憩開始ボタンを表示
        } else if (status === '休憩中') {
            if (endBreakForm) endBreakForm.style.display = 'block';   // 休憩終了ボタンのみ表示
        } else if (status === '退勤済') {
           
        }
        
    }

    // Ajax 
    async function punchAction(url, successMessage) {
        try {
            const form = document.querySelector(`form[action="${url}"]`); // ★URLからフォームを特定するように変更★
        if (!form) throw new Error(`Form for URL ${url} not found.`);

        const formData = new FormData(form);

        const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
        if (!csrfTokenMeta) throw new Error("CSRF token meta tag not found.");
        const token = csrfTokenMeta.getAttribute('content');

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token
            },
            body: formData
        });

        const data = await response.json();
        const messageArea = document.getElementById('message-area');

        if (response.ok) {
            if (messageArea) {
                messageArea.textContent = successMessage; // 引数で渡されたメッセージを表示
                messageArea.style.color = 'green';
            }
            // サーバーから返されるnew_attendance_statusでUIを更新
            if (data.new_attendance_status) {
                updateButtonsAndStatus(data.new_attendance_status);
            }
        } else {
            if (messageArea) {
                // サーバーからのエラーメッセージを優先して表示
                messageArea.textContent = data.message || '不明なエラーが発生しました。';
                messageArea.style.color = 'red';
            }
        }
    } catch (error) {
        console.error('打刻エラー:', error);
        const messageArea = document.getElementById('message-area');
        if (messageArea) {
            messageArea.textContent = 'エラーが発生しました。コンソールを確認してください。';
            messageArea.style.color = 'red';
        }
    }
}
</script>
@endsection