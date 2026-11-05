@extends('layouts.admin')

@section('title', $user->full_name)

@section('header')
    <x-admin.page-header :title="$user->full_name" :description="$user->email"
        :breadcrumb="['Yönetim' => route('management.index'), 'Kullanıcılar' => route('users.index')]">
        <x-slot:leading><x-admin.avatar :user="$user" class="size-12 text-base" /></x-slot:leading>
        <x-admin.role-badge :role="$user->role" />
    </x-admin.page-header>
@endsection

@section('content')
    @include('admin.users._form')
@endsection
