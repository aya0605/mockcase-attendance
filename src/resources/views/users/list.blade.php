@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}">
@endsection

@section('content')
<div class="attendance__content">
    <h1 class="attendance__date-text">{{ $currentMonth }}の勤怠</h1>

    <div class="attendance__header"> 
        <a href="{{ url('/attendance/list?month=' . $prevMonth) }}" class="attendance__button">← 前月</a> 
        <h2 class="attendance__date">{{ $currentMonth }}</h2> 
        <a href="{{ url('/attendance/list?month=' . $nextMonth) }}" class="attendance__button">翌月 →</a> 
    </div>

    <table class="attendance__table"> 
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
