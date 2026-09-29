@extends('errors.layout')

@section('code', '419')
@section('title', 'Sesi berakhir')
@section('message', 'Halaman sudah terlalu lama dibuka sehingga sesi keamanannya berakhir. Muat ulang halaman lalu coba lagi.')

@section('actions')
    <a class="btn primary" href="">Muat ulang</a>
@endsection
