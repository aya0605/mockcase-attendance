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
                @forelse ($pendingApplications as $application)
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
                @forelse ($approvedApplications as $application)
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
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.dataset.tab;
            
            // すべてのタブボタンから 'active' クラスを削除
            tabButtons.forEach(btn => btn.classList.remove('active'));
            
            // クリックされたボタンに 'active' クラスを追加
            this.classList.add('active');
            
            // すべてのタブコンテンツから 'active' クラスを削除
            tabContents.forEach(content => content.classList.remove('active'));
            
            // 選択されたタブに対応するコンテンツに 'active' クラスを追加
            document.getElementById(`${targetTab}-applications`).classList.add('active');

            // URLのクエリパラメータを更新
            const newUrl = new URL(window.location.href);
            newUrl.searchParams.set('tab', targetTab);
            window.history.pushState({ path: newUrl.href }, '', newUrl.href);
        });
    });
    
    // ページロード時の初期タブ表示
    const initialTab = new URLSearchParams(window.location.search).get('tab') || 'pending';
    const initialButton = document.querySelector(`.tab-button[data-tab="${initialTab}"]`);
    const initialContent = document.getElementById(`${initialTab}-applications`);
    
    if (initialButton) {
        initialButton.classList.add('active');
    }
    
    if (initialContent) {
        initialContent.classList.add('active');
    }
});
</script>
@endsection