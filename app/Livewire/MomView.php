<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ActivityLog;
use App\Models\DadProfile;
use Native\Mobile\Facades\Haptic;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;

class MomView extends Component
{
    public $profileId;
    public $momName;
    public $isPostBirth = false;

    // Form States
    public $activityText = '', $activityNotes = '';
    public $medName = '', $medDose = '', $medNextHours = '', $medNotes = '';
    public $poopNotes = '', $poopType = 'normal';
    public $isSleeping = false, $sleepStartedAt;
    public $sleepStartTime, $sleepEndTime;
    public $pumpSide = 'both', $pumpMl = '', $pumpDuration = '';
    public $perPage = 10;

    // Form States - Reminder
    public $reminderLabel = 'Medical';
    public $reminderCustomLabel = '';
    public $reminderTime;
    public $reminderNotes = '';

    public function mount($profileId, $momName = 'Mom', $isPostBirth = false)
    {
        $this->profileId = $profileId;
        $this->momName = $momName;
        $this->isPostBirth = $isPostBirth;
        $this->sleepStartTime = now()->subHours(8)->format('Y-m-d\TH:i');
        $this->reminderTime = now()->addDay()->format('Y-m-d\TH:i');
        $this->sleepEndTime = now()->format('Y-m-d\TH:i');

        // Restore live sleep state from DB
        $openSleep = ActivityLog::where('subject_type', DadProfile::class)
            ->where('subject_id', $profileId)
            ->where('category', 'sleep')
            ->whereNull('data->end_time')
            ->latest()
            ->first();
        if ($openSleep) {
            $this->isSleeping = true;
            $this->sleepStartedAt = Carbon::parse($openSleep->logged_at)->format('Y-m-d\TH:i:s');
        }
    }

    /**
     * Medicine Alert Logic
     */
    #[Computed]
public function nextMedDue()
{
    $last = ActivityLog::where('subject_type', DadProfile::class)
        ->where('subject_id', $this->profileId)
        ->where('category', 'medicine')
        ->latest('logged_at')
        ->first();

    // Check if we have a log and if the specific key exists
    if (!$last || !isset($last->data['next_due_hours'])) return null;

    // Convert to int/float to satisfy Carbon's type safety
    $hours = (float) $last->data['next_due_hours'];
    
    // If the user left it blank or 0, we don't need a countdown
    if ($hours <= 0) return null;

    $nextDue = Carbon::parse($last->logged_at)->addHours($hours);
    
    if ($nextDue->isPast()) {
        return "{$last->data['med_name']} overdue!";
    }
    
    return "{$last->data['med_name']} due in " . $nextDue->diffForHumans(parts: 1, short: true);
}

