@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/detail.css') }}">
@endsection

@section('content')
<div class="attendance-detail-container">
    <h1>勤怠詳細</h1>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

        <form action="{{ url('admin/attendance/update/' . $attendanceData->id) }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="name">名前</label>
            <input type="text" id="name" name="name" value="{{ $attendanceData->user->name }}" readonly>
        </div>

        <div class="form-group">
            <label for="date">日付:</label>
            <input type="date" id="date" name="date" value="{{ $attendanceData->work_date->format('Y-m-d') }}" readonly>
        </div>

        <div class="form-group">
            <label>出勤・退勤</label>
            <div class="time-pair-group">
                <input type="time" id="start_time" name="start_time" value="{{ $attendanceData->start_time ? $attendanceData->start_time->format('H:i') : '' }}">
                <span>〜</span>
                <input type="time" id="end_time" name="end_time" value="{{ $attendanceData->end_time ? $attendanceData->end_time->format('H:i') : '' }}">
            </div>
        </div>

        <div class="form-group">
            <label>休憩1</label>
            <div class="break-input-group">
                @php
                    $break1_start = isset($attendanceData->breaks[0]) ? $attendanceData->breaks[0]->start_time->format('H:i') : '';
                    $break1_end = isset($attendanceData->breaks[0]) ? $attendanceData->breaks[0]->end_time->format('H:i') : '';
                @endphp
                <input type="time" name="break_start_1" value="{{ $break1_start }}">
                <span>〜</span>
                <input type="time" name="break_end_1" value="{{ $break1_end }}">
            </div>
        </div>

        <div class="form-group">
            <label>休憩2</label>
            <div class="break-input-group">
                @php
                    $break2_start = isset($attendanceData->breaks[1]) ? $attendanceData->breaks[1]->start_time->format('H:i') : '';
                    $break2_end = isset($attendanceData->breaks[1]) ? $attendanceData->breaks[1]->end_time->format('H:i') : '';
                @endphp
                <input type="time" name="break_start_2" value="{{ $break2_start }}">
                <span>〜</span>
                <input type="time" name="break_end_2" value="{{ $break2_end }}">
            </div>
        </div>

        <div class="form-group">
            <label for="note">備考</label>
            <textarea id="note" name="note" rows="4">{{ old('note', $attendanceData->note) }}</textarea>
        </div>

        <div class="submit-button-wrapper">
            <button type="submit" class="submit-button">承認</button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
    });
</script>
@endsection