<!--  -->
@extends('layouts.app')

@section('title')
    test
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance_index.css') }}">
@endsection

@section('content')
@include('layouts.components.header')
@include('layouts.components.headerAdmin')
<main>test
</main>
@endsection