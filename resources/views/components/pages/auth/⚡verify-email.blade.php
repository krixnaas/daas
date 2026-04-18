<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $otp = '';

    /**
     * Verify the tactical OTP.
     */
    public function verifyOtp(): void
    {
        $user = Auth::user();

        if ($this->otp === $user->email_otp && now()->lt($user->email_otp_expires_at)) {
            $user->markEmailAsVerified();
            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
            return;
        }

        $this->addError('otp', 'The intelligence code provided is invalid or expired.');
    }

    /**
     * Send an email verification notification to the user.
     */
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);

            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        $this->dispatch('verification-link-sent');
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(): void
    {
        Auth::guard('web')->logout();

        session()->invalidate();
        session()->regenerateToken();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div class="min-h-screen flex items-center justify-center p-6 bg-slate-50 dark:bg-zinc-950">
    <div class="w-full max-w-md space-y-8">
        <div class="text-center space-y-4">
            <div class="w-20 h-20 bg-blue-600 rounded-[2rem] flex items-center justify-center mx-auto shadow-xl shadow-blue-500/20">
                <x-lucide-shield-check class="w-10 h-10 text-white" />
            </div>
            <h2 class="heading-premium text-3xl text-slate-900 dark:text-white">Verify Intelligence</h2>
            <p class="text-slate-500 dark:text-zinc-400 text-sm max-w-xs mx-auto">
                Thanks for joining DAAS. Before starting your mission, please verify your email address by clicking the link we just emailed to you.
            </p>
        </div>

        <div class="space-y-6">
            <flux:input 
                wire:model="otp" 
                label="6-Digit Intelligence Code" 
                placeholder="000000" 
                class="text-center text-2xl font-black !bg-white !border-slate-100 !h-16" 
                maxlength="6"
            />

            <flux:button wire:click="verifyOtp" variant="primary" class="w-full h-16 rounded-2xl font-black uppercase italic tracking-widest bg-emerald-600 shadow-xl shadow-emerald-500/30">
                Execute Verification
            </flux:button>
        </div>

        <div class="pt-8 border-t border-slate-100">
            @if (session('status') == 'verification-link-sent')
                <div class="p-4 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-100 dark:border-emerald-500/20 rounded-2xl text-center mb-6">
                    <p class="text-[10px] font-black uppercase tracking-widest text-emerald-600">New Code Transmitted</p>
                </div>
            @endif

            <div class="space-y-4">
                <flux:button wire:click="sendVerification" variant="ghost" class="w-full h-12 font-black uppercase text-[10px] tracking-widest text-slate-500">
                    Resend Intelligence Code
                </flux:button>

                <flux:button wire:click="logout" variant="ghost" class="w-full font-black uppercase text-[10px] tracking-[0.2em] text-slate-400">
                    Abort Mission (Logout)
                </flux:button>
            </div>
        </div>
    </div>
</div>
