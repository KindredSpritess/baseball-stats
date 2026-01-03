@extends('layouts.main')

@section('title')
  StatsKeeper - User Preferences
@endsection

@section('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
<div style="max-width: 1200px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); padding: 40px; max-width: 400px; width: 100%; text-align: center;">
        @vite(['resources/js/user-preferences.js'])
        <div id="preferences-app"></div>
    </div>
</div>
@endsection