<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Native\Mobile\Facades\Haptic;
use Illuminate\Support\Facades\DB;

new #[Layout('layouts.app')] class extends Component {
    public $currentStep = 1;
    public $totalSteps = 2;
    
    public $children = [
        ['name' => '', 'dob' => '', 'gender' => '']
    ];

    public $trackMom = null;
    public $momName = ''; // Added property for partner's name

    public function addChild()
    {
        $this->children[] = ['name' => '', 'dob' => '', 'gender' => ''];
        if (class_exists(Haptic::class)) Haptic::impact('light');
    }

    public function removeChild($index)
    {
        if (count($this->children) > 1) {
            unset($this->children[$index]);
            $this->children = array_values($this->children);
            if (class_exists(Haptic::class)) Haptic::impact('medium');
        }
    }

    public function nextStep()
    {
        if ($this->currentStep === 1) {
            foreach ($this->children as $child) {
                if (empty($child['name']) || empty($child['dob'])) {
                    $this->addError('children', 'Please complete all child profiles.');
                    return;
                }
            }
        }

        // Validation for Step 2 if tracking mom
        if ($this->currentStep === 2 && $this->trackMom === true && empty($this->momName)) {
            $this->addError('momName', 'Please enter your partner\'s name.');
            return;
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
            // 1. Create the Dad Profile first
            $profile = auth()->user()->dadProfile()->create([
                'type' => 'existing',
                'track_mom' => $this->trackMom ?? false,
                'partner_name' => $this->momName,
                'tab_config' => [], // Temporary empty
            ]);

            // 2. Create the Children and gather their IDs
            $tabs = [];
            
            if ($this->trackMom) {
                $tabs[] = [
                    'id' => 'mom',
                    'label' => $this->momName ?: 'Mom',
                    'type' => 'mom'
                ];
            }

            foreach ($this->children as $childData) {
                $child = $profile->children()->create([
                    'name' => $childData['name'],
                    'date_of_birth' => $childData['dob'],
                    'gender' => $childData['gender'],
                    'status' => 'born'
                ]);

                $tabs[] = [
                    'id' => 'child_' . $child->id,
                    'label' => $child->name,
                    'type' => 'kid'
                ];
            }

            // 3. Update the Dad Profile with real tab config
            $profile->update(['tab_config' => $tabs]);
        });

        if (class_exists(Haptic::class)) Haptic::success();
        return redirect()->route('dashboard');
    }
}; ?>

<div class="min-h-screen bg-white dark:bg-zinc-950 flex flex-col p-8 font-sans antialiased">
    <div class="flex items-center justify-between mb-8 pt-4">
        <button wire:click="prevStep" class="flex items-center gap-1 text-slate-400 hover:text-indigo-600 transition-colors group">
            <x-lucide-chevron-left class="w-5 h-5 transition-transform group-active:-translate-x-1" />
            <span class="text-[10px] font-black uppercase tracking-widest">Back</span>
        </button>
        <div class="flex flex-col items-end">
            <span class="text-[10px] font-black text-slate-300 dark:text-zinc-700 uppercase tracking-[0.2em]">Step</span>
            <span class="text-xs font-black text-indigo-600 italic uppercase tracking-tighter">{{ $currentStep }}/{{ $totalSteps }}</span>
        </div>
    </div>

    <div class="w-full h-1 bg-slate-100 dark:bg-zinc-900 rounded-full mb-12 overflow-hidden">
        <div class="h-full bg-indigo-600 transition-all duration-700 ease-in-out" style="width: {{ ($currentStep / $totalSteps) * 100 }}%"></div>
    </div>

    <div class="flex-1 max-w-md mx-auto w-full">
        @if($currentStep === 1)
            <div x-transition class="space-y-8">
                <div class="space-y-1">
                    <h2 class="heading-premium text-3xl text-slate-900 dark:text-white leading-none">The Squad</h2>
                    <p class="text-indigo-600 font-black text-[10px] uppercase tracking-[0.3em]">Active Profiles</p>
                </div>

                <div class="space-y-6">
                    @foreach($children as $index => $child)
                        <div class="relative p-6 bg-slate-50 dark:bg-zinc-900 rounded-[1.5rem] shadow-sm">
                            @if(count($children) > 1)
                                <button wire:click="removeChild({{ $index }})" class="absolute -top-2 -right-2 w-8 h-8 bg-white dark:bg-zinc-800 shadow-lg rounded-full flex items-center justify-center text-rose-500">
                                    <x-lucide-trash-2 class="w-4 h-4" />
                                </button>
                            @endif

                            <div class="space-y-5">
                                <flux:input wire:model="children.{{ $index }}.name" placeholder="Child's Name" size="xl" class="rounded-2xl border-none bg-white dark:bg-zinc-800" />
                                <flux:input wire:model="children.{{ $index }}.dob" type="date" label="Date of Birth" size="xl" class="rounded-2xl border-none bg-white dark:bg-zinc-800" />
                                
                                <div class="grid grid-cols-2 gap-2">
                                    @foreach(['boy' => '👦', 'girl' => '👧'] as $key => $emoji)
                                        <button wire:click="$set('children.{{ $index }}.gender', '{{ $key }}')"
                                            class="py-3 rounded-xl font-black text-[10px] uppercase tracking-widest border-2 transition-all {{ $children[$index]['gender'] === $key ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white dark:bg-zinc-800 border-transparent text-slate-400' }}">
                                            <span class="mr-1">{{ $emoji }}</span> {{ $key }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <button wire:click="addChild" class="w-full py-4 flex items-center justify-center gap-2 group">
                        <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 group-hover:text-indigo-600">+ Add Sibling</span>
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
                    <h2 class="heading-premium text-3xl text-slate-900 dark:text-white leading-none">Partner Support</h2>
                    <p class="text-rose-600 font-black text-[10px] uppercase tracking-[0.3em]">Recovery & Care</p>
                </div>

                <div class="space-y-6">
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

                    @if($trackMom === true)
                        <div class="animate-in fade-in slide-in-from-top-4 duration-500 space-y-4 pt-4">
                            <flux:input 
                                wire:model="momName" 
                                label="Partner's Name" 
                                placeholder="e.g. Sarah" 
                                size="xl" 
                                class="rounded-2xl border-none bg-slate-50 dark:bg-zinc-900 shadow-sm" 
                            />
                            <p class="text-[10px] text-slate-400 font-medium px-2 uppercase tracking-wider">
                                We'll use this to personalize her support dashboard.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <div class="mt-auto pt-10">
        <flux:button wire:click="nextStep" variant="primary" 
        class="w-full h-16 rounded-[1.5rem] text-lg 
        italic uppercase tracking-widest  shadow-xl shadow-indigo-500/30">
            {{ $currentStep === $totalSteps ? 'Start Tracking' : 'Continue' }}
            <x-slot name="iconTrailing"><x-lucide-rocket class="w-5 h-5" /></x-slot>
        </flux:button>
    </div>
</div>