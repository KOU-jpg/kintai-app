@extends('layouts.app')

@section('title')
    申請一覧画面
@endsection

@section('css')
  <link rel="stylesheet" href="{{ asset('css/attendance_request.css') }}">
@endsection

@section('content')
@include('layouts.components.headerAdmin')
<main>
    <div class="attendance-container">
        <div class="container_title">
            勤怠詳細
        </div>
        <div class="border">
            <ul class="border__list">
                <li>
                    <a href="?status=pending" class="{{ $status == 'pending' ? 'active' : '' }}">申請待ち</a>
                </li>
                <li>
                    <a href="?status=approved" class="{{ $status == 'approved' ? 'active' : '' }}">申請済み</a>
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
                        <th>申請理由</th>
                        <th>申請日時</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($requests as $request)
                    <tr>
                        <td>{{ $request->request_status === 'pending' ? '申請待ち' : '申請済み' }}</td>
                        <td>{{ $request->user->name ?? '不明' }}</td>
                        <td>{{ \Carbon\Carbon::parse($request->work_date)->format('Y/m/d') }}</td>
                        <td>{{ $request->note ?? '-' }}</td>
                        <td>{{ $request->created_at->format('Y/m/d H:i') }}</td>
                        <td>
                            <a href="{{ route('admin.request.approveForm', $request->attendance_id) }}" class="detail-link">詳細</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</main>
@endsection