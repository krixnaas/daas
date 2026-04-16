<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Native\Mobile\Facades\Haptic;

new #[Layout('layouts.app')] class extends Component {
    public function mount()
    {
        // Check if profile exists (NativePHP/Laravel Auth)
        if (auth()->user()->dadProfile()->exists()) {
            return redirect()->route('dashboard');
        }
    }

    public function select($journey)
    {
       
        session(['selected_journey' => $journey]);

        return $journey === 'expectant' 
            ? redirect()->route('setup.to-be-dad') 
            : redirect()->route('setup.existing-dad');
    }
}; ?>

<div class="min-h-screen bg-slate-50 dark:bg-zinc-950 flex flex-col justify-center px-6 py-12">
    <div class="text-center mb-12 space-y-3">
        <div class="flex items-center justify-center gap-2 mb-4">
            <flux:icon.sparkles class="text-blue-600 w-10 h-10" />
            <flux:icon.heart variant="solid" class="text-rose-500 w-6 h-6 mt-4" />
        </div>
        
        <h1 class="text-5xl font-extrabold tracking-tighter text-slate-900 dark:text-white">DAAS</h1>
        <p class="text-blue-600 font-bold tracking-[0.2em] uppercase text-sm">Dad As A Service</p>
        <p class="text-slate-500 dark:text-zinc-400 text-base max-w-xs mx-auto">
            The essential companion for every dad
        </p>
    </div>

    <div class="max-w-md mx-auto w-full space-y-4">
        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest ml-1">I am a...</p>

        <button wire:click="select('expectant')" class="w-full text-left group">
            <div class="bg-blue-50 dark:bg-blue-900/10 border-2 border-blue-100 dark:border-blue-900/30 rounded-[2rem] p-5 flex items-center gap-4 transition-all active:scale-95 hover:border-blue-500">
                <div class="w-14 h-14 bg-white dark:bg-zinc-800 rounded-full flex items-center justify-center shadow-sm text-2xl">
                    🤰
                </div>
                
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white leading-tight">To Be Dad</h3>
                    <p class="text-xs text-slate-500 dark:text-zinc-400 leading-normal mt-1">
                        Partner is pregnant? Track the journey from first kick to first breath.
                    </p>
                </div>

                <div class="w-8 h-8 bg-white dark:bg-zinc-800 rounded-full flex items-center justify-center text-slate-900 dark:text-white shadow-sm">
                    <flux:icon.chevron-right class="w-4 h-4" />
                </div>
            </div>
        </button>

        <button wire:click="select('existing')" class="w-full text-left group">
            <div class="bg-indigo-50 dark:bg-indigo-900/10 border-2 border-indigo-100 dark:border-indigo-900/30 rounded-[2rem] p-5 flex items-center gap-4 transition-all active:scale-95 hover:border-indigo-500">
                <div class="w-14 h-14 bg-white dark:bg-zinc-800 rounded-full flex items-center justify-center shadow-sm text-2xl">
                    👨‍👧
                </div>
                
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white leading-tight">Existing Dad</h3>
                    <p class="text-xs text-slate-500 dark:text-zinc-400 leading-normal mt-1">
                        Already a dad? Track feeds, sleep, nappies and milestones.
                    </p>
                </div>

                <div class="w-8 h-8 bg-white dark:bg-zinc-800 rounded-full flex items-center justify-center text-slate-900 dark:text-white shadow-sm">
                    <flux:icon.chevron-right class="w-4 h-4" />
                </div>
            </div>
        </button>
    </div>

    <p class="text-center text-[10px] text-slate-400 mt-12 uppercase tracking-tight">
        Your family data stays private on your device
    </p>
</div>