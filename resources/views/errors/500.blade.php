@extends('errors.layout')

@section('code', '500')
@section('title', 'Terjadi kesalahan')
@section('message', 'Maaf, terjadi kesalahan pada server. Kesalahan ini sudah tercatat di log sistem. Silakan coba beberapa saat lagi.')

@section('actions')
    <a class="btn primary" href="{{ url('/') }}">Ke beranda</a>
@endsection
