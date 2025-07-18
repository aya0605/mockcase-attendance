@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}">
@endsection

@section('content')
<div class="attendance-list-container">
    <h1 class="attendance-list-header">勤怠一覧<h1>

    <div class="month-navigation">
        <a href="{{ url('/attendance/list?month=' . $prevMonth) }}" class="nav-button">← 前月</a>
        <div class="date-display">
            <span>{{ $currentMonth }}</span>
        </div>
        <a href="{{ url('/attendance/list?month=' . $nextMonth) }}" class="nav-button">翌月 →</a>
    </div>

    <table class="attendance-table">
        <thead>
            <tr>
                <th>日付</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩時間</th>
                <th>勤務時間</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($attendanceData as $data)
            <tr>
                <td>{{ $data['work_date'] }}</td>
                <td>{{ $data['start_time'] }}</td>
                <td>{{ $data['end_time'] }}</td>
                <td>{{ $data['total_break_time'] }}</td>
                <td>{{ $data['total_work_time'] }}</td>
                <td>
                    {{-- 勤怠詳細画面は後で実装するため、今は仮のリンクまたは空でOK --}}
                    <a href="/attendance/detail/{{ $data['attendance_id'] }}" class="detail-link">詳細</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6">今月の勤怠記録はありません。</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection