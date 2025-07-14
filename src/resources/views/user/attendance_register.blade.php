@extends('layouts.app')

@section('title')
    勤怠登録画面
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance_register.css') }}">
@endsection

@section('content')
@include('layouts.components.header')
<main>
    <div class="attendance-container">
        <div class="attendance-card">
                        <div class="status">
                @switch(optional($attendance)->status)
                    @case('before_work')
                        勤務外
                        @break
                    @case('working')
                        勤務中
                        @break
                    @case('on_break')
                        休憩中
                        @break
                    @case('after_work')
                        退勤済
                        @break
                    @default
                        勤務外
                @endswitch
            </div>
            <div class="date" id="now-date">今日</div>
            <div class="time" id="now-time">現在時刻</div>
        </div>
        <div class="attendance-button">
            @switch(optional($attendance)->status)
                @case('before_work')
                    <form action="{{ route('attendance.clockIn') }}" method="POST" style="display:inline;">
                        @csrf
                        <button class="action btn_black">出勤</button>
                    </form>
                    @break

                @case('working')
                    <form action="{{ route('attendance.clockOut') }}" method="POST" style="display:inline;">
                        @csrf
                        <button class="action btn_black">退勤</button>
                    </form>
                    <form action="{{ route('attendance.startBreak') }}" method="POST" style="display:inline;">
                        @csrf
                        <button class="action btn_white">休憩入</button>
                    </form>
                    @break

                @case('on_break')
                    <form action="{{ route('attendance.endBreak') }}" method="POST" style="display:inline;">
                        @csrf
                        <button class="action btn_white">休憩戻</button>
                    </form>
                    @break

                @case('after_work')
                    <p>お疲れさまでした。</p>
                    @break
            @endswitch
        </div>
    </div>
</main>

<script src="{{ asset('js/now_time.js') }}"></script>
<!-- ページを開いたとき、そして1秒ごとに、updateNowTime関数を実行する -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    updateNowTime();
    setInterval(updateNowTime, 1000);
});
</script>
@endsection