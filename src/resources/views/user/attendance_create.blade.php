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
            勤怠新規作成（一般ユーザー）
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
        <form method="POST" action="{{ route('attendance.store') }}" novalidate>
            @csrf
            <input type="hidden" name="user_id" value="{{ $user->id }}">
            <input type="hidden" name="work_date" value="{{ $date }}">

            <div class="container_form">
                <div class="form-group">
                    <label>名前</label>
                    <div class="form-contents">
                        <span>{{ $user->name }}</span>
                        <input type="hidden" name="user_name" value="{{ $user->name }}">
                    </div>
                </div>

                <div class="form-group">
                    <label>日付</label>
                    <div class="form-contents">
                        <span>{{ \Carbon\Carbon::parse($date)->format('Y年') }}</span>
                        <span>{{ \Carbon\Carbon::parse($date)->format('n月j日') }}</span>
                    </div>
                </div>

                <div class="form-group">
                    <label>出勤・退勤</label>
                    <div class="form-contents">
                        <input type="time" name="shift_start" value="{{ old('shift_start') }}">
                        <span>～</span>
                        <input type="time" name="shift_end" value="{{ old('shift_end') }}">
                    </div>
                </div>

                <div class="form-group">
                    <label>休憩</label>
                    <div class="form-contents">
                        <input type="time" name="breaktimes[0][start_time]" value="{{ old('breaktimes.0.start_time') }}">
                        <span>～</span>
                        <input type="time" name="breaktimes[0][end_time]" value="{{ old('breaktimes.0.end_time') }}">
                    </div>
                </div>

                <div class="form-group">
                    <label>備考</label>
                    <div class="form-contents">
                        <textarea name="note" class="textarea_other">{{ old('note') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="container_button">
                <button type="submit" class="submit-btn">登録</button>
            </div>
        </form>
    </div>
</main>
@endsection