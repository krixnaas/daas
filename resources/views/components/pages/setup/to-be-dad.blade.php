<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Native\Mobile\Facades\Haptic;
use Illuminate\Support\Facades\DB;

new #[Layout('layouts.app')] class extends Component {
    public $currentStep = 1;
    public $totalSteps = 2;
    
    // Default with one child input visible
    public $arrivals = [
        ['name' => '', 'due_date' => '', 'gender' => 'unknown']
    ];

    public $trackMom = null;
    public $momName = '';

    public function addArrival()
    {
        $this->arrivals[] = ['name' => '', 'due_date' => '', 'gender' => 'unknown'];
        if (class_exists(Haptic::class)) Haptic::impact('light');
    }

    public function removeArrival($index)
    {
        if (count($this->arrivals) > 1) {
            unset($this->arrivals[$index]);
            $this->arrivals = array_values($this->arrivals);
            if (class_exists(Haptic::class)) Haptic::impact('medium');
        }
    }

    public function nextStep()
    {
        if ($this->currentStep === 1) {
            foreach ($this->arrivals as $arrival) {
                if (empty($arrival['due_date'])) {
                    $this->addError('due_date', 'Arrival date is required for all entries.');
                    return;
                }
            }
        }

        if ($this->currentStep < $this->totalSteps) {
            if (class_exists(Haptic::class)) Haptic::impact('medium');
            $this->currentStep++;
        } else {
            $this->save();
        }
    }

    public function prevStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        } else {
            return redirect()->route('selection');
        }
    }

    public function save()
{
    DB::transaction(function () {
        $profile = auth()->user()->dadProfile()->create([
            'type' => 'expectant',
            'track_mom' => $this->trackMom ?? false,
            'partner_name' => $this->momName,
            'tab_config' => [], 
        ]);

        $tabs = [];

        if ($this->trackMom) {
            $tabs[] = [
                'id' => 'mom', 
                'label' => $this->momName ?: 'Mom', 
                'type' => 'mom'
            ];
        }

        // Use $this->arrivals here, and $index + 1 for the default name
        foreach ($this->arrivals as $index => $arrival) {
            $child = $profile->children()->create([
                'name' => $arrival['name'] ?: 'Baby ' . ($index + 1),
                'due_date' => $arrival['due_date'],
                'gender' => $arrival['gender'],
                'status' => 'expecting'
            ]);

            $tabs[] = [
                'id' => 'child_' . $child->id, 
                'label' => $child->name, 
                'type' => 'expectant'
            ];
        }

        $profile->update(['tab_config' => $tabs]);
    });

    if (class_exists(Haptic::class)) Haptic::success();
    return redirect()->route('dashboard');
}
}; ?>

