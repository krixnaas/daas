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
    public $leftBreastScale = 0;
    public $rightBreastScale = 0;
    public $ebmAmount = '';
    public $formulaAmount = '';
    public $feedAmount = '', $feedType = 'breast'; 
    public $nextFeedHours = 3, $feedTime;
    
    // Form States - Diaper
    public $isWet = true;
    public $isDirty = false;
    public $stoolType = 'yellow'; 
    public $wetWeight = 2;
    public $dirtyWeight = 2;
    public $diaperNotes = '';

    // Form States - Profile
    public $weight, $height, $head, $bloodGroup, $umbilicalFellAt;

    // Form States - Sleep
    public $sleepStartTime, $sleepEndTime;

    public $perPage = 10;
    public $appointmentTitle = '', $appointmentTime = null, $appointmentNotes = '';

    public function mount($kidId, $kidName = 'Kiddo')
    {
        $this->kidId = $kidId;
        $this->kidName = $kidName;
        $this->feedTime = now()->format('Y-m-d\TH:i');
        $this->sleepStartTime = now()->subHour()->format('Y-m-d\TH:i');
        $this->sleepEndTime = now()->format('Y-m-d\TH:i');

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
            ->where('subject_type', 'App\Models\Child') 
            ->latest('logged_at')
            ->take($this->perPage)
            ->get();
    }

    #[Computed]
    public function nextFeedDue()
    {
        $last = ActivityLog::where('subject_id', $this->kidId)
            ->where('category', 'feed')
            ->latest('logged_at')
            ->first();

        if (!$last || !isset($last->data['next_hour'])) return null;

        $due = $last->logged_at->addHours((int)$last->data['next_hour']);
        return $due->isFuture() ? $due->format('g:i a a') . ' (' . $due->diffForHumans() . ')' : 'OVERDUE: ' . $due->diffForHumans();
    }

    #[Computed]
    public function ageInfo()
    {
        $child = \App\Models\Child::find($this->kidId);
        if (!$child || !$child->date_of_birth) return 'New Born';
        
        $dob = Carbon::parse($child->date_of_birth);
        if ($dob->isFuture()) return 'Awaiting Arrival';
        
        $diff = now()->diff($dob);
        
        if ($diff->days > 0) {
            return $diff->days . ($diff->days === 1 ? ' Day' : ' Days') . ' Old';
        }
        
        if ($diff->h > 0) {
            return $diff->h . ($diff->h === 1 ? ' Hour' : ' Hours') . ' Old';
        }
        
        if ($diff->i > 0) {
            return $diff->i . ($diff->i === 1 ? ' Minute' : ' Minutes') . ' Old';
        }
        
        return $diff->s . ' Seconds Old';
    }

    #[Computed]
    public function nextWetPredicted()
    {
        return $this->calculatePrediction('wet');
    }

    #[Computed]
    public function nextDirtyPredicted()
    {
        return $this->calculatePrediction('dirty');
    }

    private function calculatePrediction($type)
    {
        $query = ActivityLog::where('subject_id', $this->kidId)->where('category', 'nappy');
        
        if ($type === 'wet') {
            $query->whereIn('data->type', ['wet', 'both']);
        } else {
            $query->whereIn('data->type', ['dirty', 'both']);
        }

        $logs = $query->latest('logged_at')->take(5)->get();

        if ($logs->count() < 2) return null;

        $intervals = [];
        for ($i = 0; $i < $logs->count() - 1; $i++) {
            $intervals[] = abs($logs[$i]->logged_at->diffInMinutes($logs[$i+1]->logged_at));
        }

        $avgMinutes = array_sum($intervals) / count($intervals);
        $avgMinutes = min($avgMinutes, 360); // 6 hour cap
        $predictedTime = $logs[0]->logged_at->addMinutes($avgMinutes);

        if ($predictedTime->isPast()) {
            return ['time' => 'Due Now', 'relative' => 'Imminent', 'minutes' => 0];
        }
        
        return [
            'time' => $predictedTime->format('g:i a'),
            'relative' => $predictedTime->diffForHumans(options: Carbon::DIFF_RELATIVE_TO_NOW, short: true),
            'minutes' => round($avgMinutes)
        ];
    }

    #[Computed]
    public function totalSleepToday()
    {
        $logs = ActivityLog::where('subject_id', $this->kidId)
            ->where('category', 'sleep')
            ->where(function($q) {
                $q->whereDate('logged_at', now()->toDateString())
                  ->orWhereDate('data->end_time', now()->toDateString());
            })
            ->get();
            
        $totalMinutes = 0;
        foreach($logs as $log) {
            $start = Carbon::parse($log->logged_at);
            $end = isset($log->data['end_time']) ? Carbon::parse($log->data['end_time']) : now();
            
            // Limit to today
            $todayStart = now()->startOfDay();
            $todayEnd = now()->endOfDay();
            
            $effectiveStart = $start->greaterThan($todayStart) ? $start : $todayStart;
            $effectiveEnd = $end->lessThan($todayEnd) ? $end : $todayEnd;
            
            if ($effectiveEnd->greaterThan($effectiveStart)) {
                $totalMinutes += $effectiveStart->diffInMinutes($effectiveEnd);
            }
        }
        
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;
        
        return "{$hours}h {$minutes}m";
    }

    #[Computed]
    public function umbilicalInfo()
    {
        if (!$this->umbilicalFellAt) return null;
        
        try {
            $date = Carbon::parse($this->umbilicalFellAt);
            return "Cord: " . $date->format('M d, g:i a');
        } catch (\Exception $e) {
            return "Cord: " . $this->umbilicalFellAt;
        }
    }

    public function loadMore()
    {
        $this->perPage += 10;
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

    public function logSleep()
    {
        $start = Carbon::parse($this->sleepStartTime);
        $end = Carbon::parse($this->sleepEndTime);
        $duration = $start->diffInMinutes($end);

        $this->logActivity('sleep', [
            'label' => "Slept for " . round($duration/60, 1) . "h",
            'start_time' => $start,
            'end_time' => $end,
            'duration_minutes' => $duration,
        ], $start);
        
        if (class_exists(Haptic::class)) Haptic::success();
    }

    public function saveFeeding() {
        $meta = [];
        $labels = [];

        if ($this->leftBreastScale > 0) {
            $meta['left_breast'] = $this->leftBreastScale;
            $labels[] = "L:{$this->leftBreastScale}";
        }
        if ($this->rightBreastScale > 0) {
            $meta['right_breast'] = $this->rightBreastScale;
            $labels[] = "R:{$this->rightBreastScale}";
        }
        if ($this->ebmAmount > 0) {
            $meta['ebm'] = $this->ebmAmount;
            $labels[] = "{$this->ebmAmount}ml EBM";
        }
        if ($this->formulaAmount > 0) {
            $meta['formula'] = $this->formulaAmount;
            $labels[] = "{$this->formulaAmount}ml Formula";
        }

        if (empty($labels)) return;

        $this->logActivity('feed', array_merge($meta, [
            'label' => implode(' + ', $labels),
            'next_hour' => $this->nextFeedHours
        ]), $this->feedTime);
        $this->reset(['leftBreastScale', 'rightBreastScale', 'ebmAmount', 'formulaAmount']);
    }

    public $editingLogId, $editingLogLabel, $editingLogTime, $editingLogNotes;

    public function editLog($id)
    {
        $log = ActivityLog::find($id);
        if ($log) {
            $this->editingLogId = $id;
            $this->editingLogLabel = $log->data['label'] ?? $log->category;
            $this->editingLogTime = $log->logged_at->format('Y-m-d\TH:i');
            $this->editingLogNotes = $log->data['notes'] ?? '';
            $this->dispatch('modal-open', name: 'edit-log-modal');
        }
    }

    public function updateLog()
    {
        $log = ActivityLog::find($this->editingLogId);
        if ($log) {
            $data = $log->data;
            $data['label'] = $this->editingLogLabel;
            $data['notes'] = $this->editingLogNotes;
            
            $log->update([
                'data' => $data,
                'logged_at' => $this->editingLogTime
            ]);
            
            if (class_exists(Haptic::class)) Haptic::success();
        }
    }

    public function deleteLog($id)
    {
        ActivityLog::destroy($id);
        if (class_exists(Haptic::class)) Haptic::impact('medium');
    }

    public function saveGrowth()
    {
        if ($child = \App\Models\Child::find($this->kidId)) {
            $child->update([
                'weight' => $this->weight, 
                'height' => $this->height, 
                'head_circumference' => $this->head, 
                'blood_group' => $this->bloodGroup
            ]);
            
            // Refresh local state to ensure view stays sync'd
            $this->weight = $child->weight;
            $this->height = $child->height;
            $this->head = $child->head_circumference;
            $this->bloodGroup = $child->blood_group;

            $this->logActivity('growth', [
                'label' => 'Measurements: ' . $this->weight . 'kg',
                'weight' => $this->weight,
                'height' => $this->height,
                'head' => $this->head,
                'blood_group' => $this->bloodGroup
            ]);
            
            if (class_exists(Haptic::class)) Haptic::success();
        }
    }

    public function saveDiaper() {
        $meta = [];
        $labels = [];

        if ($this->isWet) {
            $meta['is_wet'] = true;
            $sw = ['', '+', '++', '+++', '++++'][$this->wetWeight] ?? '+';
            $meta['wet_weight'] = $this->wetWeight;
            $labels[] = "Wet {$sw}";
        }
        if ($this->isDirty) {
            $meta['is_dirty'] = true;
            $sd = ['', '+', '++', '+++', '++++'][$this->dirtyWeight] ?? '+';
            $meta['dirty_weight'] = $this->dirtyWeight;
            $meta['stool_type'] = $this->stoolType;
            $labels[] = "Dirty ({$this->stoolType}) {$sd}";
        }

        if (empty($labels)) return;

        $meta['notes'] = $this->diaperNotes;

        $this->logActivity('nappy', array_merge($meta, ['label' => implode(' + ', $labels)]));
        $this->reset(['isWet', 'isDirty', 'diaperNotes', 'wetWeight', 'dirtyWeight']);
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

    private function logActivity($category, $data, $time = null) {
        ActivityLog::create([
            'dad_profile_id' => auth()->user()->dadProfile->id,
            'subject_id' => $this->kidId,
            'subject_type' => 'App\Models\Child',
            'category' => $category,
            'data' => $data,
            'logged_at' => $time ?: now(),
        ]);
        if (class_exists(Haptic::class)) Haptic::success();
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
                    <div class="flex flex-col gap-1 mt-1">
                        <div class="flex items-center gap-2">
                            <span class="text-[9px] font-black uppercase text-indigo-500 tracking-widest">{{ $this->ageInfo }}</span>
                            @if($isSleeping)
                                <div class="flex items-center gap-1.5"><span class="flex h-1.5 w-1.5 rounded-full bg-indigo-500 animate-pulse"></span><p class="text-[9px] font-black text-indigo-500 uppercase tracking-widest">Sleeping</p></div>
                            @else
                                <div class="flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span><p class="text-[9px] font-black text-emerald-500 uppercase tracking-widest">Active</p></div>
                            @endif
                        </div>
                        @if($this->umbilicalInfo)
                            <p class="text-[8px] font-black uppercase text-slate-400 tracking-widest italic">{{ $this->umbilicalInfo }}</p>
                        @endif
                    </div>
                </div>
                <button onclick="Flux.modal('kid-profile-modal').show()" class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-zinc-800 flex items-center justify-center text-slate-400 active:scale-95 transition-all">
                    <!-- <x-lucide-settings-2 class="w-5 h-5" /> -->
                </button>
            </div>

            <!-- Biometric HUD -->
            <div onclick="Flux.modal('kid-growth-modal').show()" class="grid grid-cols-4 gap-3 cursor-pointer active:scale-[0.98] transition-all">
                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm text-center">
                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Weight</p>
                    <p class="text-sm font-black text-slate-900">{{ $weight ?: '--' }}<span class="text-[10px] ml-0.5 opacity-40">kg</span></p>
                </div>
                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm text-center">
                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Height</p>
                    <p class="text-sm font-black text-slate-900">{{ $height ?: '--' }}<span class="text-[10px] ml-0.5 opacity-40">cm</span></p>
                </div>
                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm text-center">
                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Head</p>
                    <p class="text-sm font-black text-slate-900">{{ $head ?: '--' }}<span class="text-[10px] ml-0.5 opacity-40">cm</span></p>
                </div>
                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm text-center">
                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Blood</p>
                    <p class="text-sm font-black text-slate-900">{{ $bloodGroup ?: '--' }}</p>
                </div>
            </div>

            

            @if($this->nextFeedDue)
                <div class="flex items-center gap-3 bg-white border border-blue-100 p-4 rounded-3xl shadow-sm">
                    <div class="w-8 h-8 rounded-xl bg-blue-600 flex items-center justify-center shadow-lg shadow-blue-500/20">
                        <x-lucide-milk class="w-4 h-4 text-white" />
                    </div>
                    <div>
                        <p class="text-[8px] font-black text-blue-600 uppercase tracking-[0.2em] leading-none mb-1">Next Feeding Sync</p>
                        <span class="text-xs font-black text-slate-900 uppercase tracking-tight">{{ $this->nextFeedDue }}</span>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-2 gap-3">
                <div class="flex items-center gap-3 bg-white border border-indigo-100 p-4 rounded-3xl shadow-sm">
                    <div class="w-8 h-8 rounded-xl bg-indigo-500 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                        <x-lucide-droplets class="w-4 h-4 text-white" />
                    </div>
                    <div class="flex-1 overflow-hidden">
                        <h4 class="text-[7px] font-black text-indigo-500 uppercase tracking-widest leading-none mb-1">Next Wet</h4>
                        <div class="flex items-baseline gap-1">
                            <p class="text-xs font-black text-slate-900 uppercase italic leading-none">{{ $this->nextWetPredicted['time'] ?? '--' }}</p>
                            @if(isset($this->nextWetPredicted['relative']) && $this->nextWetPredicted['time'] !== 'Due Now')
                                <p class="text-[7px] font-black text-slate-400 uppercase italic truncate leading-none">({{ $this->nextWetPredicted['relative'] }})</p>
                            @endif
                            @if(isset($this->nextWetPredicted['minutes']))
                                <p class="text-[7px] font-black text-slate-600 uppercase">{{ $this->nextWetPredicted['minutes'] }} min</p>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3 bg-white border border-amber-100 p-4 rounded-3xl shadow-sm">
                    <div class="w-8 h-8 rounded-xl bg-amber-600 flex items-center justify-center shadow-lg shadow-amber-600/20">
                        <x-lucide-droplets class="w-4 h-4 text-white" />
                    </div>
                    <div class="flex-1 overflow-hidden">
                        <h4 class="text-[7px] font-black text-amber-600 uppercase tracking-widest leading-none mb-1">Next Dirty</h4>
                        <div class="flex items-baseline gap-1">
                            <p class="text-xs font-black text-slate-900 uppercase italic leading-none">{{ $this->nextDirtyPredicted['time'] ?? '--' }}</p>
                        @if(isset($this->nextDirtyPredicted['minutes']))
                                <p class="text-[7px] font-black text-slate-600 uppercase">{{ $this->nextDirtyPredicted['minutes'] }} min</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 bg-white border border-indigo-100 p-4 rounded-3xl shadow-sm">
                <div class="w-8 h-8 rounded-xl bg-indigo-600 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                    <x-lucide-moon class="w-4 h-4 text-white" />
                </div>
                <div class="flex-1">
                    <p class="text-[8px] font-black text-indigo-600 uppercase tracking-[0.2em] leading-none mb-1">Total Sleep Today</p>
                    <span class="text-xs font-black text-slate-900 uppercase tracking-tight">{{ $this->totalSleepToday }}</span>
                </div>
                @if($isSleeping)
                    <div class="px-3 py-1 bg-indigo-50 rounded-full border border-indigo-100">
                        <span class="text-[7px] font-black text-indigo-500 uppercase animate-pulse">In Progress</span>
                    </div>
                @endif
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

                <button onclick="Flux.modal('kid-sleep-modal').show()" 
                    @class([
                        'h-28 rounded-[1rem] p-4 flex flex-col justify-between transition-all active:scale-95 shadow-lg text-left',
                        'bg-indigo-600 shadow-indigo-600/30 text-white' => $isSleeping,
                        'bg-white dark:bg-zinc-900 border border-slate-100 dark:border-zinc-800' => !$isSleeping
                    ])>
                    <x-lucide-moon @class(['w-8 h-8', 'text-white' => $isSleeping, 'text-slate-400' => !$isSleeping]) />
                    <span class="font-black uppercase italic tracking-tighter leading-tight">Sleep<br>Protocol</span>
                </button>

                <button onclick="Flux.modal('kid-vaccine-modal').show()" 
                    class="bg-white border border-slate-100 h-28 rounded-[1rem] p-4 flex flex-col justify-between shadow-sm active:scale-95 transition-all text-left">
                    <x-lucide-shield-check class="w-8 h-8 text-indigo-400" />
                    <span class="font-black text-slate-400 uppercase italic tracking-tighter leading-tight">Vaccine<br>Intel</span>
                </button>

                <button onclick="Flux.modal('kid-reminder-modal').show()" 
                    class="bg-white border border-slate-100 h-28 rounded-[1rem] p-4 flex flex-col justify-between shadow-sm active:scale-95 transition-all text-left">
                    <x-lucide-bell class="w-8 h-8 text-amber-400" />
                    <span class="font-black text-slate-400 uppercase italic tracking-tighter leading-tight">Next Sync<br>Alarm</span>
                </button>
            </div>

            <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm">
                <h3 class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-400 mb-8 px-1 italic">Activity</h3>
                <div class="relative space-y-5">
                    <div class="absolute left-6 top-1 bottom-1 w-px bg-slate-50 dark:bg-zinc-800"></div>
                    @forelse($this->logs as $log)
                        <div wire:click="editLog({{ $log->id }})" class="flex items-center gap-4 relative z-9 cursor-pointer active:scale-[0.98] transition-all group" wire:key="log-{{ $log->id }}">
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
                                <p class="text-[10px] font-black text-slate-900 dark:text-white uppercase italic leading-tight group-hover:text-blue-600 transition-colors">{{ $log->data['label'] ?? ucfirst($log->category) }}</p>
                                <p class="text-[8px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">{{ $log->logged_at->format('g:i:s a') }} • {{ $log->logged_at->diffForHumans(short:true) }}</p>
                                @if(isset($log->data['notes']) && $log->data['notes'])
                                    <p class="text-[8px] text-slate-400 italic mt-1">{{ Str::limit($log->data['notes'], 40) }}</p>
                                @endif
                            </div>
                            <x-lucide-chevron-right class="w-3 h-3 text-slate-200 group-hover:text-blue-600" />
                        </div>
                    @empty
                        <p class="text-center py-4 text-slate-300 text-[9px] font-black uppercase italic tracking-[0.2em]">Silence in Sector 7</p>
                    @endforelse

                    @if($this->logs->count() >= $perPage)
                        <div class="flex justify-center pt-4">
                            <button wire:click="loadMore" class="text-[9px] font-black uppercase tracking-widest text-indigo-500 hover:text-indigo-600 transition-colors">Load More Intel +</button>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Modals (Profile, Feed, Diaper) -->
            <flux:modal name="kid-profile-modal" variant="flyout" side="bottom" class="space-y-6 !rounded-t-[2.5rem] !p-8">
                <div class="sheet-handle"></div>
                <h2 class="text-xl font-black uppercase italic tracking-tighter">Profile</h2>
                <div class="grid grid-cols-2 gap-3">
                    <flux:input wire:model="weight" label="Weight (kg)" type="number" step="0.1" />
                    <flux:input wire:model="height" label="Height (cm)" type="number" />
                    <flux:input wire:model="head" label="Head (cm)" type="number" />
                    <flux:input wire:model="bloodGroup" label="Blood Group" />
                </div>
                <flux:input wire:model="umbilicalFellAt" type="datetime-local" label="Umbilical Cord Fell Off" />
                <flux:button wire:click="saveProfile" @click="Flux.modal('kid-profile-modal').hide()" class="w-full h-14 bg-blue-600 text-white font-black uppercase italic !rounded-xl">Save Profile</flux:button>
            </flux:modal>

            <flux:modal name="kid-feed-modal" variant="flyout" side="bottom" class="!max-w-lg !rounded-t-[2.5rem] space-y-6 !p-8">
                <div class="sheet-handle"></div>
                <div class="text-center">
                    <h2 class="text-xl font-black uppercase italic tracking-tighter text-blue-600">Feeding</h2>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Log the last feed</p>
                </div>

                <div class="space-y-4">
                    <flux:input wire:model="feedTime" type="datetime-local" label="Time" />
                    
                    <div class="space-y-2">
                        <label class="text-[9px] font-black uppercase text-slate-400 ml-1">Next Feed In (Hours)</label>
                        <flux:input wire:model="nextFeedHours" type="number" class="!bg-slate-50 !border-none" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-[9px] font-black uppercase text-slate-400">Left Side (Scale 1-4)</label>
                            <div class="grid grid-cols-4 gap-1">
                                @foreach([1,2,3,4] as $v)
                                    <button wire:click="$set('leftBreastScale', {{ $v }})" @class(['py-3 rounded-lg font-black text-xs border transition-all', $leftBreastScale == $v ? 'bg-blue-600 border-blue-600 text-white' : 'bg-slate-50 border-slate-100 text-slate-400'])>{{ $v }}</button>
                                @endforeach
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[9px] font-black uppercase text-slate-400">Right Side (Scale 1-4)</label>
                            <div class="grid grid-cols-4 gap-1">
                                @foreach([1,2,3,4] as $v)
                                    <button wire:click="$set('rightBreastScale', {{ $v }})" @class(['py-3 rounded-lg font-black text-xs border transition-all', $rightBreastScale == $v ? 'bg-blue-600 border-blue-600 text-white' : 'bg-slate-50 border-slate-100 text-slate-400'])>{{ $v }}</button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottle Section -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-[9px] font-black uppercase text-slate-400">EBM (ml)</label>
                        <flux:input wire:model="ebmAmount" type="number" placeholder="0" class="!bg-slate-50 !border-none !text-xl !font-black" />
                    </div>
                    <div class="space-y-2">
                        <label class="text-[9px] font-black uppercase text-slate-400">Formula (ml)</label>
                        <flux:input wire:model="formulaAmount" type="number" placeholder="0" class="!bg-slate-50 !border-none !text-xl !font-black" />
                    </div>
                </div>

                <flux:button wire:click="saveFeeding" @click="Flux.modal('kid-feed-modal').hide()" class="w-full h-14 bg-blue-600 text-white font-black uppercase italic !rounded-xl shadow-xl shadow-blue-600/20">Execute Feed</flux:button>
            </flux:modal>

            <flux:modal name="kid-diaper-modal" variant="flyout" side="bottom" class="space-y-6 !rounded-t-[2.5rem] !p-8">
                <div class="sheet-handle"></div>
                <div class="text-center">
                    <h2 class="text-xl font-black uppercase italic tracking-tighter text-emerald-600">Diaper Change</h2>
                    
                    
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <button wire:click="$toggle('isWet')" @class(['py-6 rounded-2xl flex flex-col items-center gap-2 border-2 transition-all', $isWet ? 'bg-emerald-500 border-emerald-500 text-white' : 'bg-slate-50 border-slate-100 text-slate-400'])>
                        <x-lucide-droplets class="w-6 h-6" />
                        <span class="text-[10px] font-black uppercase">Wet</span>
                    </button>
                    <button wire:click="$toggle('isDirty')" @class(['py-6 rounded-2xl flex flex-col items-center gap-2 border-2 transition-all', $isDirty ? 'bg-emerald-500 border-emerald-500 text-white' : 'bg-slate-50 border-slate-100 text-slate-400'])>
                        <span class="text-[10px] font-black uppercase">Dirty</span>
                    </button>
                </div>

                @if($isWet)
                    <div class="space-y-2 animate-in slide-in-from-top-2 duration-300">
                        <label class="text-[9px] font-black uppercase text-slate-400 ml-1">Wet Load Scale</label>
                        <div class="grid grid-cols-4 gap-2">
                            @foreach([1=>['+', 'small'], 2=>['++', 'medium'], 3=>['+++', 'Large'], 4=>['++++', 'Explosive']] as $v => $info)
                                <button wire:click="$set('wetWeight', {{ $v }})" @class(['py-3 rounded-lg flex flex-col items-center gap-0.5 border-2 transition-all', $wetWeight == $v ? 'bg-emerald-500 border-emerald-500 text-white' : 'bg-slate-50 border-slate-100 text-slate-400'])>
                                    <span class="font-black text-xs leading-none">{{ $info[0] }}</span>
                                    <span class="text-[7px] font-black uppercase opacity-60">{{ $info[1] }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($isDirty)
                    <div class="space-y-4 animate-in slide-in-from-top-2 duration-300">
                        <div class="space-y-2">
                            <label class="text-[9px] font-black uppercase text-slate-400 ml-1">Stool Analysis</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button wire:click="$set('stoolType', 'yellow')" @class(['py-3 rounded-xl text-xs font-black border-2', $stoolType === 'yellow' ? 'bg-emerald-500 border-emerald-500 text-white' : 'bg-slate-50 border-slate-100 text-slate-400'])>💛 Yellow</button>
                                <button wire:click="$set('stoolType', 'green')" @class(['py-3 rounded-xl text-xs font-black border-2', $stoolType === 'green' ? 'bg-emerald-500 border-emerald-500 text-white' : 'bg-slate-50 border-slate-100 text-slate-400'])>💚 Green</button>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[9px] font-black uppercase text-slate-400 ml-1">Dirty Load Scale</label>
                            <div class="grid grid-cols-4 gap-2">
                                @foreach([1=>['+', 'small'], 2=>['++', 'medium'], 3=>['+++', 'Large'], 4=>['++++', 'Explosive']] as $v => $info)
                                    <button wire:click="$set('dirtyWeight', {{ $v }})" @class(['py-3 rounded-lg flex flex-col items-center gap-0.5 border-2 transition-all', $dirtyWeight == $v ? 'bg-emerald-500 border-emerald-500 text-white' : 'bg-slate-50 border-slate-100 text-slate-400'])>
                                        <span class="font-black text-xs leading-none">{{ $info[0] }}</span>
                                        <span class="text-[7px] font-black uppercase opacity-60">{{ $info[1] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                <flux:textarea wire:model="diaperNotes" placeholder="Observation notes..." rows="2" class="!bg-slate-50 !border-none" />
                
                <flux:button wire:click="saveDiaper" @click="Flux.modal('kid-diaper-modal').hide()" class="w-full h-14 bg-emerald-500 text-white font-black uppercase italic !rounded-xl shadow-xl shadow-emerald-500/20">Sync Status</flux:button>
            </flux:modal>

            <flux:modal name="kid-vaccine-modal" variant="flyout" side="bottom" class="space-y-6 !rounded-t-[2.5rem] !p-8">
                <div class="sheet-handle"></div>
                <h2 class="text-xl font-black uppercase italic tracking-tighter text-indigo-600">Vaccine Protocol</h2>
                <div class="grid grid-cols-2 gap-2">
                    @foreach(['BCG', 'HepB', 'DTP', 'Polio', 'Rotavirus'] as $v)
                        <button wire:click="logVaccine('{{ $v }}')" @click="Flux.modal('kid-vaccine-modal').hide()" class="py-4 bg-slate-50 rounded-xl text-[10px] font-black uppercase text-slate-500">{{ $v }}</button>
                    @endforeach
                </div>
            </flux:modal>

            <flux:modal name="kid-reminder-modal" variant="flyout" side="bottom" class="space-y-6 !rounded-t-[2.5rem] !p-8 text-center">
                <div class="sheet-handle"></div>
                <h2 class="text-xl font-black uppercase italic tracking-tighter text-amber-600">Next Sync Alarm</h2>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Set a reminder for the next critical event.</p>
                <div class="grid grid-cols-2 gap-2">
                    <button class="py-4 bg-slate-50 rounded-xl text-[10px] font-black uppercase text-slate-500">Next Feed</button>
                    <button class="py-4 bg-slate-50 rounded-xl text-[10px] font-black uppercase text-slate-500">Medicine</button>
                </div>
                <flux:button variant="ghost" @click="Flux.modal('kid-reminder-modal').hide()">Cancel</flux:button>
            </flux:modal>

            <flux:modal name="edit-log-modal" variant="flyout" side="bottom" class="space-y-6 !rounded-t-[2.5rem] !p-8">
                <div class="sheet-handle"></div>
                <div class="text-center">
                    <h2 class="text-xl font-black uppercase italic tracking-tighter text-blue-600">Edit Log</h2>
                </div>
                <div class="space-y-4">
                    <flux:input wire:model="editingLogLabel" label="Name" />
                    <flux:input wire:model="editingLogTime" type="datetime-local" label="Time" />
                    <flux:textarea wire:model="editingLogNotes" label="Notes" />
                </div>
                <div class="pt-4 space-y-3">
                    <flux:button wire:click="updateLog" @click="Flux.modal('edit-log-modal').hide()" class="w-full h-16 bg-blue-600 text-white font-black uppercase italic !rounded-2xl shadow-lg">Save Changes</flux:button>
                    <flux:button wire:click="deleteLog({{ $editingLogId }})" @click="Flux.modal('edit-log-modal').hide()" variant="ghost" class="w-full text-rose-500 font-black uppercase text-[10px] tracking-widest">Delete Log</flux:button>
                </div>
            </flux:modal>

            <flux:modal name="kid-growth-modal" variant="flyout" side="bottom" class="space-y-6 !rounded-t-[2.5rem] !p-8">
                <div class="sheet-handle"></div>
                <div class="text-center">
                    <h2 class="text-xl font-black uppercase italic tracking-tighter text-blue-600">Baby Measurements</h2>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="weight" label="Weight (kg)" type="number" step="0.1" />
                    <flux:input wire:model="height" label="Height (cm)" type="number" />
                    <flux:input wire:model="head" label="Head (cm)" type="number" />
                    <flux:input wire:model="bloodGroup" label="Blood Group" />
                </div>
                <div class="pt-4">
                    <flux:button wire:click="saveGrowth" @click="Flux.modal('kid-growth-modal').hide()" class="w-full h-16 bg-blue-600 text-white font-black uppercase italic !rounded-2xl shadow-lg">Save Measurements</flux:button>
                </div>
            </flux:modal>

            <flux:modal name="kid-sleep-modal" variant="flyout" side="bottom" class="space-y-6 !rounded-t-[2.5rem] !p-8">
                <div class="sheet-handle"></div>
                <div class="text-center">
                    <h2 class="text-xl font-black uppercase italic tracking-tighter text-indigo-600">Sleep Log</h2>
                    @if($isSleeping)
                        <div class="mt-2 py-1 px-3 bg-indigo-50 text-indigo-600 rounded-full inline-block text-[8px] font-black uppercase tracking-widest animate-pulse">Current Sleep Active</div>
                    @endif
                </div>
                
                <div class="space-y-4">
                    <flux:input wire:model="sleepStartTime" type="datetime-local" label="Sleep Started" />
                    <flux:input wire:model="sleepEndTime" type="datetime-local" label="Sleep Ended" />
                </div>

                <div class="space-y-3 pt-4">
                    <flux:button wire:click="logSleep" @click="Flux.modal('kid-sleep-modal').hide()" class="w-full h-16 bg-indigo-600 text-white font-black uppercase italic !rounded-2xl shadow-lg">Save Manual Log</flux:button>
                    <flux:button wire:click="toggleSleep" @click="Flux.modal('kid-sleep-modal').hide()" variant="ghost" class="w-full text-indigo-600 font-black uppercase italic tracking-widest">
                        {{ $isSleeping ? 'Stop Current Session' : 'Start Live Session' }}
                    </flux:button>
                </div>
            </flux:modal>
        </div>
        HTML;
    }
}