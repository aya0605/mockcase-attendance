@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/application_list.css') }}">
@endsection

@section('content')
<div class="container">
    <h1 class="page-title">申請一覧</h1>

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
                <th>名前</th>
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
                <td>{{ $application->user->name }}</td>
                <td>{{ \Carbon\Carbon::parse($application->attendance->work_date)->format('Y-m-d') }}</td>
                <td>{{ $application->note }}</td>
                <td>{{ \Carbon\Carbon::parse($application->created_at)->format('Y-m-d H:i') }}</td>
                <td>
                    <a href="/attendance/detail/{{ $application->attendance->id }}" class="action-button detail-button">詳細</a>
                    {{-- 管理者向け承認/却下ボタン（管理者のみ表示） --}}
                    @if (Auth::user()->role === 'admin')
                        <form action="/applications/{{ $application->id }}/approve" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="action-button approve-button">承認</button>
                        </form>
                        <form action="/applications/{{ $application->id }}/reject" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="action-button reject-button">却下</button>
                        </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="no-applications">承認待ちの申請はありません。</td>
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
                <th>名前</th>
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
                <td>{{ $application->user->name }}</td>
                <td>{{ \Carbon\Carbon::parse($application->attendance->work_date)->format('Y-m-d') }}</td>
                <td>{{ $application->note }}</td>
                <td>{{ \Carbon\Carbon::parse($application->created_at)->format('Y-m-d H:i') }}</td>
                <td>
                    <a href="/attendance/detail/{{ $application->attendance->id }}" class="action-button detail-button">詳細</a>
                </td>
            </tr>
            @empty
            
            @endforelse
        </tbody>
    </table>
</div>