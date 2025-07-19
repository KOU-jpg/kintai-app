
@extends('layouts.app')

@section('title')
    スタッフ別勤怠画面
@endsection

@section('css')
  <link rel="stylesheet" href="{{ asset('css/attendance_index.css') }}">
@endsection

@section('content')
@include('layouts.components.headerAdmin')
<main>
  <div class="attendance-container">
      @csrf
      <div class="container_title">{{ $user->name }}さんの勤怠</div>
      <div class="container_header">
          <a href="?month={{ $prevMonth }}" class="header-prev">← 前月</a>
          <span class="header-month">{{ $displayMonth }}</span>
          <a href="?month={{ $nextMonth }}" class="header-next">翌月 →</a>
      </div>
      <div class="container_table">
          <table class="attendance-table">
              <thead>
                  <tr>
                      <th>日付</th>
                      <th>出勤</th>
                      <th>退勤</th>
                      <th>休憩</th>
                      <th>合計</th>
                      <th>詳細</th>
                  </tr>
              </thead>
              <tbody>
                  @foreach($attendances as $attendance)
                  <tr>
                      <td>{{ \Carbon\Carbon::parse($attendance['date'])->format('m/d') }}({{ $attendance['weekday'] }})</td>
                      <td>{{ $attendance['start_time'] }}</td>
                      <td>{{ $attendance['end_time'] }}</td>
                      <td>{{ $attendance['break_time'] }}</td>
                      <td>{{ $attendance['work_time'] }}</td>
                      <td>
                          <a href="{{ $attendance['detail_url'] }}" class="detail-link">詳細</a>
                      </td>
                  </tr>
                  @endforeach
              </tbody>
          </table>
      </div>
      <div class="container_button">
        <form method="POST" action="{{ route('attendance.exportCsv') }}">
            @csrf
            <input type="hidden" name="attendanceData" value='@json($attendances)'>
            <button type="submit" class="submit-btn">CSV出力</button>
        </form>
      </div>
  </div>
</main>
@endsection