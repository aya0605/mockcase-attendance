@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}">
@endsection

@section('content')
<div class="attendance__content">
    <div class="attendance__header">
        <a href="{{ url('admin/attendances/list?date=' . $prevDay) }}" class="attendance__button">&lt;</a>
        <h2 class="attendance__date">{{ $currentDate }}</h2>
        <a href="{{ url('admin/attendances/list?date=' . $nextDay) }}" class="attendance__button">&gt;</a>
    </div>

    <table class="attendance__table">
        <thead>
            <tr>
                <th>名前</th>
                <th>勤務開始</th>
                <th>勤務終了</th>
                <th>休憩時間</th>
                <th>勤務時間</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendanceData as $attendance)
            <tr>
                <td>{{ $attendance['user_name'] }}</td>
                <td>{{ $attendance['start_time'] }}</td>
                <td>{{ $attendance['end_time'] }}</td>
                <td>{{ $attendance['total_break_time'] }}</td>
                <td>{{ $attendance['total_work_time'] }}</td>
                <td>
                    @if($attendance['attendance_id'])
                        <a href="{{ url('/admin/attendances/' . $attendance['attendance_id']) }}" class="detail-link">詳細</a>
                    @else
                        -
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6">該当日付の勤怠記録はありません。</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
