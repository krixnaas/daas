<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::middleware('guest')->group(function () {
    // This points to resources/views/components/pages/auth/login.blade.php
    Route::livewire('/login', 'pages.auth.login')->name('login');
    Route::livewire('/register', 'pages.auth.register')->name('register');
});

// 2. Protected App Routes
Route::middleware(['auth', 'verified'])->group(function () {
    

    Route::livewire('/setup/to-be-dad', 'pages.setup.to-be-dad')->name('setup.to-be-dad');
    Route::livewire('/setup/existing-dad', 'pages.setup.existing-dad')->name('setup.existing-dad');

    // The "Gatekeeper" - Choose Expectant vs Existing
    Route::livewire('/selection', 'pages.selection')->name('selection');

    // The Onboarding Form
    Route::livewire('/onboarding/setup', 'pages.onboarding.setup')->name('onboarding.setup');

    // The Main App Dashboard
    Route::livewire('/dashboard', 'pages.dashboard')->name('dashboard');
    
    // Root Redirect
    // Route::get('/', function () {
    //     return redirect()->route('dashboard');
    // });
});