@extends('layouts.default')

@section('title')
  mail_verify
@endsection

@section('css')
  <link rel="stylesheet" href="{{ asset('css/pages/verify.css') }}">
@endsection

@section('content')
@include('components.header')
  <div class="verify-container">
    <p class="verify-message">
    登録していただいたメールアドレスに認証メールを送付しました。<br>
    メール認証を完了してください。
    </p>
    <a href="http://localhost:8025" target="_blank" rel="noopener noreferrer"
    class="verify-btn">認証はこちらから
    </a>
    <form method="POST" action="{{ route('verification.send') }}">
    @csrf
    <button type="submit" class="resend-link">認証メールを再送する</button>
    </form>
  </div>
@endsection