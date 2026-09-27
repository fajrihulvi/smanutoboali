@extends('errors.layout')

@section('code', '503')
@section('title', 'Sedang dalam perbaikan')
@section('message', 'Skoolia sedang diperbarui dan akan segera kembali. Terima kasih atas kesabaran Anda.')

@section('actions')
    <a class="btn primary" href="javascript:location.reload()">Coba lagi</a>
@endsection
