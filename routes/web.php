<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::middleware('guest')->group(function () {
    // This points to resources/views/components/pages/auth/login.blade.php
    Route::livewire('/login', 'pages.auth.login')->name('login');
    Route::livewire('/register', 'pages.auth.register')->name('register');
});

Route::middleware('auth')->group(function () {
    Route::livewire('/verify-email', 'pages.auth.verify-email')->name('verification.notice');

    Route::get('/verify-email/{id}/{hash}', function (\Illuminate\Foundation\Auth\EmailVerificationRequest $request) {
        $request->fulfill();
        return redirect()->route('selection');
    })->middleware('signed')->name('verification.verify');

    Route::post('/email/verification-notification', function (\Illuminate\Http\Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('status', 'verification-link-sent');
    })->middleware('throttle:6,1')->name('verification.send');
});

// 2. Verified Tactical Sectors
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

Route::get('/auth/callback', function(Request $request){
    if($request->filled('token')){
        session(['auth_token'=> $request->get('token'), 'token_verified_at' => now()]);
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
})->name('auth.callback');