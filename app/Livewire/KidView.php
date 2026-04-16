<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ActivityLog;
use App\Models\DadProfile;
use Native\Mobile\Facades\Haptic;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;

class KidView extends Component
{
    public $kidId;
    public $kidName;
    
    // Tracking States
    public $isSleeping = false;
    public $sleepStartedAt;
    
    // Form States - Feed
    public $feedAmount = '', $feedType = 'bottle', $feedSubtype = 'AF';
    public $breastSide = 'left'; 
    public $breastScale = 4;
    
    // Form States - Diaper
    public $diaperType = 'wet', $stoolType = 'yellow'; 
    public $diaperWeight = 2, $diaperNotes = '';

    // Form States - Profile
    public $weight, $height, $head, $bloodGroup, $umbilicalFellAt;

    public function mount($kidId, $kidName = 'Kiddo')
    {
        $this->kidId = $kidId;
        $this->kidName = $kidName;

        $child = \App\Models\Child::find($this->kidId);
        if ($child) {
            $this->weight = $child->weight;
            $this->height = $child->height;
            $this->head = $child->head_circumference;
            $this->bloodGroup = $child->blood_group;
            $this->umbilicalFellAt = $child->umbilical_cord_fell_off_at;
        }
        
        // Resume active sleep session if it exists
        $activeSleep = ActivityLog::where('subject_id', $this->kidId)
            ->where('category', 'sleep')
            ->whereNull('data->end_time')
            ->latest()
            ->first();

        if ($activeSleep) {
            $this->isSleeping = true;
            $this->sleepStartedAt = $activeSleep->logged_at;
        }
    }

    #[Computed]
    public function lastFeeding()
    {
        return ActivityLog::where('subject_id', $this->kidId)
            ->where('category', 'feed')
            ->latest()
            ->first();
    }
  
    #[Computed]
    public function logs()
    {
        return ActivityLog::where('subject_id', $this->kidId)
            ->where('subject_type', 'App\Models\Kid') 
            ->latest('logged_at')
            ->take(10)
            ->get();
    }

    public function toggleSleep()
    {
        if (!$this->isSleeping) {
            $this->logActivity('sleep', ['start_time' => now(), 'label' => 'Started Nap']);
            $this->isSleeping = true;
            $this->sleepStartedAt = now();
        } else {
            $log = ActivityLog::where('subject_id', $this->kidId)
                ->where('category', 'sleep')
                ->whereNull('data->end_time')
                ->latest()
                ->first();

            if ($log) {
                $duration = now()->diffInMinutes(Carbon::parse($this->sleepStartedAt));
                $log->update([
                    'data' => array_merge($log->data, [
                        'end_time' => now(),
                        'duration_minutes' => $duration,
                        'label' => "Slept for " . round($duration/60, 1) . "h"
                    ])
                ]);
            }
            $this->isSleeping = false;
            $this->sleepStartedAt = null;
        }
    }

    public function saveFeeding() {
        $meta = ['type' => $this->feedType, 'amount' => $this->feedAmount];

        if ($this->feedType === 'breast') {
            $meta['side'] = $this->breastSide;
            $meta['scale'] = $this->breastScale;
            $meta['subtype'] = $this->feedSubtype;
            $label = "{$this->feedSubtype} Breast ({$this->breastSide[0]}) scale {$this->breastScale}";
        } else {
            $label = "{$this->feedAmount}ml {$this->feedType} feed";
        }

        $this->logActivity('feed', array_merge($meta, ['label' => $label]));
        $this->reset(['feedAmount', 'breastScale']);
    }

    public function saveDiaper() {
        $meta = ['type' => $this->diaperType, 'weight' => $this->diaperWeight, 'notes' => $this->diaperNotes];
        $sym = ['', '+', '++', '+++', '++++'][$this->diaperWeight] ?? '+';

        if ($this->diaperType !== 'wet') {
            $meta['stool_type'] = $this->stoolType;
            $label = ucfirst($this->diaperType) . " ({$this->stoolType}) {$sym}";
        } else {
            $label = "Wet Diaper {$sym}";
        }

        $this->logActivity('nappy', array_merge($meta, ['label' => $label]));
        $this->reset(['diaperNotes', 'diaperWeight']);
    }

    public function saveProfile() {
        if ($child = \App\Models\Child::find($this->kidId)) {
            $child->update([
                'weight' => $this->weight, 'height' => $this->height, 'head_circumference' => $this->head,
                'blood_group' => $this->bloodGroup, 'umbilical_cord_fell_off_at' => $this->umbilicalFellAt,
            ]);
            $this->logActivity('profile', ['label' => 'Profile Updated']);
        }
    }

