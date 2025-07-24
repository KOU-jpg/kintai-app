@extends('layouts.app')

@section('title')
    勤怠詳細画面
@endsection

@section('css')
  <link rel="stylesheet" href="{{ asset('css/attendance_detail.css') }}">
@endsection

@section('content')
@include('layouts.components.headerAdmin')
<main>
    <div class="attendance-container">
        <div class="container_title">
            勤怠詳細
        </div>
        <form action="{{ route('admin.request.update', $attendance->id) }}" method="POST" novalidate>
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
                        <input type="hidden" name="work_date" value="{{ \Carbon\Carbon::parse($attendance->work_date)->format('Y-m-d') }}">
                    </div>
                </div>
                <div class="form-group">
                    <label>出勤・退勤</label>
                    <div class="form-contents">
                        <span>{{\Carbon\Carbon::parse($attendance->shift_start)->format('H:i')}}</span>
                        <span>～</span>
                        <span>{{\Carbon\Carbon::parse($attendance->shift_end)->format('H:i')}}</span>
                    </div>
                </div>
                @foreach($breaktimes as $i => $breaktime)
                    <div class="form-group">
                        <label>休憩{{ $i+1 }}</label>
                        <div class="form-contents">
                            <span>{{\Carbon\Carbon::parse($breaktime->start_time)->format('H:i')}}</span>
                            <span>～</span>
                           <span>{{ \Carbon\Carbon::parse($breaktime->end_time)->format('H:i')}}</span>
                        </div>
                    </div>
                @endforeach
                <div class="form-group">
                    <label>備考</label>
                    <div class="form-contents" >
                        <textarea name="note" class="textarea_other" style="border: none !important; padding: 0 !important;">{{ old('note', $attendance->note ?? '') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="container_button">
            @if ($attendance->request_status === 'pending')
                <button type="submit" class="submit-btn">承認</button>
            @else
                <button type="submit" class="submit-btn-approve">承認済み</button>
            @endif
            </div>
        </form>
    </div>
</main>
@endsection