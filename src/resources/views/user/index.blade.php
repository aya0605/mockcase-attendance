@extends('layouts.app')

@section('css')
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/application_list.css') }}">
@endsection

@section('content')
<div class="container">
    <h1 class="page-title">申請一覧</h1>

    <div id="status-message">
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif
    </div>

    <div class="tab-buttons">
        <button class="tab-button @if($currentTab === 'pending') active @endif" data-tab="pending">承認待ち</button>
        <button class="tab-button @if($currentTab === 'approved') active @endif" data-tab="approved">承認済み</button>
    </div>

   {{-- 承認待ちタブの内容 --}}
<div id="pending-applications" class="tab-content @if($currentTab === 'pending') active @endif">
    <table class="application-table">
        <thead>
            <tr>
                <th>状態</th>
                <th>対象日時</th>
                <th>申請理由</th>
                <th>申請日時</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            {{-- 承認待ちの申請をフィルタリングして表示 --}}
            @forelse ($applications->where('status', 'pending') as $application)
            <tr>
                <td><span class="status-pending">承認待ち</span></td>
                <td>{{ \Carbon\Carbon::parse($application->attendance->work_date)->format('Y-m-d') }}</td>
                <td>{{ $application->note }}</td>
                <td>{{ \Carbon\Carbon::parse($application->created_at)->format('Y-m-d H:i') }}</td>
                <td>
                    <a href="/user/applications/{{ $application->id }}" class="action-button detail-button">詳細</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="no-applications">承認待ちの申請はありません。</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- 承認済みタブの内容 --}}
<div id="approved-applications" class="tab-content @if($currentTab === 'approved') active @endif">
    <table class="application-table">
        <thead>
            <tr>
                <th>状態</th>
                <th>対象日時</th>
                <th>申請理由</th>
                <th>申請日時</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            {{-- 承認済みと却下の申請をフィルタリングして表示 --}}
            @forelse ($applications->whereIn('status', ['approved', 'rejected']) as $application)
            <tr>
                <td>
                    @if ($application->status === 'approved')
                        <span class="status-approved">承認済み</span>
                    @elseif ($application->status === 'rejected')
                        <span class="status-rejected">却下</span>
                    @endif
                </td>
                <td>{{ \Carbon\Carbon::parse($application->attendance->work_date)->format('Y-m-d') }}</td>
                <td>{{ $application->note }}</td>
                <td>{{ \Carbon\Carbon::parse($application->created_at)->format('Y-m-d H:i') }}</td>
                <td>
                    <a href="/user/applications/{{ $application->id }}" class="action-button detail-button">詳細</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="no-applications">承認済みの申請はありません。</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    const statusMessageContainer = document.getElementById('status-message');

    // タブ切り替え機能
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.dataset.tab;
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            this.classList.add('active');
            document.getElementById(`${targetTab}-applications`).classList.add('active');
        });
    });

    // 処理結果メッセージ表示
    function showStatusMessage(type, message) {
        let messageDiv = document.createElement('div');
        messageDiv.classList.add('alert', `alert-${type}`);
        messageDiv.textContent = message;

        // 既存のメッセージをクリア
        statusMessageContainer.innerHTML = '';
        statusMessageContainer.appendChild(messageDiv);
        
        // 3秒後にメッセージを消す
        setTimeout(() => {
            messageDiv.style.opacity = '0';
            messageDiv.style.transition = 'opacity 0.5s ease-out';
            setTimeout(() => {
                messageDiv.remove();
            }, 500);
        }, 3000);
    }
});
</script>
@endsection
