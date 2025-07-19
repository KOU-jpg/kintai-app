@extends('layouts.app')

@section('title')
    勤怠一覧画面（管理者）
@endsection

@section('css')
  <link rel="stylesheet" href="{{ asset('css/attendance_index.css') }}">
@endsection

@section('content')
@include('layouts.components.headerAdmin')
<main>
    <div class="attendance-container">
        <div class="container_title">{{ $titleDate }}の勤怠</div>
        <div class="container_header">
            <a href="?date={{ $prevDate }}" class="header-prev">← 前日</a>
            <span class="header-date">{{ $displayDate }}</span>
            <a href="?date={{ $nextDate }}" class="header-next">翌日 →</a>
        </div>
        <div class="container_table">
        <table class="attendance-table">
            <thead>
                <tr>
                    <th>名前</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($adminData as $attendance)
                <tr>
                    <td>{{ $attendance['user']->name }}</td>
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
    </div>
</main>
@endsection