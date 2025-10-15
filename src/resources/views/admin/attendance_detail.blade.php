@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/detail.css') }}">
@endsection

@section('content')
<div class="detail__content">
    <div class="detail__header">
        <h1 class="content__header--item" style="font-size: 28px; font-weight: bold;">{{ $user->name }}さんの勤怠詳細</h1>
    </div>

    @if (session('success') || $errors->any())
        <div class="form" style="max-width: 100vh; margin: 0 auto 20px;">
            @if (session('success'))
                <div class="alert alert-success" style="padding: 15px; border-radius: 4px;">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger" style="padding: 15px; border-radius: 4px;">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif

    <form class="form" action="/admin/attendances/{{ $attendance->id }}" method="POST">
        @csrf
        @method('PUT') 
        
        <div class="form__content">
            
            {{-- 1. 名前 --}}
            <div class="form__group">
                {{-- label -> form__header --}}
                <p class="form__header">名前</p>
                {{-- input type="text" を直接配置し、form__input--name クラスを適用 --}}
                <div class="form__input-group">
                    <input class="form__input form__input--name" type="text" id="name" name="name" value="{{ $user->name }}" readonly>
                </div>
            </div>

            {{-- 2. 日付 --}}
            <div class="form__group">
                <p class="form__header">日付</p>
                <div class="form__input-group">
                    {{-- type="date" を使わず、staff詳細と同様に readonly の type="text" として扱う --}}
                    <input class="form__input" type="text" id="date" name="date" 
                        value="{{ $attendance->work_date->format('Y年　　　　　　m月d日') }}" readonly>
                </div>
            </div>

            {{-- 3. 出勤・退勤 --}}
            <div class="form__group">
                <p class="form__header">出勤・退勤</p>
                <div class="form__input-group">
                    <input class="form__input" type="time" id="start_time" name="start_time" value="{{ $attendance->start_time ? $attendance->start_time->format('H:i') : '' }}">
                    <p>〜</p>
                    <input class="form__input" type="time" id="end_time" name="end_time" value="{{ $attendance->end_time ? $attendance->end_time->format('H:i') : '' }}">
                </div>
            </div>

            {{-- 4. 休憩 --}}
            <div class="form__group">
                <p class="form__header">休憩</p>
                <div class="form__input-group">
                    @php
                        $break1_start = isset($attendance->breaks[0]) ? $attendance->breaks[0]->start_time->format('H:i') : '';
                        $break1_end = isset($attendance->breaks[0]) ? $attendance->breaks[0]->end_time->format('H:i') : '';
                    @endphp
                    <input class="form__input" type="time" name="breaks[0][start_time]" value="{{ old('breaks.0.start_time', $break1_start) }}">
                    <p>〜</p>
                    <input class="form__input" type="time" name="breaks[0][end_time]" value="{{ old('breaks.0.end_time', $break1_end) }}">
                </div>
            </div>

            {{-- 5. 休憩2 --}}
            <div class="form__group">
                <p class="form__header">休憩2</p>
                <div class="form__input-group">
                    @php
                        $break2_exists = isset($attendance->breaks[1]);
                        $break2_db_start = $break2_exists ? $attendance->breaks[1]->start_time->format('H:i') : '';
                        $break2_db_end = $break2_exists ? $attendance->breaks[1]->end_time->format('H:i') : '';
                        
                        $input_start = old('breaks.1.start_time', $break2_db_start);
                        $input_end = old('breaks.1.end_time', $break2_db_end);

                        $start_type = empty($input_start) ? 'text' : 'time';
                        $end_type = empty($input_end) ? 'text' : 'time';
                    @endphp
                    <input class="form__input" type="{{ $start_type }}" name="breaks[1][start_time]" value="{{ $input_start }}">
                    <p>〜</p>
                    <input class="form__input" type="{{ $end_type }}" name="breaks[1][end_time]" value="{{ $input_end }}">
                </div>
            </div>

            {{-- 6. 備考 --}}
            <div class="form__group">
                <p class="form__header">備考</p>
                <div class="form__input-group">
                    {{-- textarea に form__textarea クラスを適用 --}}
                    <textarea class="form__textarea" id="note" name="note" rows="4">{{ old('note', $attendance->note) }}</textarea>
                </div>
            </div>
        </div>

        <div class="form__button">
            <button type="submit" class="form__button--submit">修正</button>
        </div>
    </form>
</div>
@endsection