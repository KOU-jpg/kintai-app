@extends('layouts.app')

@section('title')
    勤怠詳細画面
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance_detail.css') }}">
@endsection

@section('content')
@include('layouts.components.header')
<main>
    <div class="attendance-container">
        <div class="container_title">
            勤怠詳細
        </div>
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form action="{{ route('attendance.store', $attendance->id) }}" method="POST" novalidate>
            <div class="container_form">
                @csrf
                <div class="form-group">
                    <label>名前</label>
                    <div class="form-contents">
                        <span>{{ $attendance->user->name ?? '' }}</span>
                        <input type="hidden" name="user_name" value="{{ $attendance->user->name ?? '' }}">
                    </div>
                </div>
                <div class="form-group">
                    <label>日付</label>
                    <div class="form-contents">
                        <span>{{ \Carbon\Carbon::parse($attendance->work_date)->format('Y年') }}</span>
                        <span>{{ \Carbon\Carbon::parse($attendance->work_date)->format('n月j日') }}</span>
                        <input type="hidden" name="work_date" value="{{ $attendance->work_date }}">
                    </div>
                </div>
                <div class="form-group">
                    <label>出勤・退勤</label>
                    <div class="form-contents">
                        <input type="time" name="shift_start" value="{{ old('shift_start', $attendance->shift_start ? \Carbon\Carbon::parse($attendance->shift_start)->format('H:i') : '') }}">
                        <span>～</span>
                        <input type="time" name="shift_end" value="{{ old('shift_end', $attendance->shift_end ? \Carbon\Carbon::parse($attendance->shift_end)->format('H:i') : '')  }}">
                    </div>
                </div>
                @foreach($breaktimes as $i => $breaktime)
                    <div class="form-group">
                        <label>休憩{{ $i+1 }}</label>
                        <div class="form-contents">
                            <input type="time" name="breaktimes[{{ $i }}][start_time]"
                                value="{{ old('breaktimes.' . $i . '.start_time', $breaktime->start_time ? \Carbon\Carbon::parse($breaktime->start_time)->format('H:i') : '') }}">
                            <span>～</span>
                            <input type="time" name="breaktimes[{{ $i }}][end_time]"
                                value="{{ old('breaktimes.' . $i . '.end_time', $breaktime->end_time ? \Carbon\Carbon::parse($breaktime->end_time)->format('H:i') : '') }}">
                        </div>
                    </div>
                @endforeach
                {{-- 追加用の空欄（必ず1つ表示） --}}
                @php $nextIndex = count($breaktimes); @endphp
                <div class="form-group">
                    <label>休憩{{ $nextIndex + 1 }}</label>
                    <div class="form-contents">
                        <input type="time" name="breaktimes[{{ $nextIndex }}][start_time]"
                            value="{{ old('breaktimes.' . $nextIndex . '.start_time', '') }}">
                        <span>～</span>
                        <input type="time" name="breaktimes[{{ $nextIndex }}][end_time]"
                            value="{{ old('breaktimes.' . $nextIndex . '.end_time', '') }}">
                    </div>
                </div>
                <div class="form-group">
                    <label>備考</label>
                    <div class="form-contents">
                        <textarea name="note" class="textarea_other">{{ old('note', $attendance->note ?? '') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="container_button">
                <button type="submit" class="submit-btn">修正</button>
            </div>
        </form>
    </div>
</main>
@endsection