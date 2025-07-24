@extends('layouts.app')

@section('title')
    会員登録画面
@endsection

@section('css')
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endsection

@section('content')
@include('layouts.components.header')
<main>
    <h2>会員登録</h2>
    <form action="{{ route('register') }}" method="post" novalidate>
        @csrf
        <label for="name">
            お名前
            @error('name')
                <span class="error-message">{{ $message }}</span>
            @enderror
        </label>
        <input type="text" id="name" name="name" value="{{ old('name') }}">

        <label for="email">
            メールアドレス
            @error('email')
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

        <label for="password_confirmation">
            確認用パスワード
            @error('password_confirmation')
                <span class="error-message">{{ $message }}</span>
            @enderror
        </label>
        <input type="password" id="password_confirmation" name="password_confirmation">

        <button class="submit_buttom" type="submit">登録する</button>
    </form>
    <a class="change" href="{{ route('login')}}">ログインはこちら</a>
</main>
@endsection