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

    <form action="/attendance/update-application/{{ $attendance->id }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="name">名前</label>
            <input type="text" id="name" name="name" value="{{ Auth::user()->name }}" readonly>
        </div>

        <div class="form-group">
            <label for="date">日付:</label>
            <input type="date" id="date" name="date" value="{{ $attendance->work_date->format('Y-m-d') }}" readonly>
        </div>

        <div class="form-group">
            <label>出勤・退勤</label>
            <div class="time-pair-group">
                <input type="time" id="start_time" name="start_time" value="{{ $attendance->start_time ? $attendance->start_time->format('H:i') : '' }}">
                <span>〜</span>
                <input type="time" id="end_time" name="end_time" value="{{ $attendance->end_time ? $attendance->end_time->format('H:i') : '' }}">
            </div>
        </div>

        <div class="form-group">
            <label>休憩</label>
            <div class="break-input-group">
                @php
                    $break1_start = isset($attendance->breaks[0]) ? $attendance->breaks[0]->start_time->format('H:i') : '';
                    $break1_end = isset($attendance->breaks[0]) ? $attendance->breaks[0]->end_time->format('H:i') : '';
                @endphp
                <input type="time" name="breaks[0][start_time]" value="{{ $break1_start }}">
                <span>〜</span>
                <input type="time" name="breaks[0][end_time]" value="{{ $break1_end }}">
            </div>
        </div>

        <div class="form-group">
            <label>休憩2</label> 
            <div class="break-input-group">
                @php
                    $break2_start = isset($attendance->breaks[1]) ? $attendance->breaks[1]->start_time->format('H:i') : '';
                    $break2_end = isset($attendance->breaks[1]) ? $attendance->breaks[1]->end_time->format('H:i') : '';
                @endphp
                <input type="time" name="breaks[1][start_time]" value="{{ $break2_start }}">
                <span>〜</span>
                <input type="time" name="breaks[1][end_time]" value="{{ $break2_end }}">
            </div>
        </div>

        <div class="form-group">
            <label for="note">備考</label>
            <textarea id="note" name="note" rows="4">{{ old('note', $attendance->note) }}</textarea>
        </div>

        @if (!$isPendingApplication)
            <div class="submit-button-wrapper"> 
                <button type="submit" class="submit-button">修正</button>
            </div>
        @else
            <p class="pending-message">承認待ちのため修正はできません。</p>
        @endif
    </form>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
    });
</script>
@endsection