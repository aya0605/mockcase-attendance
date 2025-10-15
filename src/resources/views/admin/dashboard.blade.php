@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}">
@endsection

@section('content')
<div class="attendance-list__content">
    <div class="content__header">
        <h1 class="content__header--item" style="font-size: 28px; font-weight: bold;">{{ Carbon\Carbon::parse($currentDate)->format('Y年m月d日') }}の勤怠</h1>
    </div>

    <div class="content__menu">
        {{-- ←前日 --}}
        <a href="{{ url('/admin/dashboard?date=' . $prevDay) }}" class="previous-month">
            前日
        </a>
        
        {{-- 現在の日付 --}}
        <p class="current-month">
            {{ $currentDate }}
        </p>
        
        {{-- 翌日→ --}}
        <a href="{{ url('/admin/dashboard?date=' . $nextDay) }}" class="next-month">
            翌日
        </a>
    </div>

    <table class="table">
        <thead>
            <tr class="table__row">
                <th class="table__header"><p class="table__header--item">名前</p></th>
                <th class="table__header"><p class="table__header--item">出勤</p></th>
                <th class="table__header"><p class="table__header--item">退勤</p></th>
                <th class="table__header"><p class="table__header--item">休憩</p></th>
                <th class="table__header"><p class="table__header--item">合計</p></th>
                <th class="table__header"><p class="table__header--item">詳細</p></th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendanceData as $attendance)
            <tr class="table__row">
                <td class="table__description"><p class="table__description--item">{{ $attendance['user_name'] }}</p></td>
                <td class="table__description"><p class="table__description--item">{{ $attendance['start_time'] }}</p></td>
                <td class="table__description"><p class="table__description--item">{{ $attendance['end_time'] }}</p></td>
                <td class="table__description"><p class="table__description--item">{{ $attendance['total_break_time'] }}</p></td>
                <td class="table__description"><p class="table__description--item">{{ $attendance['total_work_time'] }}</p></td>
                <td class="table__description">
                    
                    @if($attendance['attendance_id'])
                        <a href="{{ url('/admin/attendances/' . $attendance['attendance_id']) }}" class="table__item--detail-link">詳細</a>
                    @else
                        <p class="table__description--item">-</p>
                    @endif
                </td>
            </tr>
            @empty
            <tr class="table__row">
                <td colspan="6" class="table__description"><p class="table__description--item">該当日付の勤怠記録はありません。</p></td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection