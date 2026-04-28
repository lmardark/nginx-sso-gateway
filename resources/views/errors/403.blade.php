@extends('errors::minimal')

@section('title', __('Acesso Restrito'))
@section('code', '403')
@section('message', __($exception->getMessage() ?: 'Acesso Restrito'))
