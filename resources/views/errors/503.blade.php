@extends('errors.layout')

@section('code', '503')
@section('title', 'Sedang dalam perbaikan')
@section('message', config('app.name', 'SMA NU Toboali').' sedang diperbarui dan akan segera kembali. Terima kasih atas kesabaran Anda.')

@section('actions')
    <a class="btn primary" href="">Coba lagi</a>
@endsection
