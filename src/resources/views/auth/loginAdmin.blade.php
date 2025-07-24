@extends('layouts.app')

@section('title')
    ログイン
@endsection

@section('css')
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endsection

@section('content')
@include('layouts.components.headerAdmin')
<main>
    <h2>ログイン（管理者）</h2>
    <form action="{{ route('admin.login') }}" method="post" novalidate>
        @csrf
        <label for="email">
            メールアドレス
            @error('email')
                <span class="error-message">{{ $message }}</span>
            @enderror
            @error('auth.failed')
                <span class="error-message">{{ $message }}</span>
            @enderror
        </label>
        <input type="email" id="email" name="email" value="{{ old('email') }}">
        <label for="password">
            パスワード
            @error('password')
                <span class="error-message">{{ $message }}</span>
            @enderror
        </label>
        <input type="password" id="password" name="password">
        <button class="submit_buttom" type="submit">ログインする</button>
    </form>
</main>
@endsection