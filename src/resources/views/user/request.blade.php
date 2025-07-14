@extends('layouts.app')

@section('title')
    申請一覧
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance_request.css') }}">
@endsection

@section('content')
@include('layouts.components.header')
<main>
    <div class="attendance-container">
        <div class="container_title">
            勤怠詳細
        </div>
        <div class="border">
            <ul class="border__list">
                <li>
                    <a href="?status=pending" class="{{ request('status', 'pending') == 'pending' ? 'active' : '' }}">申請待ち</a>
                </li>
                <li>
                    <a href="?status=approved" class="{{ request('status') == 'approved' ? 'active' : '' }}">申請済み</a>
                </li>
            </ul>
        </div>
        <div class="container_table">
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th>状態</th>
                        <th>名前</th>
                        <th>対象日時</th>
                        <th>休憩</th>
                        <th>申請理由</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    
                    <tr>
                        <td>承認待ち</td>
                        <td>Lubowitz Megane</td>
                        <td>2024/09/24</td>
                        <td>遅延のため遅延のため遅延のため</td>
                        <td>2024/09/09</td>
                        <td>
                            <a href=# class="detail-link">詳細</a>
                        </td>
                    </tr>
                    
                </tbody>
            </table>
        </div>
    </div>
</main>
@endsection