    public function logVaccine($name) {
        $this->logActivity('vaccine', ['name' => $name, 'label' => "Vaccine: {$name}"]);
    }

    private function logActivity($category, $data) {
        ActivityLog::create([
            'dad_profile_id' => auth()->user()->dadProfile->id,
            'subject_id' => $this->kidId,
            'subject_type' => 'App\Models\Kid',
            'category' => $category,
            'data' => $data,
            'logged_at' => now(),
        ]);
        
    }

    public function render()
    {
        return <<<'HTML'
        <div class="space-y-6 pb-24 animate-in fade-in duration-500 p-5 bg-[#F1F5F9]">
            <!-- Strategic Header -->
            <div class="flex items-center gap-4 bg-white dark:bg-zinc-900 p-4 rounded-3xl border border-slate-100 dark:border-zinc-800 shadow-sm relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:scale-110 transition-transform">
                    <x-lucide-baby class="w-32 h-32" />
                </div>
                <div class="w-14 h-14 rounded-2xl bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center border border-indigo-100 dark:border-indigo-500/20">
                    <x-lucide-baby class="w-7 h-7 text-indigo-500" />
                </div>
                <div class="flex-1">
                    <h2 class="text-xl font-black text-slate-900 dark:text-white italic tracking-tighter uppercase leading-none">{{ $kidName }}</h2>
                    <div class="flex items-center gap-2 mt-1">
                        @if($isSleeping)
                            <div class="flex items-center gap-1.5"><span class="flex h-1.5 w-1.5 rounded-full bg-indigo-500 animate-pulse"></span><p class="text-[9px] font-black text-indigo-500 uppercase tracking-widest">Sleeping</p></div>
                        @else
                            <div class="flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span><p class="text-[9px] font-black text-emerald-500 uppercase tracking-widest">Active</p></div>
                        @endif
                    </div>
                </div>
                <button onclick="Flux.modal('kid-profile-modal').show()" class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-zinc-800 flex items-center justify-center text-slate-400 active:scale-95 transition-all">
                    <!-- <x-lucide-settings-2 class="w-5 h-5" /> -->
                </button>
            </div>

            <!-- Protocol Quick Stats -->
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-blue-600 p-4 rounded-3xl shadow-lg shadow-blue-600/20 flex flex-col justify-between h-28 relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-2 opacity-10"><x-lucide-milk class="w-12 h-12 text-white" /></div>
                    <span class="text-[8px] font-black text-white/50 uppercase tracking-widest">Last Feed</span>
                    <p class="text-lg font-black text-white italic uppercase tracking-tighter leading-none">
                        {{ $this->lastFeeding ? $this->lastFeeding->logged_at->diffForHumans(short: true) : 'OFFLINE' }}
                    </p>
                </div>
                <div class="bg-indigo-600 p-4 rounded-3xl shadow-lg shadow-indigo-600/20 flex flex-col justify-between h-28 relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-2 opacity-10"><x-lucide-moon class="w-12 h-12 text-white" /></div>
                    <span class="text-[8px] font-black text-white/50 uppercase tracking-widest">Current Status</span>
                    <p class="text-lg font-black text-white italic uppercase tracking-tighter leading-none">
                        {{ $isSleeping ? 'RECOVERING' : 'OPERATIONAL' }}
                    </p>
                </div>
            </div>

            <!-- Action Protocol Grid -->
            <div class="grid grid-cols-2 gap-3">
                <button onclick="Flux.modal('kid-feed-modal').show()" 
                    class="col-span-2 bg-blue-600 h-16 rounded-[1rem] p-4 flex items-center justify-between shadow-xl shadow-blue-600/20 active:scale-[0.98] transition-all group">
                    <span class="text-xl font-black text-white uppercase italic leading-none">Record Feed</span>
                    <div class="bg-white/20 p-2 rounded-xl"><x-lucide-milk class="w-6 h-6 text-white" /></div>
                </button>

                <button onclick="Flux.modal('kid-diaper-modal').show()" 
                    class="bg-emerald-500 h-28 rounded-[1rem] p-4 flex flex-col justify-between shadow-lg shadow-emerald-500/20 active:scale-95 transition-all text-left">
                    <x-lucide-droplets class="w-8 h-8 text-white" />
                    <span class="font-black text-white uppercase italic tracking-tighter leading-tight">Diaper<br>Change</span>
                </button>

                <button wire:click="toggleSleep" 
                    @class([
                        'h-28 rounded-[1rem] p-4 flex flex-col justify-between transition-all active:scale-95 shadow-lg text-left',
                        'bg-indigo-600 shadow-indigo-600/30 text-white' => $isSleeping,
                        'bg-white dark:bg-zinc-900 border border-slate-100 dark:border-zinc-800' => !$isSleeping
                    ])>
                    <x-lucide-moon @class(['w-8 h-8', 'text-white' => $isSleeping, 'text-slate-400' => !$isSleeping]) />
                    <span class="font-black uppercase italic tracking-tighter leading-tight">Sleep<br>Protocol</span>
                </button>
            </div>

            <!-- Timeline -->
            <div class="bg-white dark:bg-zinc-900 rounded-3xl p-5 border border-slate-100 dark:border-zinc-800 shadow-sm mt-2">
                <h3 class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-400 italic mb-6">Execution Timeline</h3>
                <div class="relative space-y-5">
                    <div class="absolute left-6 top-1 bottom-1 w-px bg-slate-50 dark:bg-zinc-800"></div>
                    @forelse($this->logs as $log)
                        <div class="flex items-center gap-4 relative z-10" wire:key="log-{{ $log->id }}">
                            <div @class([
                                'w-10 h-10 rounded-xl flex items-center justify-center shadow-sm border-2 border-white dark:border-zinc-900',
                                'bg-blue-600 text-white' => $log->category === 'feed',
                                'bg-emerald-500 text-white' => $log->category === 'nappy',
                                'bg-indigo-600 text-white' => $log->category === 'sleep',
                                'bg-slate-100 text-slate-400' => $log->category !== 'feed' && $log->category !== 'nappy' && $log->category !== 'sleep'
                            ])>
                                @if($log->category === 'feed') <x-lucide-milk class="w-4 h-4" />
                                @elseif($log->category === 'nappy') <x-lucide-droplets class="w-4 h-4" />
                                @elseif($log->category === 'sleep') <x-lucide-moon class="w-4 h-4" />
                                @else <x-lucide-activity class="w-4 h-4" /> @endif
                            </div>
                            <div class="flex-1">
                                <p class="text-[10px] font-black text-slate-900 dark:text-white uppercase italic leading-tight">{{ $log->data['label'] ?? ucfirst($log->category) }}</p>
                                <p class="text-[8px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">{{ $log->logged_at->format('H:i') }} • {{ $log->logged_at->diffForHumans(short:true) }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-center py-4 text-slate-300 text-[9px] font-black uppercase italic tracking-[0.2em]">Silence in Sector 7</p>
                    @endforelse
                </div>
            </div>

            <!-- Modals (Profile, Feed, Diaper) -->
            <flux:modal name="kid-profile-modal" class="space-y-6 !rounded-t-[2.5rem] !p-6">
                <h2 class="text-xl font-black uppercase italic tracking-tighter">Tactical Profile</h2>
                <div class="grid grid-cols-2 gap-3">
                    <flux:input wire:model="weight" label="Weight (kg)" type="number" step="0.1" />
                    <flux:input wire:model="height" label="Height (cm)" type="number" />
                    <flux:input wire:model="head" label="Head (cm)" type="number" />
                    <flux:input wire:model="bloodGroup" label="Blood Group" />
                </div>
                <flux:input wire:model="umbilicalFellAt" type="date" label="Umbilical Cord Fell Off" />
                <flux:button wire:click="saveProfile" @click="Flux.modal('kid-profile-modal').hide()" class="w-full h-14 bg-blue-600 text-white font-black uppercase italic !rounded-xl">Sync Profile</flux:button>
            </flux:modal>

           <flux:modal name="kid-feed-modal" variant="flyout" side="bottom" class="!max-w-lg !rounded-t-[1rem] space-y-4 !p-8">
                <h2 class="text-lg font-black uppercase italic tracking-tighter text-blue-600">Feeding Protocol</h2>
                <div class="grid grid-cols-3 gap-2">
                    @foreach(['breast', 'ebm', 'formula'] as $t)
                        <button wire:click="$set('feedType', '{{ $t }}')" @class(['py-3 rounded-xl font-black text-[9px] uppercase border-2 transition-all', $feedType === $t ? 'bg-blue-600 border-blue-600 text-white' : 'bg-slate-50 dark:bg-zinc-800 border-transparent text-slate-400'])>{{ $t }}</button>
                    @endforeach
                </div>
                @if($feedType === 'breast')
                    <div class="space-y-4 pt-2">
                        <div class="grid grid-cols-2 gap-2">
                            <button wire:click="$set('breastSide', 'left')" @class(['py-3 rounded-lg font-black text-[9px] uppercase', $breastSide === 'left' ? 'bg-blue-600 text-white' : 'bg-slate-100 dark:bg-zinc-800 text-slate-400'])>Left (L)</button>
                            <button wire:click="$set('breastSide', 'right')" @class(['py-3 rounded-lg font-black text-[9px] uppercase', $breastSide === 'right' ? 'bg-blue-600 text-white' : 'bg-slate-100 dark:bg-zinc-800 text-slate-400'])>Right (R)</button>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[9px] font-black uppercase text-slate-400 ml-1">Saturation Scale (1-4)</label>
                            <div class="grid grid-cols-4 gap-2">
                                @foreach([1,2,3,4] as $v)
                                    <button wire:click="$set('breastScale', {{ $v }})" @class(['py-3 rounded-lg font-black text-xs border-2 transition-all', $breastScale == $v ? 'bg-blue-600 border-blue-600 text-white' : 'bg-slate-50 dark:bg-zinc-800 border-transparent text-slate-400'])>{{ $v }}</button>
                                @endforeach
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <button wire:click="$set('feedSubtype', 'AF')" @class(['py-2 rounded-lg text-[9px] font-black uppercase border', $feedSubtype === 'AF' ? 'border-blue-600' : 'border-transparent text-slate-400'])>AF</button>
                            <button wire:click="$set('feedSubtype', 'EBF')" @class(['py-2 rounded-lg text-[9px] font-black uppercase border', $feedSubtype === 'EBF' ? 'border-blue-600' : 'border-transparent text-slate-400'])>EBF</button>
                        </div>
                    @else
                        <flux:input wire:model="feedAmount" type="number" label="Quantity (ml)" />
                @endif
                <flux:button wire:click="saveFeeding" @click="Flux.modal('kid-feed-modal').hide()" class="w-full h-14 bg-blue-600 text-white font-black uppercase italic !rounded-xl">Execute Feed</flux:button>
            </flux:modal>

            <flux:modal name="kid-diaper-modal" variant="flyout" side="bottom" class="space-y-6 !rounded-t-[2.5rem] !p-6">
                <h2 class="text-xl font-black uppercase italic tracking-tighter text-emerald-600">Sanitation Protocol</h2>
                <div class="grid grid-cols-3 gap-2">
                    @foreach(['wet', 'dirty', 'both'] as $t)
                        <button wire:click="$set('diaperType', '{{ $t }}')" @class(['py-3 rounded-xl font-black text-[9px] uppercase border-2 transition-all', $diaperType === $t ? 'bg-emerald-500 border-emerald-500 text-white' : 'bg-slate-50 dark:bg-zinc-800 border-transparent text-slate-400'])>{{ $t }}</button>
                    @endforeach
                </div>
                @if($diaperType !== 'wet')
                    <div class="grid grid-cols-2 gap-2">
                        <button wire:click="$set('stoolType', 'yellow')" @class(['py-3 rounded-lg text-xs font-black', $stoolType === 'yellow' ? 'bg-emerald-500 text-white' : 'bg-slate-50 dark:bg-zinc-800 text-slate-400'])>💛 Yellow</button>
                        <button wire:click="$set('stoolType', 'green')" @class(['py-3 rounded-lg text-xs font-black', $stoolType === 'green' ? 'bg-emerald-500 text-white' : 'bg-slate-50 dark:bg-zinc-800 text-slate-400'])>💚 Green</button>
                    </div>
                @endif
                <div class="space-y-2">
                    <label class="text-[9px] font-black uppercase text-slate-400 ml-1">Weight Scale</label>
                    <div class="grid grid-cols-4 gap-2">
                        @foreach([1=>'+', 2=>'++', 3=>'+++', 4=>'++++'] as $v => $s)
                            <button wire:click="$set('diaperWeight', {{ $v }})" @class(['py-3 rounded-lg font-black text-xs border-2 transition-all', $diaperWeight == $v ? 'bg-emerald-500 border-emerald-500 text-white' : 'bg-slate-50 dark:bg-zinc-800 border-transparent text-slate-400'])>{{ $s }}</button>
                        @endforeach
                    </div>
                </div>
                <flux:textarea wire:model="diaperNotes" placeholder="Observation notes..." rows="2" />
                <flux:button wire:click="saveDiaper" @click="Flux.modal('kid-diaper-modal').hide()" class="w-full h-14 bg-emerald-500 text-white font-black uppercase italic !rounded-xl">Update Status</flux:button>
            </flux:modal>
        </div>
        HTML;
    }
}