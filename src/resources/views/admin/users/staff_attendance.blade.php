@extends('layouts.app')

@section('title', 'スタッフ別月次勤怠一覧')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/staff_attendance.css') }}">
@endsection

@section('content')
    <div class="attendance__content">
        <!-- ヘッダー -->
        <header class="attendance__header">
            <p class="attendance__user-info">{{ $staff->name }} さんの勤怠</p>
        </header>

        <!-- 日付ナビゲーション -->
        <div class="attendance__date-navigation">
            <a href="/admin/users/{{ $staff->id }}/attendances?date={{ $prevMonth->format('Y-m') }}" class="attendance__button">&lt; 前月</a>
            <p class="attendance__date-text">{{ $currentMonth->format('Y年m月') }}</p>
            <a href="/admin/users/{{ $staff->id }}/attendances?date={{ $nextMonth->format('Y-m') }}" class="attendance__button">翌月 &gt;</a>
        </div>

        <!-- 勤怠情報テーブル -->
        <div class="attendance__table-container">
            <table class="attendance__table">
                <thead>
                    <tr>
                        <th>日付</th>
                        <th>出勤</th>
                        <th>退勤</th>
                        <th>休憩</th>
                        <th>合計</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($attendanceList as $attendance)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($attendance['work_date'])->format('m/d') }}</td>
                            <td>{{ $attendance['start_time'] }}</td>
                            <td>{{ $attendance['end_time'] }}</td>
                            <td>{{ $attendance['total_break_time'] }}</td>
                            <td>{{ $attendance['total_work_time'] }}</td>
                            <td>
                                <a href="/admin/attendance/detail/{{ $attendance['attendance_id'] }}" class="detail-link">詳細</a>
                            </td>
                        </tr>
                    @endforeach
                    {{-- 勤怠情報がない日の行を生成 --}}
                    @foreach($emptyDays as $emptyDate)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($emptyDate)->format('m/d') }}</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- CSV出力ボタン -->
        <div class="button-group">
            <a href="/admin/users/{{ $staff->id }}/attendances/csv?date={{ $currentMonth->format('Y-m') }}" class="csv-export__button">CSV出力</a>
        </div>
    </div>
@endsection