<div class="min-h-screen bg-white dark:bg-zinc-950 flex flex-col p-8 font-sans antialiased">
    <div class="flex items-center justify-between mb-8 pt-4">
        <button wire:click="prevStep" class="flex items-center gap-1 text-slate-400 hover:text-blue-600 transition-colors group">
            <x-lucide-chevron-left class="w-5 h-5 transition-transform group-active:-translate-x-1" />
            <span class="text-[10px] font-black uppercase tracking-widest">Back</span>
        </button>
        <div class="flex flex-col items-end">
            <span class="text-[10px] font-black text-slate-300 dark:text-zinc-700 uppercase tracking-[0.2em]">Phase</span>
            <span class="text-xs font-black text-blue-600 italic uppercase tracking-tighter">{{ $currentStep }}/{{ $totalSteps }}</span>
        </div>
    </div>

    <div class="w-full h-1 bg-slate-100 dark:bg-zinc-900 rounded-full mb-12 overflow-hidden">
        <div class="h-full bg-blue-600 transition-all duration-700 ease-in-out" style="width: {{ ($currentStep / $totalSteps) * 100 }}%"></div>
    </div>

    <div class="flex-1 max-w-md mx-auto w-full">
        @if($currentStep === 1)
            <div x-transition class="space-y-8">
                <div class="space-y-1">
                    <h2 class="heading-premium text-5xl text-slate-900 dark:text-white leading-none">The Arrival</h2>
                    <p class="text-blue-600 font-black text-[10px] uppercase tracking-[0.3em]">Incoming Profiles</p>
                </div>

                <div class="space-y-6">
                    @foreach($arrivals as $index => $arrival)
                        <div class="relative p-6 bg-slate-50 dark:bg-zinc-900 rounded-[2.5rem] border-2 border-transparent shadow-sm">
                            @if(count($arrivals) > 1)
                                <button wire:click="removeArrival({{ $index }})" class="absolute -top-2 -right-2 w-8 h-8 bg-white dark:bg-zinc-800 shadow-lg rounded-full flex items-center justify-center text-rose-500">
                                    <x-lucide-minus class="w-4 h-4" />
                                </button>
                            @endif

                            <div class="space-y-5">
                                <flux:input wire:model="arrivals.{{ $index }}.name" placeholder="Nickname (e.g. Peanut)" size="xl" class="rounded-2xl border-none shadow-sm bg-white dark:bg-zinc-800" />
                                <flux:input wire:model="arrivals.{{ $index }}.due_date" type="date" label="Estimated Due Date" size="xl" class="rounded-2xl border-none shadow-sm bg-white dark:bg-zinc-800" />
                                
                                <div class="grid grid-cols-3 gap-2">
                                    @foreach(['boy', 'girl', 'unknown'] as $g)
                                        <button wire:click="$set('arrivals.{{ $index }}.gender', '{{ $g }}')"
                                            class="py-3 rounded-xl font-black text-[10px] uppercase tracking-widest border-2 transition-all {{ $arrivals[$index]['gender'] === $g ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-zinc-800 border-transparent text-slate-400' }}">
                                            {{ $g }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <button wire:click="addArrival" class="w-full py-4 flex items-center justify-center gap-2 group transition-all">
                        <div class="w-8 h-8 rounded-full border-2 border-slate-200 dark:border-zinc-800 flex items-center justify-center group-hover:border-blue-600 group-hover:bg-blue-50 transition-all">
                            <x-lucide-plus class="w-4 h-4 text-slate-400 group-hover:text-blue-600" />
                        </div>
                        <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 group-hover:text-blue-600">Add Another Arrival</span>
                    </button>
                </div>
            </div>
        @endif

        @if($currentStep === 2)
            <div x-transition class="space-y-10">
                <div class="bg-rose-500 w-16 h-16 rounded-[2rem] flex items-center justify-center shadow-lg shadow-rose-500/20 rotate-[4deg]">
                    <x-lucide-heart-pulse class="w-8 h-8 text-white" />
                </div>
                <div class="space-y-2">
                    <h2 class="heading-premium text-5xl text-slate-900 dark:text-white leading-none">Partner Support</h2>
                    <p class="text-rose-600 font-black text-[10px] uppercase tracking-[0.3em]">Maternal Health</p>
                </div>

                <div class="space-y-8">
                    <div class="flex gap-4">
                        <button wire:click="$set('trackMom', true)" class="flex-1 p-8 rounded-[2.5rem] border-2 transition-all flex flex-col items-center gap-3 {{ $trackMom === true ? 'border-rose-600 bg-rose-50 dark:bg-rose-950' : 'border-transparent bg-slate-50 dark:bg-zinc-900' }}">
                            <x-lucide-check-circle class="w-6 h-6 {{ $trackMom === true ? 'text-rose-600' : 'text-slate-300' }}" />
                            <span class="text-xs font-black uppercase tracking-widest">Enable</span>
                        </button>
                        <button wire:click="$set('trackMom', false)" class="flex-1 p-8 rounded-[2.5rem] border-2 transition-all flex flex-col items-center gap-3 {{ $trackMom === false ? 'border-slate-400 bg-slate-100 dark:bg-zinc-800' : 'border-transparent bg-slate-50 dark:bg-zinc-900' }}">
                            <x-lucide-minus-circle class="w-6 h-6 {{ $trackMom === false ? 'text-slate-600' : 'text-slate-300' }}" />
                            <span class="text-xs font-black uppercase tracking-widest">Skip</span>
                        </button>
                    </div>
                    @if($trackMom)
                        <flux:input wire:model="momName" placeholder="Partner's Name" size="xl" class="rounded-2xl border-none shadow-sm bg-slate-50 dark:bg-zinc-900" />
                    @endif
                </div>
            </div>
        @endif
    </div>

    <div class="mt-auto pt-10">
        <flux:button wire:click="nextStep" variant="primary" class="w-full h-16 rounded-[1.5rem] text-lg font-black italic uppercase tracking-widest bg-blue-600 shadow-xl shadow-blue-500/30">
            {{ $currentStep === $totalSteps ? 'Initialize App' : 'Continue' }}
            <x-slot name="iconTrailing"><x-lucide-arrow-right class="w-5 h-5" /></x-slot>
        </flux:button>
    </div>
</div>