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
            勤怠詳細(修正中)
        </div>
        <div class="container_form">
            @csrf
            <div class="form-group">
                <label>名前</label>
                <div class="form-contents">
                    <span>{{ $requested_data['user_name'] ?? ($attendance->user->name ?? '') }}</span>
                </div>
            </div>
            <div class="form-group">
                <label>日付</label>
                <div class="form-contents">
                        <span>{{ \Carbon\Carbon::parse($requested_data['work_date'] ?? $attendance->work_date)->format('Y年') }}</span>
                        <span>{{ \Carbon\Carbon::parse($requested_data['work_date'] ?? $attendance->work_date)->format('n月j日') }}</span>
                </div>
            </div>
            <div class="form-group">
                <label>出勤・退勤</label>
                <div class="form-contents">
                    <span>{{ $requested_data['shift_start'] ?? '' }}</span>
                    <span>～</span>
                    <span>{{ $requested_data['shift_end'] ?? '' }}</span>
                </div>
            </div>
            @if(isset($requested_data['breaktimes']))
                @foreach($requested_data['breaktimes'] as $i => $breaktime)
                    <div class="form-group">
                        <label>休憩{{ $i+1 }}</label>
                        <div class="form-contents">
                            <span>{{ $breaktime['start_time'] ?? '' }}</span>
                            <span>～</span>
                            <span>{{ $breaktime['end_time'] ?? '' }}</span>
                        </div>
                    </div>
                @endforeach
            @endif
            <div class="form-group">
                <label>備考</label>
                <div class="form-contents">
                    <span>{{ $requested_data['note'] ?? '' }}</span>
                </div>
            </div>
        </div>
        <div class="container_button">
            <span class="span_padding">*承認中のため修正はできません</span>
        </div>
    </div>
</main>
@endsection