    #[Computed]
    public function totalSleepToday()
    {
        $logs = ActivityLog::where('subject_id', $this->profileId)
            ->where('subject_type', DadProfile::class)
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
    /**
     * Intelligence Feed Data
     */
    #[Computed]
    public function logs()
    {
        return ActivityLog::where('subject_type', DadProfile::class)
            ->where('subject_id', $this->profileId)
            ->where('category', '!=', 'reminder')
            ->latest('logged_at')
            ->take($this->perPage)
            ->get();
    }

    public function loadMore()
    {
        $this->perPage += 10;
    }

    #[Computed]
    public function dailySummary()
    {
        $logs = ActivityLog::where('subject_type', DadProfile::class)
            ->where('subject_id', $this->profileId)
            ->where('logged_at', '>=', now()->startOfDay())
            ->get();

        return [
            'medicines'    => $logs->where('category', 'medicine')->count(),
            'pumps'        => $logs->where('category', 'pump')->count(),
            'total_pumped' => $logs->where('category', 'pump')->sum(fn($l) => (int)($l->data['amount'] ?? 0)),
            'activities'   => $logs->where('category', 'activity')->count(),
            'sleep_sessions' => $logs->where('category', 'sleep')->count(),
        ];
    }

    public function saveActivity() {
        $this->logActivity('activity', ['label' => $this->activityText, 'notes' => $this->activityNotes]);
        $this->reset(['activityText', 'activityNotes']);
        $this->dispatch('modal-close');
    }

    public function saveMedicine() {
        $this->logActivity('medicine', [
            'med_name' => $this->medName, 
            'dose' => $this->medDose, 
            'next_due_hours' => $this->medNextHours,
            'label' => "{$this->medName} · {$this->medDose}"
        ]);
        $this->reset(['medName', 'medDose', 'medNextHours']);
        $this->dispatch('modal-close');
    }

    public function savePump() {
        $this->logActivity('pump', [
            'side' => $this->pumpSide, 
            'amount' => $this->pumpMl, 
            'label' => "Pumped {$this->pumpMl}ml ({$this->pumpSide})"
        ]);
        $this->reset(['pumpMl', 'pumpDuration']);
        $this->dispatch('modal-close');
    }

    public function savePoop() {
        $this->logActivity('poop', [
            'type' => $this->poopType,
            'label' => "Poop Log ({$this->poopType})",
            'notes' => $this->poopNotes
        ]);
        $this->reset(['poopNotes']);
        $this->dispatch('modal-close');
    }

    #[Computed]
    public function sleepSessions()
    {
        return ActivityLog::where('subject_type', DadProfile::class)
            ->where('subject_id', $this->profileId)
            ->where('category', 'sleep')
            ->whereDate('logged_at', now()->toDateString())
            ->latest('logged_at')
            ->get()
            ->map(function ($log) {
                $start = Carbon::parse($log->logged_at);
                $end = isset($log->data['end_time']) ? Carbon::parse($log->data['end_time']) : null;
                $min = $end ? $start->diffInMinutes($end) : null;
                return [
                    'id'       => $log->id,
                    'start'    => $start->format('g:i a'),
                    'end'      => $end?->format('g:i a'),
                    'duration' => $min !== null ? floor($min / 60).'h '.($min % 60).'m' : null,
                    'active'   => !$end,
                ];
            });
    }

    public function toggleSleep() {
        if (!$this->isSleeping) {
            $now = now();
            $this->logActivity('sleep', [
                'label'      => 'Sleep Started',
                'start_time' => $now->toDateTimeString(),
            ]);
            $this->isSleeping = true;
            $this->sleepStartedAt = $now->format('Y-m-d\TH:i:s');
        } else {
            $log = ActivityLog::where('subject_type', DadProfile::class)
                ->where('subject_id', $this->profileId)
                ->where('category', 'sleep')
                ->whereNull('data->end_time')
                ->latest()
                ->first();

            if ($log) {
                $start = Carbon::parse($log->logged_at);
                $end = now();
                $min = $start->diffInMinutes($end);
                $log->update([
                    'data' => array_merge($log->data, [
                        'end_time'         => $end->toDateTimeString(),
                        'duration_minutes' => $min,
                        'label'            => 'Slept '.floor($min / 60).'h '.($min % 60).'m',
                    ]),
                ]);
            }
            $this->isSleeping = false;
            $this->sleepStartedAt = null;
        }
        if (class_exists(Haptic::class)) Haptic::impact('medium');
    }

    public function logSleep()
    {
        $start = Carbon::parse($this->sleepStartTime);
        $end = Carbon::parse($this->sleepEndTime);
        $min = $start->diffInMinutes($end);

        $this->logActivity('sleep', [
            'label'            => 'Slept '.floor($min / 60).'h '.($min % 60).'m',
            'start_time'       => $start->toDateTimeString(),
            'end_time'         => $end->toDateTimeString(),
            'duration_minutes' => $min,
        ], $start);
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
            $this->js("Flux.modal('edit-log-modal').show()");
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

    #[Computed]
    public function reminders()
    {
        return ActivityLog::where('subject_type', DadProfile::class)
            ->where('subject_id', $this->profileId)
            ->where('category', 'reminder')
            ->get()
            ->sortBy(fn($r) => $r->data['due_at'] ?? '9999')
            ->values();
    }

    public function saveReminder()
    {
        $label = $this->reminderLabel === 'Custom'
            ? trim($this->reminderCustomLabel)
            : $this->reminderLabel;

        if (!$label || !$this->reminderTime) return;

        $this->logActivity('reminder', [
            'label' => $label,
            'due_at' => $this->reminderTime,
            'notes' => $this->reminderNotes,
        ], $this->reminderTime);

        $this->reset(['reminderCustomLabel', 'reminderNotes']);
        $this->reminderLabel = 'Medical';
        $this->reminderTime = now()->addDay()->format('Y-m-d\TH:i');
    }

    public function deleteReminder($id)
    {
        ActivityLog::where('id', $id)->where('category', 'reminder')->delete();
        if (class_exists(Haptic::class)) Haptic::impact('medium');
    }

    private function logActivity($category, $data, $time = null) {
        ActivityLog::create([
            'dad_profile_id' => auth()->user()->dadProfile->id,
            'subject_id' => $this->profileId,
            'subject_type' => DadProfile::class,
            'category' => $category,
            'data' => $data,
            'logged_at' => $time ?: now(),
        ]);
        if (class_exists(Haptic::class)) Haptic::success();
    }

    public function render()
    {
        return <<<'HTML'
        <div class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500 p-5 bg-[#F1F5F9] pb-24"
             x-data="{
                 pendingModal: null,
                 tryOpen(modal) {
                     if ($wire.isSleeping) {
                         this.pendingModal = modal;
                         Flux.modal('sleep-interrupt-modal').show();
                     } else {
                         Flux.modal(modal).show();
                     }
                 },
                 async endSleepAndContinue() {
                     Flux.modal('sleep-interrupt-modal').hide();
                     await $wire.toggleSleep();
                     if (this.pendingModal) {
                         Flux.modal(this.pendingModal).show();
                         this.pendingModal = null;
                     }
                 }
             }">
            
            <div class="flex items-center justify-between px-2">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-white flex items-center justify-center shadow-sm border border-slate-100">
                        <x-lucide-heart class="w-6 h-6 text-rose-500" />
                    </div>
                    <div>
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white italic tracking-tighter uppercase leading-none">{{ $momName }}</h2>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-1">Status: {{ $isSleeping ? 'Recovering' : 'Operational' }}</p>
                    </div>
                </div>
                @if($isPostBirth)
                    <span class="px-3 py-1 bg-white text-emerald-600 text-[9px] font-black uppercase rounded-full shadow-sm border border-emerald-50">Post-Birth</span>
                @endif
            </div>

            @if($this->nextMedDue)
                <div class="flex items-center gap-3 bg-white border border-amber-100 p-4 rounded-3xl shadow-sm">
                    <div class="w-8 h-8 rounded-xl bg-amber-500 flex items-center justify-center shadow-lg shadow-amber-500/20">
                        <x-lucide-pill class="w-4 h-4 text-white" />
                    </div>
                    <div>
                        <p class="text-[8px] font-black text-amber-500 uppercase tracking-[0.2em] leading-none mb-1">Medication</p>
                        <span class="text-xs font-black text-slate-900 uppercase tracking-tight">{{ $this->nextMedDue }}</span>
                    </div>
                </div>
            @endif

            <div onclick="Flux.modal('mom-sleep-modal').show()" class="flex items-center gap-3 bg-white border border-indigo-100 p-4 rounded-3xl shadow-sm cursor-pointer active:scale-[0.98] transition-all">
                <div @class(['w-8 h-8 rounded-xl flex items-center justify-center shadow-lg', 'bg-indigo-600 shadow-indigo-500/20' => $isSleeping, 'bg-indigo-100' => !$isSleeping])>
                    <x-lucide-moon @class(['w-4 h-4', 'text-white' => $isSleeping, 'text-indigo-400' => !$isSleeping]) />
                </div>
                <div class="flex-1">
                    <p class="text-[8px] font-black text-indigo-600 uppercase tracking-[0.2em] leading-none mb-1">Sleep Today</p>
                    <span class="text-xs font-black text-slate-900 uppercase tracking-tight">{{ $this->totalSleepToday }}</span>
                </div>
                @if($isSleeping)
                    <div x-data="{
                        start: new Date('{{ $sleepStartedAt }}'),
                        t: '',
                        init() { setInterval(() => {
                            const s = Math.floor((new Date() - this.start) / 1000);
                            this.t = Math.floor(s/3600)+'h '+ Math.floor((s%3600)/60)+'m';
                        }, 1000); }
                    }" x-init="init()" class="text-right">
                        <p class="text-[7px] font-black text-indigo-500 uppercase animate-pulse leading-none">Live</p>
                        <p x-text="t" class="text-xs font-black text-indigo-600 tabular-nums mt-0.5"></p>
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-2 gap-3">
                <button @click="tryOpen('mom-med-modal')" class="bg-amber-400 h-28 rounded-3xl flex flex-col items-center justify-center gap-2 active:scale-95 transition-all shadow-lg shadow-amber-400/20">
                    <x-lucide-pill class="w-7 h-7 text-white" />
                    <span class="text-[10px] font-black uppercase text-white">Meds</span>
                </button>

                <button @click="tryOpen('mom-pump-modal')" class="bg-rose-500 h-28 rounded-3xl flex flex-col items-center justify-center gap-2 active:scale-95 transition-all shadow-lg shadow-rose-500/20">
                    <x-lucide-droplets class="w-7 h-7 text-white" />
                    <span class="text-[10px] font-black uppercase text-white">Pump</span>
                </button>

                <button wire:click="toggleSleep"
                    @class([
                        'h-28 rounded-3xl flex flex-col items-center justify-center gap-2 active:scale-95 transition-all shadow-lg',
                        'bg-indigo-600 shadow-indigo-600/20 text-white' => $isSleeping,
                        'bg-white text-slate-400 border border-slate-100' => !$isSleeping
                    ])>
                    <x-lucide-moon @class(['w-7 h-7', 'animate-pulse' => $isSleeping]) />
                    <span class="text-[10px] font-black uppercase">{{ $isSleeping ? 'Stop Sleep' : 'Sleep' }}</span>
                </button>

                <button @click="tryOpen('mom-poop-modal')" class="bg-white border border-slate-100 h-28 rounded-3xl flex flex-col items-center justify-center gap-2 active:scale-95 transition-all shadow-sm">
                    <x-lucide-trash-2 class="w-7 h-7 text-amber-800" />
                    <span class="text-[10px] font-black uppercase text-slate-400">Poop Dial</span>
                </button>

                <button @click="tryOpen('mom-activity-modal')" class="bg-slate-800 h-16 rounded-2xl flex items-center justify-center gap-3 active:scale-[0.98] transition-all col-span-2 shadow-xl shadow-slate-950/20">
                    <x-lucide-terminal class="w-5 h-5 text-emerald-400" />
                    <span class="text-[11px] font-black uppercase text-white italic">Log Activity</span>
                </button>
                <button onclick="Flux.modal('mom-reminder-modal').show()" class="bg-amber-500 h-16 rounded-2xl flex items-center justify-center gap-3 active:scale-[0.98] transition-all col-span-2 shadow-lg shadow-amber-500/20">
                    <x-lucide-bell class="w-5 h-5 text-white" />
                    <span class="text-[11px] font-black uppercase text-white italic">Set Reminder</span>
                </button>
            </div>

            <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm">
                <h3 class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-400 mb-8 px-1 italic">Activity</h3>
                <div class="space-y-6 relative">
                    <div class="absolute left-5 top-0 bottom-0 w-px bg-slate-50"></div>
                    @forelse($this->logs as $log)
                        <div wire:click="editLog({{ $log->id }})" class="flex items-center gap-4 relative z-10 cursor-pointer active:scale-[0.98] transition-all group">
                            <div @class([
                                'w-10 h-10 rounded-xl flex items-center justify-center shadow-sm border-2 border-white',
                                'bg-amber-400 text-white' => $log->category === 'medicine',
                                'bg-rose-500 text-white' => $log->category === 'pump',
                                'bg-indigo-600 text-white' => $log->category === 'sleep',
                                'bg-emerald-500 text-white' => $log->category === 'activity',
                                'bg-amber-800 text-white' => $log->category === 'poop',
                                'bg-slate-50 text-slate-400' => !in_array($log->category, ['medicine', 'pump', 'sleep', 'activity', 'poop']),
                            ])>
                                <x-dynamic-component :component="'lucide-' . ($log->category === 'medicine' ? 'pill' : ($log->category === 'pump' ? 'droplets' : ($log->category === 'sleep' ? 'moon' : ($log->category === 'poop' ? 'trash-2' : 'terminal'))))" class="w-4 h-4"/>
                            </div>
                            <div class="flex-1">
                                <p class="text-[11px] font-black text-slate-900 uppercase italic leading-none group-hover:text-rose-500 transition-colors">{{ $log->data['label'] ?? $log->category }}</p>
                                <p class="text-[8px] text-slate-400 uppercase font-black tracking-widest mt-1">{{ $log->logged_at->format('g:i a') }} • {{ $log->logged_at->diffForHumans(short: true) }}</p>
                            </div>
                            <x-lucide-chevron-right class="w-3 h-3 text-slate-200 group-hover:text-rose-500" />
                        </div>
                    @empty
                        <div class="py-8 text-center opacity-30 italic text-[9px] uppercase font-black tracking-widest">Awaiting Sector Intel</div>
                    @endforelse

                    @if($this->logs->count() >= $perPage)
                        <div class="flex justify-center pt-4">
                            <button wire:click="loadMore" class="text-[9px] font-black uppercase tracking-widest text-rose-500 hover:text-rose-600 transition-colors">Load More Intel +</button>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Daily Summary -->
            <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm">
                <h3 class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-400 mb-5 px-1 italic">Today's Summary</h3>
                <div class="grid grid-cols-3 gap-3">
                    <div class="bg-amber-50 rounded-2xl p-4 text-center">
                        <x-lucide-pill class="w-5 h-5 text-amber-500 mx-auto mb-2" />
                        <p class="text-xl font-black text-slate-900">{{ $this->dailySummary['medicines'] }}</p>
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Meds</p>
                    </div>
                    <div class="bg-rose-50 rounded-2xl p-4 text-center">
                        <x-lucide-droplets class="w-5 h-5 text-rose-500 mx-auto mb-2" />
                        <p class="text-xl font-black text-slate-900">{{ $this->dailySummary['pumps'] }}</p>
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Pumps</p>
                        @if($this->dailySummary['total_pumped'] > 0)
                            <p class="text-[8px] font-black text-rose-500 mt-0.5">{{ $this->dailySummary['total_pumped'] }}ml</p>
                        @endif
                    </div>
                    <div class="bg-indigo-50 rounded-2xl p-4 text-center">
                        <x-lucide-moon class="w-5 h-5 text-indigo-600 mx-auto mb-2" />
                        <p class="text-sm font-black text-slate-900 leading-none">{{ $this->totalSleepToday }}</p>
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mt-1">Sleep</p>
                    </div>
                </div>
            </div>

            <!-- Sleep Interrupt -->
            <flux:modal name="sleep-interrupt-modal" variant="flyout" position="bottom" class="!rounded-t-[2.5rem] !p-8 space-y-5">
                <div class="sheet-handle"></div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-indigo-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <x-lucide-moon class="w-8 h-8 text-indigo-600" />
                    </div>
                    <h2 class="text-xl font-black uppercase italic tracking-tighter text-indigo-600">Sleeping Now</h2>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">End sleep to log this activity?</p>
                </div>
                <button x-on:click="endSleepAndContinue()" class="w-full h-14 bg-indigo-600 rounded-2xl text-white font-black uppercase italic text-sm active:scale-[0.98] transition-all">
                    End Sleep & Continue
                </button>
                <button @click="Flux.modal('sleep-interrupt-modal').hide(); pendingModal = null;" class="w-full h-12 rounded-2xl text-slate-400 font-black uppercase text-[10px] tracking-widest">
                    Cancel
                </button>
            </flux:modal>

            <flux:modal name="mom-med-modal" variant="flyout" position="bottom" class="!max-w-lg !rounded-t-[2.5rem] space-y-4 !p-8">
                <div class="sheet-handle"></div>
                <div class="text-center"><h2 class="text-xl font-black uppercase italic tracking-tighter text-amber-500">Medicine</h2></div>
                <div class="space-y-4">
                    <flux:input wire:model="medName" label="Name" placeholder="e.g. Paracetamol" class="!bg-slate-50 !border-none" />
                    <flux:input wire:model="medDose" label="Dose" placeholder="e.g. 500mg" class="!bg-slate-50 !border-none" />
                    <flux:input wire:model="medNextHours" type="number" label="Next In (Hours)" class="!bg-slate-50 !border-none" />
                </div>
                <flux:button wire:click="saveMedicine" class="w-full h-16 rounded-2xl bg-amber-500 text-white font-black uppercase italic">Save Medicine</flux:button>
            </flux:modal>

            <flux:modal name="mom-pump-modal" variant="flyout" position="bottom" class="!max-w-lg !rounded-t-[2.5rem] space-y-4 !p-8">
                <div class="sheet-handle"></div>
                <div class="text-center"><h2 class="text-xl font-black uppercase italic tracking-tighter text-rose-500">Pump</h2></div>
                <div class="grid grid-cols-3 gap-2">
                    @foreach(['left', 'right', 'both'] as $side)
                        <button wire:click="$set('pumpSide', '{{ $side }}')" 
                            class="py-4 rounded-xl font-black text-[10px] uppercase border-2 transition-all 
                            {{ $pumpSide === $side ? 'bg-rose-600 text-white border-rose-600' : 'bg-slate-50 border-transparent text-slate-400' }}">
                            {{ $side }}
                        </button>
                    @endforeach
                </div>
                <flux:input wire:model="pumpMl" type="number" label="Amount (ml)" class="!bg-slate-50 !border-none" />
                <flux:button wire:click="savePump" class="w-full h-16 rounded-2xl bg-rose-600 text-white font-black uppercase italic">Save Pump</flux:button>
            </flux:modal>

            <flux:modal name="mom-poop-modal" variant="flyout" position="bottom" class="!max-w-lg !rounded-t-[2.5rem] space-y-4 !p-8">
                <div class="sheet-handle"></div>
                <div class="text-center"><h2 class="text-xl font-black uppercase italic tracking-tighter text-amber-800">Digestion Log</h2></div>
                <div class="grid grid-cols-2 gap-2">
                    @foreach(['normal', 'constipated', 'loose'] as $type)
                        <button wire:click="$set('poopType', '{{ $type }}')" 
                            class="py-4 rounded-xl font-black text-[10px] uppercase border-2 transition-all 
                            {{ $poopType === $type ? 'bg-amber-800 text-white border-amber-800' : 'bg-slate-50 border-transparent text-slate-400' }}">
                            {{ $type }}
                        </button>
                    @endforeach
                </div>
                <flux:textarea wire:model="poopNotes" label="Observation Notes" class="!bg-slate-50 !border-none" />
                <flux:button wire:click="savePoop" class="w-full h-16 rounded-2xl bg-amber-800 text-white font-black uppercase italic">Log Disposal</flux:button>
            </flux:modal>

            <flux:modal name="mom-activity-modal" variant="flyout" position="bottom" class="!max-w-lg !rounded-t-[2.5rem] space-y-4 !p-8">
                <div class="sheet-handle"></div>
                <div class="text-center"><h2 class="text-xl font-black uppercase italic tracking-tighter">Event Log</h2></div>
                <flux:input wire:model="activityText" label="Event Type" placeholder="Shower, Walk, Food, etc." class="!bg-slate-50 !border-none" />
                <flux:textarea wire:model="activityNotes" label="Metadata" class="!bg-slate-50 !border-none" />
                <flux:button wire:click="saveActivity" class="w-full h-16 rounded-2xl bg-slate-800 text-white font-black uppercase italic">Confirm Event</flux:button>
            </flux:modal>
            <flux:modal name="mom-sleep-modal" variant="flyout" position="bottom" class="!rounded-t-[2.5rem] !p-8 space-y-5">
                <div class="sheet-handle"></div>
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-black uppercase italic tracking-tighter text-indigo-600">Sleep</h2>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">Total today: {{ $this->totalSleepToday }}</p>
                    </div>
                </div>

                @if($isSleeping)
                    <div x-data="{
                        start: new Date('{{ $sleepStartedAt }}'),
                        t: '',
                        init() { setInterval(() => {
                            const s = Math.floor((new Date() - this.start) / 1000);
                            this.t = Math.floor(s/3600)+'h '+ Math.floor((s%3600)/60)+'m '+(s%60)+'s';
                        }, 1000); }
                    }" x-init="init()" class="bg-indigo-600 rounded-2xl p-5 text-center space-y-2">
                        <p class="text-[9px] font-black uppercase tracking-widest text-indigo-200">Started</p>
                        <p class="text-sm font-black uppercase italic text-white">{{ \Carbon\Carbon::parse($sleepStartedAt)->format('g:i a') }}</p>
                        <p x-text="t" class="text-3xl font-black tabular-nums text-white leading-none"></p>
                        <flux:button wire:click="toggleSleep" @click="Flux.modal('mom-sleep-modal').hide()" class="w-full mt-2 !bg-white !text-indigo-600 font-black uppercase italic !rounded-xl">Stop Sleep</flux:button>
                    </div>
                @else
                    <button wire:click="toggleSleep" @click="Flux.modal('mom-sleep-modal').hide()" class="w-full h-14 bg-indigo-600 rounded-2xl flex items-center justify-center gap-3 text-white font-black uppercase italic text-sm active:scale-[0.98] transition-all shadow-lg shadow-indigo-600/20">
                        <x-lucide-moon class="w-5 h-5" />
                        Start Sleep Now
                    </button>
                @endif

                @if($this->sleepSessions->isNotEmpty())
                    <div class="space-y-2">
                        <p class="text-[9px] font-black uppercase tracking-widest text-slate-400">Today's Sessions</p>
                        @foreach($this->sleepSessions as $session)
                            <div class="flex items-center gap-3 bg-slate-50 rounded-2xl p-3">
                                <div class="w-8 h-8 rounded-xl bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                    <x-lucide-moon class="w-3.5 h-3.5 text-indigo-600" />
                                </div>
                                <div class="flex-1">
                                    <p class="text-[11px] font-black uppercase text-slate-800">
                                        {{ $session['start'] }} → {{ $session['end'] ?? '...' }}
                                    </p>
                                    @if($session['duration'])
                                        <p class="text-[9px] text-indigo-600 font-bold uppercase tracking-widest mt-0.5">{{ $session['duration'] }}</p>
                                    @elseif($session['active'])
                                        <p class="text-[9px] text-indigo-500 font-bold uppercase tracking-widest mt-0.5 animate-pulse">In progress</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="space-y-3 border-t border-slate-100 pt-4">
                    <p class="text-[9px] font-black uppercase tracking-widest text-slate-400">Log Past Session</p>
                    <flux:input wire:model="sleepStartTime" type="datetime-local" label="Started" class="!bg-slate-50 !border-none" />
                    <flux:input wire:model="sleepEndTime" type="datetime-local" label="Ended" class="!bg-slate-50 !border-none" />
                    <flux:button wire:click="logSleep" @click="Flux.modal('mom-sleep-modal').hide()" class="w-full h-14 bg-slate-800 text-white font-black uppercase italic !rounded-xl">Save Entry</flux:button>
                </div>
            </flux:modal>

            <flux:modal name="edit-log-modal" variant="flyout" position="bottom" class="space-y-6 !rounded-t-[2.5rem] !p-8">
                <div class="sheet-handle"></div>
                <div class="text-center">
                    <h2 class="text-xl font-black uppercase italic tracking-tighter text-rose-500">Edit Log</h2>
                </div>
                <div class="space-y-4">
                    <flux:input wire:model="editingLogLabel" label="Name" />
                    <flux:input wire:model="editingLogTime" type="datetime-local" label="Time" />
                    <flux:textarea wire:model="editingLogNotes" label="Notes" />
                </div>
                <div class="pt-4 space-y-3">
                    <flux:button wire:click="updateLog" @click="Flux.modal('edit-log-modal').hide()" class="w-full h-16 bg-rose-500 text-white font-black uppercase italic !rounded-2xl shadow-lg">Save Changes</flux:button>
                    <flux:button wire:click="deleteLog({{ $editingLogId }})" @click="Flux.modal('edit-log-modal').hide()" variant="ghost" class="w-full text-rose-500 font-black uppercase text-[10px] tracking-widest">Delete Log</flux:button>
                </div>
            </flux:modal>

            <flux:modal name="mom-reminder-modal" variant="flyout" position="bottom" class="!rounded-t-[2.5rem] !p-8 space-y-5">
                <div class="sheet-handle"></div>
                <div>
                    <h2 class="text-xl font-black uppercase italic tracking-tighter text-amber-600">Reminders</h2>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Medical, appointments & alerts</p>
                </div>

                @if($this->reminders->isNotEmpty())
                    <div class="space-y-2">
                        @foreach($this->reminders as $reminder)
                            <div class="flex items-center gap-3 bg-amber-50 rounded-2xl p-3">
                                <div class="w-8 h-8 rounded-xl bg-amber-100 flex items-center justify-center flex-shrink-0">
                                    <x-lucide-bell class="w-3.5 h-3.5 text-amber-600" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-[11px] font-black uppercase italic text-slate-800 truncate">{{ $reminder->data['label'] }}</p>
                                    <p class="text-[9px] text-amber-600 font-bold uppercase tracking-widest mt-0.5">
                                        {{ \Carbon\Carbon::parse($reminder->data['due_at'])->format('M d · g:i A') }}
                                    </p>
                                    @if(!empty($reminder->data['notes']))
                                        <p class="text-[9px] text-slate-400 italic mt-0.5 truncate">{{ $reminder->data['notes'] }}</p>
                                    @endif
                                </div>
                                <button wire:click="deleteReminder({{ $reminder->id }})" class="w-8 h-8 rounded-xl bg-white flex items-center justify-center text-rose-400 active:scale-95 transition-all flex-shrink-0">
                                    <x-lucide-trash-2 class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        @endforeach
                    </div>
                    <div class="border-t border-slate-100 -mx-8 px-8 pt-5">
                        <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-3">Add New</p>
                    </div>
                @endif

                <div class="space-y-2">
                    <label class="text-[9px] font-black uppercase text-slate-400 ml-1">Type</label>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach(['Medical', 'Doctor Visit', 'Postnatal', 'Custom'] as $preset)
                            <button wire:click="$set('reminderLabel', '{{ $preset }}')"
                                @class(['py-3 rounded-xl text-[10px] font-black uppercase border-2 transition-all',
                                    'bg-amber-500 border-amber-500 text-white' => $reminderLabel === $preset,
                                    'bg-slate-50 border-transparent text-slate-500' => $reminderLabel !== $preset
                                ])>{{ $preset }}</button>
                        @endforeach
                    </div>
                </div>

                @if($reminderLabel === 'Custom')
                    <flux:input wire:model="reminderCustomLabel" label="Label" placeholder="e.g. Blood Test..." class="!bg-slate-50 !border-none" />
                @endif

                <flux:input wire:model="reminderTime" type="datetime-local" label="When" class="!bg-slate-50 !border-none" />
                <flux:textarea wire:model="reminderNotes" label="Notes (optional)" placeholder="Any details..." rows="2" class="!bg-slate-50 !border-none" />

                <flux:button wire:click="saveReminder" class="w-full h-14 bg-amber-500 text-white font-black uppercase italic !rounded-xl shadow-lg shadow-amber-500/20">Save Reminder</flux:button>
            </flux:modal>
        </div>
        HTML;
    }
}