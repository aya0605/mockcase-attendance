@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/detail.css') }}">
@endsection

@section('content')
<div class="detail__content">
    <div class="detail__header">
        <h2 class="content__header--item">勤怠詳細</h2>
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

    <form class="form" action="/attendance/update-application/{{ $attendance->id }}" method="POST">
        @csrf

        @if (!$isPendingApplication)
            <div class="form__content">
                
                {{-- 1. 名前 --}}
                <div class="form__group">
                    <p class="form__header">名前</p>
                    <div class="form__input-group">
                        <input class="form__input form__input--name" type="text" name="name" value="{{ Auth::user()->name }}" readonly>
                    </div>
                </div>

                {{-- 2. 日付 --}}
                <div class="form__group">
                    <p class="form__header">日付</p>
                    <div class="form__input-group">
                        <input class="form__input" type="text" name="date" 
                        value="{{ $attendance->work_date->format('Y年　　　　　　m月d日') }}" readonly>
                        {{-- ★FN026-2に基づき、日付は変更不可とするため readonly を追加 --}}
                    </div>
                </div>

                {{-- 3. 出勤・退勤 --}}
                <div class="form__group">
                    <p class="form__header">出勤・退勤</p>
                    <div class="form__input-group">
                        <input class="form__input" type="time" name="start_time" value="{{ $attendance->start_time ? $attendance->start_time->format('H:i') : '' }}">
                        <p>〜</p>
                        <input class="form__input" type="time" name="end_time" value="{{ $attendance->end_time ? $attendance->end_time->format('H:i') : '' }}">
                    </div>
                </div>

                {{-- 4. 休憩１--}}
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

                {{-- 5. 休憩２ --}}
                <div class="form__group">
                    <p class="form__header">休憩２</p>
                    <div class="form__input-group">
                        @php
                            $break2_exists = isset($attendance->breaks[1]);
            
                            // データベースの値
                            $break2_db_start = $break2_exists ? $attendance->breaks[1]->start_time->format('H:i') : '';
                            $break2_db_end = $break2_exists ? $attendance->breaks[1]->end_time->format('H:i') : '';
                            
                            // old()があればそれを優先し、なければデータベースの値、それもなければ空
                            $input_start = old('breaks.1.start_time', $break2_db_start);
                            $input_end = old('breaks.1.end_time', $break2_db_end);

                            // 値が空の場合は type='text' に切り替えてブラウザの00:00表示を防ぐ
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
                        <textarea class="form__textarea" name="note">{{ old('note', $attendance->note) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="form__button">
                <button class="form__button--submit" type="submit">修正</button>
            </div>

        @else
            {{-- 承認待ちあり--}}
            <div class="form__content">
                
                {{-- 名前 --}}
                <div class="form__group">
                    <p class="form__header">名前</p>
                    <div class="form__input-group">
                        <input class="form__input form__input--name readonly" type="text" value="{{ Auth::user()->name }}" readonly>
                    </div>
                </div>

                {{-- 日付 --}}
                <div class="form__group">
                    <p class="form__header">日付</p>
                    <div class="form__input-group">
                        <input class="form__input readonly" type="text" 
                        value="{{ $attendance->work_date->format('Y年　　　　　　m月d日') }}" readonly>
                    </div>
                </div>

                {{-- 出勤・退勤 --}}
                <div class="form__group">
                    <p class="form__header">出勤・退勤</p>
                    <div class="form__input-group">
                        <input class="form__input readonly" type="text" value="{{ $attendance->start_time ? $attendance->start_time->format('H:i') : '' }}" readonly>
                        <p>〜</p>
                        <input class="form__input readonly" type="text" value="{{ $attendance->end_time ? $attendance->end_time->format('H:i') : '' }}" readonly>
                    </div>
                </div>

                {{-- 休憩１ --}}
                <div class="form__group">
                    <p class="form__header">休憩</p>
                    <div class="form__input-group">
                        @php
                            $break1_start_r = isset($attendance->breaks[0]) ? $attendance->breaks[0]->start_time->format('H:i') : '';
                            $break1_end_r = isset($attendance->breaks[0]) ? $attendance->breaks[0]->end_time->format('H:i') : '';
                        @endphp
                        <input class="form__input readonly" type="text" value="{{ $break1_start_r }}" readonly>
                        <p>〜</p>
                        <input class="form__input readonly" type="text" value="{{ $break1_end_r }}" readonly>
                    </div>
                </div>

                {{-- 休憩２  --}}
                <div class="form__group">
                    <p class="form__header">休憩２</p>
                    <div class="form__input-group">
                        @php
                            $break2_start_r = isset($attendance->breaks[1]) ? $attendance->breaks[1]->start_time->format('H:i') : '';
                            $break2_end_r = isset($attendance->breaks[1]) ? $attendance->breaks[1]->end_time->format('H:i') : '';
                            
                            // データがない場合は空文字を適用
                            if (!isset($attendance->breaks[1])) {
                                $break2_start_r = '';
                                $break2_end_r = '';
                            }
                        @endphp
                        <input class="form__input readonly" type="text" value="{{ $break2_start_r }}" readonly>
                        <p>〜</p>
                        <input class="form__input readonly" type="text" value="{{ $break2_end_r }}" readonly>
                    </div>
                </div>

                {{-- 備考 --}}
                <div class="form__group">
                    <p class="form__header">備考</p>
                    <div class="form__input-group">
                        <textarea class="form__textarea readonly" name="note" readonly>{{ $attendance->note }}</textarea>
                    </div>
                </div>
            </div>

            <div class="form__button">
                <p class="readonly-message">承認待ちのため修正できません</p>
            </div>
        @endif
    </form>
</div>
@endsection