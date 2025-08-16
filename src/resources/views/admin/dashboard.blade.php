@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}">
@endsection

@section('content')
<div class="attendance__content">
    <h1 class="attendance__date-text">{{ Carbon\Carbon::parse($currentDate)->format('Y年m月d日') }}の勤怠</h1>

    <div class="attendance__header">
        {{-- 前日へのリンク --}}
        <a href="{{ url('/admin/dashboard?date=' . $prevDay) }}" class="attendance__button">←前日</a>
        {{-- 現在の日付表示 --}}
        <h2 class="attendance__date">{{ $currentDate }}</h2>
        {{-- 翌日へのリンク --}}
        <a href="{{ url('/admin/dashboard?date=' . $nextDay) }}" class="attendance__button">翌日→</a>
    </div>

    <table class="attendance__table">
        <thead>
            <tr>
                <th>名前</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
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
                        <a href="{{ url('/admin/attendance/detail/' . $attendance['attendance_id']) }}" class="detail-link">詳細</a>
                    @else
                        - {{-- 勤怠IDがない場合はハイフン表示 --}}
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
