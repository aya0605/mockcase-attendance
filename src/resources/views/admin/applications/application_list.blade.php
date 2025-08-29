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
                <th>名前</th>
                <th>対象日時</th>
                <th>申請理由</th>
                <th>申請日時</th>
                <th>詳細</th>
                @if (Auth::user()->role === 'admin')
                    <th>操作</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($pendingApplications as $application)
            <tr id="application-{{ $application->id }}">
                <td><span class="status-pending">承認待ち</span></td>
                <td>{{ $application->user->name }}</td>
                <td>{{ \Carbon\Carbon::parse($application->attendance->work_date)->format('Y-m-d') }}</td>
                <td>{{ $application->note }}</td>
                <td>{{ \Carbon\Carbon::parse($application->created_at)->format('Y-m-d H:i') }}</td>
                <td>
                    <a href="/admin/applications/{{ $application->id }}" class="action-button detail-button">詳細</a>
                </td>
                @if (Auth::user()->role === 'admin')
                <td>
                    <button type="button" class="action-button approve-button" data-id="{{ $application->id }}">承認</button>
                    <button type="button" class="action-button reject-button" data-id="{{ $application->id }}">却下</button>
                </td>
                @endif
            </tr>
            @empty
            <tr>
                <td colspan="{{ Auth::user()->role === 'admin' ? '7' : '6' }}" class="no-applications">承認待ちの申請はありません。</td>
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
                <th>名前</th>
                <th>対象日時</th>
                <th>申請理由</th>
                <th>申請日時</th>
                <th>詳細</th>
                @if (Auth::user()->role === 'admin')
                    <th>操作</th>
                @endif
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
                    {{-- 管理者用の詳細ページURLに修正 --}}
                    <a href="/admin/applications/{{ $application->id }}" class="action-button detail-button">詳細</a>
                </td>
                @if (Auth::user()->role === 'admin')
                <td>
                </td>
                @endif
            </tr>
            @empty
            <tr>
                <td colspan="{{ Auth::user()->role === 'admin' ? '7' : '6' }}" class="no-applications">承認済みの申請はありません。</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if (Auth::user()->role === 'admin')
<div id="reject-modal" class="modal">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <h2>却下理由の入力</h2>
        <form id="reject-form">
            <input type="hidden" name="application_id" id="modal-application-id">
            <textarea id="reject-reason" name="reject_reason" placeholder="却下理由を記入してください..." required></textarea>
            <button type="submit" class="modal-submit-button">送信</button>
        </form>
    </div>
</div>
@endif
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    const statusMessageContainer = document.getElementById('status-message');

    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.dataset.tab;
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            this.classList.add('active');
            document.getElementById(`${targetTab}-applications`).classList.add('active');
        });
    });

    function showStatusMessage(type, message) {
        let messageDiv = document.createElement('div');
        messageDiv.classList.add('alert', `alert-${type}`);
        messageDiv.textContent = message;

        statusMessageContainer.innerHTML = '';
        statusMessageContainer.appendChild(messageDiv);
        
        setTimeout(() => {
            messageDiv.style.opacity = '0';
            messageDiv.style.transition = 'opacity 0.5s ease-out';
            setTimeout(() => {
                messageDiv.remove();
            }, 500);
        }, 3000);
    }
    
    function moveApplicationToApproved(row, applicationData) {
        const approvedTableBody = document.querySelector('#approved-applications tbody');
        const newRow = document.createElement('tr');
        
        const statusSpan = applicationData.status === 'approved' 
                           ? '<span class="status-approved">承認済み</span>' 
                           : '<span class="status-rejected">却下</span>';

        newRow.innerHTML = `
            <td>${statusSpan}</td>
            <td>${applicationData.user.name}</td>
            <td>${applicationData.attendance.work_date}</td>
            <td>${applicationData.note}</td>
            <td>${applicationData.updated_at}</td>
            <td>
                <a href="/admin/applications/${applicationData.id}" class="action-button detail-button">詳細</a>
            </td>
            @if (Auth::user()->role === 'admin')
            <td></td>
            @endif
        `;

        approvedTableBody.prepend(newRow); 
        row.remove();

        const approvedTab = document.querySelector('.tab-button[data-tab="approved"]');
        if (!approvedTab.classList.contains('active')) {
            approvedTab.click();
        }
    }

    @if (Auth::user()->role === 'admin')
    const rejectModal = document.getElementById('reject-modal');
    const rejectForm = document.getElementById('reject-form');
    const modalApplicationId = document.getElementById('modal-application-id');
    const closeButton = document.querySelector('.close-button');
    const csrfToken = document.head.querySelector('meta[name="csrf-token"]').content;

    document.querySelectorAll('.approve-button').forEach(button => {
        button.addEventListener('click', function() {
            const applicationId = this.dataset.id;
            const url = `/admin/applications/${applicationId}/approve`;
            const row = document.getElementById(`application-${applicationId}`);

            if (confirm('この申請を承認してもよろしいですか？')) {
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showStatusMessage('success', data.message);
                        if (row) {
                            moveApplicationToApproved(row, data.application);
                        }
                    } else {
                        showStatusMessage('danger', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showStatusMessage('danger', '承認処理中にエラーが発生しました。');
                });
            }
        });
    });

    document.querySelectorAll('.reject-button').forEach(button => {
        button.addEventListener('click', function() {
            const applicationId = this.dataset.id;
            modalApplicationId.value = applicationId;
            rejectModal.style.display = 'block';
        });
    });

    closeButton.addEventListener('click', function() {
        rejectModal.style.display = 'none';
        rejectForm.reset();
    });

    window.addEventListener('click', function(event) {
        if (event.target === rejectModal) {
            rejectModal.style.display = 'none';
            rejectForm.reset();
        }
    });

    rejectForm.addEventListener('submit', function(event) {
        event.preventDefault();

        const applicationId = modalApplicationId.value;
        const rejectReason = document.getElementById('reject-reason').value;
        const url = `/admin/applications/${applicationId}/reject`;
        const row = document.getElementById(`application-${applicationId}`);

        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ reject_reason: rejectReason })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showStatusMessage('success', data.message);
                if (row) {
                    moveApplicationToApproved(row, data.application);
                }
            } else {
                showStatusMessage('danger', data.message);
            }
            rejectModal.style.display = 'none';
            rejectForm.reset();
        })
        .catch(error => {
            console.error('Error:', error);
            showStatusMessage('danger', '却下処理中にエラーが発生しました。');
            rejectModal.style.display = 'none';
            rejectForm.reset();
        });
    });
    @endif
});
</script>
@endsection
