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
    public $poopNotes = '';
    public $sleepStart, $sleepEnd;
    public $pumpSide = 'both', $pumpMl = '', $pumpDuration = '';

    public function mount($profileId, $momName = 'Mom', $isPostBirth = false)
    {
        $this->profileId = $profileId;
        $this->momName = $momName;
        $this->isPostBirth = $isPostBirth;
        $this->sleepStart = now()->format('Y-m-d H:i');
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
    /**
     * Intelligence Feed Data
     */
    #[Computed]
    public function logs()
    {
        return ActivityLog::where('subject_type', DadProfile::class)
            ->where('subject_id', $this->profileId)
            ->latest('logged_at')
            ->take(10)
            ->get();
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

    private function logActivity($category, $data) {
        ActivityLog::create([
            'dad_profile_id' => auth()->user()->dadProfile->id,
            'subject_id' => $this->profileId,
            'subject_type' => DadProfile::class,
            'category' => $category,
            'data' => $data,
            'logged_at' => now(),
        ]);
        if (class_exists(Haptic::class)) Haptic::success();
    }

    public function render()
    {
        return <<<'HTML'
        <div class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500 p-5 bg-[#F1F5F9]">
            
            <div class="flex items-center justify-between px-2">
                <div>
                    <h2 class="text-3xl font-black text-slate-900 dark:text-white italic tracking-tighter uppercase">{{ $momName }}</h2>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">Support Protocol</p>
                </div>
                @if($isPostBirth)
                    <span class="px-3 py-1 bg-emerald-50 text-emerald-600 text-[10px] font-black uppercase rounded-full border border-emerald-100">Post-Birth</span>
                @endif
            </div>

            @if($this->nextMedDue)
                <div class="flex items-center gap-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800/50 p-4 rounded-[2rem]">
                    <div class="w-8 h-8 rounded-full bg-amber-500 flex items-center justify-center shadow-lg shadow-amber-500/20">
                        <x-lucide-pill class="w-4 h-4 text-white" />
                    </div>
                    <span class="text-xs font-black text-amber-900 dark:text-amber-200 uppercase tracking-tight">{{ $this->nextMedDue }}</span>
                </div>
            @endif

            <div class="grid grid-cols-2 gap-4">
                <button onclick="Flux.modal('mom-med-modal').show()" class="bg-amber-400 p-6 rounded-[2.5rem] flex flex-col items-center gap-3 active:scale-95 transition-all">
                    <x-lucide-pill class="w-6 h-6 text-white" />
                    <span class="text-[10px] font-black uppercase text-white">Meds</span>
                </button>

                <button onclick="Flux.modal('mom-pump-modal').show()" class="bg-rose-500 p-6 rounded-[2.5rem] flex flex-col items-center gap-3 active:scale-95 transition-all">
                    <x-lucide-droplets class="w-6 h-6 text-white" />
                    <span class="text-[10px] font-black uppercase text-white">Pump</span>
                </button>

                <button onclick="Flux.modal('mom-activity-modal').show()" class="bg-slate-800 p-6 rounded-[2.5rem] flex flex-col items-center gap-3 active:scale-95 transition-all col-span-2">
                    <x-lucide-clipboard-list class="w-6 h-6 text-white" />
                    <span class="text-[10px] font-black uppercase text-white">Log Activity</span>
                </button>
            </div>

            <div class="bg-white dark:bg-zinc-900 rounded-[2.5rem] p-6 border border-slate-100 dark:border-zinc-800 shadow-sm">
                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-6 px-2 italic">Support Logs</h3>
                <div class="space-y-6">
                    @forelse($this->logs as $log)
                        <div class="flex items-center gap-4">
                            <div @class([
                                'w-10 h-10 rounded-xl flex items-center justify-center',
                                'bg-amber-50 text-amber-600' => $log->category === 'medicine',
                                'bg-rose-50 text-rose-600' => $log->category === 'pump',
                                'bg-slate-50 text-slate-600' => !in_array($log->category, ['medicine', 'pump']),
                            ])>
                                <x-dynamic-component :component="'lucide-' . ($log->category === 'medicine' ? 'pill' : ($log->category === 'pump' ? 'droplets' : 'clock'))" class="w-5 h-5"/>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $log->data['label'] ?? $log->category }}</p>
                                <p class="text-[10px] text-slate-400 uppercase font-medium tracking-wide">{{ $log->logged_at->format('g:i A') }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center opacity-30 italic text-[10px] uppercase font-black tracking-widest">No Intelligence Logged</div>
                    @endforelse
                </div>
            </div>

            <flux:modal name="mom-med-modal" class="space-y-6 !rounded-[2.5rem]">
                <div class="text-center"><h2 class="text-xl font-black uppercase italic">Log Medicine</h2></div>
                <flux:input wire:model="medName" label="Medicine Name" />
                <flux:input wire:model="medDose" label="Dose" />
                <flux:input wire:model="medNextHours" type="number" label="Next Dose In (Hours)" />
                <flux:button wire:click="saveMedicine" variant="primary" class="w-full h-16 rounded-3xl bg-amber-500 border-amber-500">Log Intake</flux:button>
            </flux:modal>

            <flux:modal name="mom-pump-modal" class="space-y-6 !rounded-[2.5rem]">
                <div class="text-center"><h2 class="text-xl font-black uppercase italic">Pump Session</h2></div>
                <div class="grid grid-cols-3 gap-2">
                    @foreach(['left', 'right', 'both'] as $side)
                        <button wire:click="$set('pumpSide', '{{ $side }}')" 
                            class="py-4 rounded-2xl font-black text-[10px] uppercase border-2 transition-all 
                            {{ $pumpSide === $side ? 'bg-rose-600 text-white border-rose-600' : 'bg-slate-50 dark:bg-zinc-800 border-transparent text-slate-400' }}">
                            {{ $side }}
                        </button>
                    @endforeach
                </div>
                <flux:input wire:model="pumpMl" type="number" label="Amount (ml)" />
                <flux:button wire:click="savePump" variant="primary" class="w-full h-16 rounded-3xl bg-rose-600 border-rose-600">Save Session</flux:button>
            </flux:modal>

            <flux:modal name="mom-activity-modal" class="space-y-6 !rounded-[2.5rem]">
                <div class="text-center"><h2 class="text-xl font-black uppercase italic">Log Activity</h2></div>
                <flux:input wire:model="activityText" label="Activity" placeholder="Shower, Walk, etc." />
                <flux:textarea wire:model="activityNotes" label="Notes" />
                <flux:button wire:click="saveActivity" variant="primary" class="w-full h-16 rounded-3xl bg-slate-800 border-slate-800">Save Activity</flux:button>
            </flux:modal>
        </div>
        HTML;
    }
}