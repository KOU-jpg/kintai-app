@extends('layouts.app')

@section('title')
    スタッフ一覧画面
@endsection

@section('css')
  <link rel="stylesheet" href="{{ asset('css/attendance_index.css') }}">
@endsection

@section('content')
@include('layouts.components.headerAdmin')
<main>
    <div class="attendance-container">
        <div class="container_title">
            スタッフ一覧
        </div>
            <div class="container_form">
                      <div class="container_table">
        <table class="attendance-table">
            <thead>
                <tr>
                    <th>名前</th>
                    <th>メールアドレス</th>
                    <th>月次勤怠</th>
                </tr>
            </thead>
            <tbody>
              @foreach ($userData as $user)
              <tr>
                  <td>{{ $user['user']->name }}</td>
                  <td>{{ $user['user']->email }}</td>
                  <td>
                      <a href="{{ $user['detail_url'] }}" class="detail-link">詳細</a>
                  </td>
              </tr>
              @endforeach
            </tbody>
        </table>
        </div>
      </div>
    </div>
</main>
@endsection