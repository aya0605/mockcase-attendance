@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}"> 
@endsection

@section('content')
<div class="attendance-list__content">  
    <div class="content__header">
        <h2 class="content__header--item">勤怠一覧</h2>
    </div>

    <div class="content__menu">
        <a class="previous-month" href="{{ url('/attendance/list?month=' . $prevMonth) }}">前月</a>

        <p class="current-month">{{ $currentMonth }}</p>

        <a class="next-month" href="{{ url('/attendance/list?month=' . $nextMonth) }}">翌月</a>
    </div>

    <table class="table"> 
        <tr class="table__row">
            <th class="table__header"><p class="table__header--item">日付</p></th>
            <th class="table__header"><p class="table__header--item">出勤</p></th>
            <th class="table__header"><p class="table__header--item">退勤</p></th>
            <th class="table__header"><p class="table__header--item">休憩</p></th>
            <th class="table__header"><p class="table__header--item">合計</p></th>
            <th class="table__header"><p class="table__header--item">詳細</p></th>
        </tr>
        
        @forelse ($attendanceData as $data)
        <tr class="table__row">
            <td class="table__description"><p class="table__description--item">{{ $data['work_date'] }}</p></td>
            <td class="table__description"><p class="table__description--item">{{ $data['start_time'] }}</p></td>
            <td class="table__description"><p class="table__description--item">{{ $data['end_time'] }}</p></td>
            <td class="table__description"><p class="table__description--item">{{ $data['total_break_time'] }}</p></td>
            <td class="table__description"><p class="table__description--item">{{ $data['total_work_time'] }}</p></td>
            <td class="table__description">
                <a class="table__item--detail-link" href="/attendance/detail/{{ $data['attendance_id'] }}">詳細</a>
            </td>
        </tr>
        @empty
        <tr class="table__row">
            <td colspan="6" class="table__description">今月の勤怠記録はありません。</td>
        </tr>
        @endforelse
    </table>
</div>
@endsection