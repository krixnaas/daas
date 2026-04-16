<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ActivityLog;
use Illuminate\Support\Carbon;
use Native\Mobile\Facades\Haptic;

class PregnancyView extends Component
{
    public $pregnancy;
    public $displayType = 'fruit'; // Ported from your React 'fruit' | 'animal'
    public $activityText = '';

    public function mount($pregnancy)
    {
        $this->pregnancy = $pregnancy;
        $this->displayType = $pregnancy->size_display_type ?? 'fruit';
    }

    public function toggleDisplay()
    {
        $this->displayType = $this->displayType === 'fruit' ? 'animal' : 'fruit';
        $this->pregnancy->update(['size_display_type' => $this->displayType]);
    }

    public function saveActivity()
    {
        ActivityLog::create([
            'dad_profile_id' => auth()->user()->dadProfile->id,
            'subject_id' => $this->pregnancy->id,
            'subject_type' => 'App\Models\Pregnancy',
            'category' => 'pregnancy_log',
            'data' => ['activity' => $this->activityText, 'label' => $this->activityText],
            'logged_at' => now(),
        ]);

        $this->reset('activityText');
        if (class_exists(Haptic::class)) Haptic::success();
    }

    public function render()
    {
        // Logic for "Days until due" ported from your React constants
        $dueDate = Carbon::parse($this->pregnancy->due_date);
        $daysToGoal = now()->diffInDays($dueDate, false);
        
        return <<<'HTML'
        <div class="space-y-6 animate-in fade-in zoom-in-95 duration-500">
            <div class="bg-white dark:bg-zinc-900 p-8 rounded-[3rem] shadow-xl shadow-indigo-500/5 border border-slate-100 dark:border-zinc-800">
                <h2 class="text-3xl font-black italic uppercase tracking-tighter mb-2">
                    {{ $pregnancy->baby_name ?? "Baby's" }} Journey
                </h2>
                <div class="flex items-center gap-2 text-slate-400">
                    <x-lucide-calendar class="w-4 h-4" />
                    <span class="text-[10px] font-black uppercase tracking-widest">
                        Due {{ $dueDate->format('M d, Y') }} • 
                        <span class="text-indigo-600">{{ $daysToGoal > 0 ? "$daysToGoal days to go" : "Due today!" }}</span>
                    </span>
                </div>
            </div>

            <div class="bg-orange-500 p-8 rounded-[3rem] shadow-xl shadow-orange-500/20 flex items-center justify-between">
                <div>
                    <p class="text-white/60 text-[10px] font-black uppercase tracking-widest">Contraction Tracker</p>
                    <h3 class="text-xl font-black text-white uppercase italic">Labor Mode</h3>
                </div>
                <button class="bg-white/20 p-4 rounded-2xl text-white">
                    <x-lucide-timer class="w-8 h-8" />
                </button>
            </div>

            <div class="bg-indigo-600 p-8 rounded-[3rem] text-white">
                <div class="flex justify-between items-start mb-6">
                    <span class="text-4xl">
                        {{ $displayType === 'fruit' ? '🍋' : '🐿️' }}
                    </span>
                    <button wire:click="toggleDisplay" class="bg-white/20 px-4 py-2 rounded-full text-[10px] font-black uppercase">
                        Switch to {{ $displayType === 'fruit' ? 'Animal' : 'Fruit' }}
                    </button>
                </div>
                <p class="text-white/60 text-[10px] font-black uppercase tracking-widest">Week 32</p>
                <h3 class="text-2xl font-black uppercase italic">Baby is the size of a Squash</h3>
            </div>
        </div>
        HTML;
    }
}