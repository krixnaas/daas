<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ActivityLog;
use App\Models\DadProfile;
use Native\Mobile\Facades\Haptic;
use Illuminate\Support\Carbon;

class MomView extends Component
{
    public $profileId;
    public $momName;
    
    // Logging State for the Modals
    public $activityText = '';
    public $activityNotes = '';
    public $medName = '', $medDose = '', $medNextHours = '', $medNotes = '';
    public $pumpSide = 'both', $pumpMl = '', $pumpDuration = '';

    public function mount($profileId, $momName = 'Mom')
    {
        $this->profileId = $profileId;
        $this->momName = $momName;
    }

    /**
     * Medicine Alert logic (Matches your React Native "getNextMedDue")
     */
    public function getNextMedDueProperty()
    {
        $last = ActivityLog::where('subject_type', DadProfile::class)
            ->where('subject_id', $this->profileId)
            ->where('category', 'medicine')
            ->latest('logged_at')
            ->first();

        if (!$last || !isset($last->data['next_due_hours'])) return null;

        $nextDue = Carbon::parse($last->logged_at)->addHours($last->data['next_due_hours']);
        
        if ($nextDue->isPast()) return "{$last->data['med_name']} overdue!";
        return "{$last->data['med_name']} due in " . $nextDue->diffForHumans(parts: 1, short: true);
    }

    public function saveMedicine()
    {
        if (empty($this->medName)) return;

        $this->logActivity('medicine', [
            'med_name' => $this->medName,
            'dose' => $this->medDose,
            'next_due_hours' => $this->medNextHours,
            'label' => "{$this->medName} ({$this->medDose})"
        ]);

        $this->reset(['medName', 'medDose', 'medNextHours', 'medNotes']);
        $this->dispatch('modal-close');
    }

    public function savePump()
    {
        $this->logActivity('pump', [
            'side' => $this->pumpSide,
            'amount' => $this->pumpMl,
            'label' => "Pumped {$this->pumpMl}ml on {$this->pumpSide}"
        ]);

        $this->reset(['pumpMl', 'pumpDuration']);
        $this->dispatch('modal-close');
    }

    private function logActivity($category, $data)
    {
        ActivityLog::create([
            'dad_profile_id' => $this->profileId,
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
        $logs = ActivityLog::where('subject_type', DadProfile::class)
            ->where('subject_id', $this->profileId)
            ->latest('logged_at')
            ->take(5)
            ->get();

        return <<<'HTML'
        <div class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
            
            <div class="bg-white dark:bg-zinc-900 p-8 rounded-[1.5rem] border border-slate-100 dark:border-zinc-800 shadow-sm relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-10">
                    <x-lucide-heart class="w-20 h-20 text-rose-500" />
                </div>
                <span class="text-[10px] font-black text-rose-500 uppercase tracking-widest">Support Protocol</span>
                <h2 class="text-3xl font-black text-slate-900 dark:text-white mt-2 italic">{{ $momName }}</h2>
            </div>

            @if($this->next_med_due)
                <div class="flex items-center gap-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800/50 p-4 rounded-3xl">
                    <x-lucide-pill class="w-5 h-5 text-amber-500" />
                    <span class="text-xs font-bold text-amber-900 dark:text-amber-200">{{ $this->next_med_due }}</span>
                </div>
            @endif

            <div class="grid grid-cols-2 gap-4">
                <button onclick="$dispatch('open-modal', { name: 'mom-med-modal' })" 
                    class="bg-amber-400 shadow-lg shadow-amber-500/20 flex flex-col items-center gap-3 active:scale-95 transition-all">
                    <div class="w-12 h-12 bg-white/20 flex items-center justify-center">
                        <x-lucide-pill class="w-6 h-6 text-white" />
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-widest text-white">Meds</span>
                </button>

                <button onclick="$dispatch('open-modal', { name: 'mom-pump-modal' })"
                    class="bg-rose-500 shadow-lg shadow-rose-500/20 flex flex-col items-center gap-3 active:scale-95 transition-all">
                    <div class="w-12 h-12 bg-white/20 flex items-center justify-center">
                        <x-lucide-droplets class="w-6 h-6 text-white" />
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-widest text-white">Pump</span>
                </button>
            </div>

            <div class="bg-white dark:bg-zinc-900 rounded-[2.5rem] p-6 border border-slate-100 dark:border-zinc-800 shadow-sm">
                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-6 px-2">Recent Support</h3>
                <div class="space-y-6">
                    @forelse($logs as $log)
                        <div class="flex items-center gap-4">
                            <div @class([
                                'w-10 h-10 rounded-xl flex items-center justify-center',
                                'bg-amber-50 text-amber-600' => $log->category === 'medicine',
                                'bg-rose-50 text-rose-600' => $log->category === 'pump',
                            ])>
                                @if($log->category === 'medicine') <x-lucide-pill class="w-5 h-5"/> @else <x-lucide-droplets class="w-5 h-5"/> @endif
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-bold text-slate-900 dark:text-white capitalize">{{ $log->category }}</p>
                                <p class="text-[10px] text-slate-400 uppercase font-medium tracking-wide">
                                    {{ $log->data['label'] ?? 'Logged' }} • {{ $log->logged_at->format('g:i A') }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-[10px] font-black uppercase text-slate-300 py-4 tracking-widest">No Intelligence Gathered</p>
                    @endforelse
                </div>
            </div>

            <flux:modal name="mom-pump-modal" class="space-y-6">
                <div class="text-center"><h2 class="text-xl font-black uppercase italic tracking-tighter">Pump Session</h2></div>
                <div class="grid grid-cols-3 gap-2">
                    @foreach(['left', 'right', 'both'] as $side)
                        <button wire:click="$set('pumpSide', '{{ $side }}')" 
                            class="py-4 rounded-xl font-black text-[10px] uppercase tracking-widest border-2 transition-all 
                            {{ $pumpSide === $side ? 'bg-rose-600 text-white border-rose-600' : 'bg-slate-50 dark:bg-zinc-800 border-transparent text-slate-400' }}">
                            {{ $side }}
                        </button>
                    @endforeach
                </div>
                <flux:input wire:model="pumpMl" type="number" label="Amount (ml)" placeholder="80" />
                <flux:button wire:click="savePump" variant="primary" class="w-full h-14 rounded-2xl bg-rose-600 border-rose-600 uppercase font-black italic">Save Session</flux:button>
            </flux:modal>

            <flux:modal name="mom-med-modal" class="space-y-6">
                <div class="text-center"><h2 class="text-xl font-black uppercase italic tracking-tighter">Log Medicine</h2></div>
                <flux:input wire:model="medName" label="Medicine Name" placeholder="e.g. Paracetamol" />
                <flux:input wire:model="medDose" label="Dose" placeholder="e.g. 500mg" />
                <flux:input wire:model="medNextHours" type="number" label="Next Dose In (Hours)" />
                <flux:button wire:click="saveMedicine" variant="primary" class="w-full h-14 rounded-2xl bg-amber-500 border-amber-500 uppercase font-black italic">Confirm Log</flux:button>
            </flux:modal>

        </div>
        HTML;
    }
}