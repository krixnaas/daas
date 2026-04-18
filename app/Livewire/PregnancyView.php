<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ActivityLog;
use App\Models\Child;
use App\Models\Pregnancy; // Though children with status 'expectant' are used
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Native\Mobile\Facades\Haptic;

class PregnancyView extends Component
{
    public $childId;
    public $child;
    public $sizeType = 'fruit'; // fruit or animal
    public $perPage = 10;
    public $babyName, $birthDate, $birthType = 'Natural Labour';
    public $apptType = 'Doctor Visit', $apptDate, $apptNotes = '';
    public $medName, $medDose, $medNextHours;
    public $generalNotes = '';

    public function mount($childId)
    {
        $this->childId = $childId;
        $this->child = Child::findOrFail($childId);
        $this->babyName = $this->child->name;
        $this->birthDate = now()->format('Y-m-d\TH:i');
        $this->apptDate = now()->addDays(7)->format('Y-m-d\TH:i');
    }

    #[Computed]
    public function week()
    {
        $dueDate = Carbon::parse($this->child->due_date);
        $conceptionDate = $dueDate->copy()->subWeeks(40);
        $week = now()->diffInWeeks($conceptionDate);
        return max(1, min(40, $week));
    }

    #[Computed]
    public function sizeInfo()
    {
        $data = [
            'fruit' => [
                1 => ['Poppy Seed', 'dot'], 5 => ['Orange', 'citrus'], 10 => ['Prune', 'circle-dot'], 
                15 => ['Apple', 'apple'], 20 => ['Banana', 'cherry'], 
                25 => ['Cauliflower', 'flower-2'], 30 => ['Cabbage', 'leaf'], 35 => ['Honeydew', 'circle'], 
                40 => ['Watermelon', 'circle']
            ],
            'animal' => [
                1 => ['Ant', 'bug'], 5 => ['Ladybug', 'bug-play'], 10 => ['Gummy Bear', 'smile'], 
                15 => ['Squirrel', 'dog'], 20 => ['Kitten', 'cat'], 
                25 => ['Rabbit', 'rabbit'], 30 => ['Platypus', 'waves'], 35 => ['Small Dog', 'dog'], 
                40 => ['Giant Panda', 'dog']
            ]
        ];

        $week = $this->week;
        $activeSet = $data[$this->sizeType];
        $info = ['Small Item', 'package'];
        
        foreach ($activeSet as $w => $i) {
            if ($week >= $w) $info = $i;
        }

        return $info;
    }

    #[Computed]
    public function logs()
    {
        return ActivityLog::where('subject_id', $this->childId)
            ->where('subject_type', 'App\Models\Child')
            ->latest('logged_at')
            ->take($this->perPage)
            ->get();
    }

    #[Computed]
    public function appointments()
    {
        return ActivityLog::where('subject_id', $this->childId)
            ->where('subject_type', 'App\Models\Child')
            ->where('category', 'appointment')
            ->where('logged_at', '>=', now()->startOfDay())
            ->orderBy('logged_at', 'asc')
            ->get();
    }

    #[Computed]
    public function nextMedDue()
    {
        $lastMed = ActivityLog::where('subject_id', $this->childId)
            ->where('subject_type', 'App\Models\Child')
            ->where('category', 'medicine')
            ->latest('logged_at')
            ->first();

        if (!$lastMed || !isset($lastMed->data['next_hour'])) return null;

        $due = $lastMed->logged_at->addHours((int)$lastMed->data['next_hour']);
        return $due->isFuture() ? $due->format('g:i a') . ' (' . $due->diffForHumans() . ')' : 'OVERDUE: ' . $due->diffForHumans();
    }

    public function loadMore()
    {
        $this->perPage += 10;
    }

    public function saveAppointment()
    {
        ActivityLog::create([
            'dad_profile_id' => auth()->user()->dadProfile->id,
            'subject_id' => $this->childId,
            'subject_type' => 'App\Models\Child',
            'category' => 'appointment',
            'data' => [
                'label' => $this->apptType,
                'notes' => $this->apptNotes
            ],
            'logged_at' => $this->apptDate,
        ]);
        
        $this->reset(['apptNotes']);
        if (class_exists(Haptic::class)) Haptic::success();
    }

    public function saveMedicine()
    {
        ActivityLog::create([
            'dad_profile_id' => auth()->user()->dadProfile->id,
            'subject_id' => $this->childId,
            'subject_type' => 'App\Models\Child',
            'category' => 'medicine',
            'data' => [
                'label' => "Med: {$this->medName}",
                'dose' => $this->medDose,
                'next_hour' => $this->medNextHours
            ],
            'logged_at' => now(),
        ]);
        
        $this->reset(['medName', 'medDose', 'medNextHours']);
        if (class_exists(Haptic::class)) Haptic::success();
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

    public function toggleSize()
    {
        $this->sizeType = $this->sizeType === 'fruit' ? 'animal' : 'fruit';
        if (class_exists(Haptic::class)) Haptic::impact('light');
    }

    public function logActivity($label)
    {
        ActivityLog::create([
            'dad_profile_id' => auth()->user()->dadProfile->id,
            'subject_id' => $this->childId,
            'subject_type' => 'App\Models\Child',
            'category' => 'pregnancy_activity',
            'data' => [
                'label' => $label,
                'notes' => $this->generalNotes
            ],
            'logged_at' => now(),
        ]);
        
        $this->generalNotes = '';
        if (class_exists(Haptic::class)) Haptic::success();
    }

    public function startLabor()
    {
        $this->child->update(['labor_started_at' => now()]);
        $this->logActivity("Labor Strategic Phase Initiated");
        if (class_exists(Haptic::class)) Haptic::impact('heavy');
    }

    public function markBabyArrived()
    {
        $this->child->update([
            'status' => 'born', 
            'name' => $this->babyName,
            'date_of_birth' => $this->birthDate,
            'birth_type' => $this->birthType
        ]);
        
        $this->logActivity("Baby is Here! Birth Phase: {$this->birthType}");

        $profile = auth()->user()->dadProfile;
        $tabs = $profile->tab_config;
        
        // Update the current expectant tab to a specific child tab
        foreach($tabs as &$tab) {
            if ($tab['id'] === 'child_'.$this->childId) {
                $tab['type'] = 'child';
                $tab['label'] = $this->babyName;
            }
        }

        // DECOUPLE: If we are tracking mom and she doesn't have a tab yet, deploy it now.
        $hasMomTab = collect($tabs)->contains('type', 'mom');
        if ($profile->track_mom && !$hasMomTab) {
            array_unshift($tabs, [
                'id' => 'mom',
                'label' => $profile->partner_name ?: 'Mom',
                'type' => 'mom'
            ]);
        }

        $profile->update(['tab_config' => $tabs]);

        return redirect()->route('dashboard');
    }

    public function render()
    {
        return <<<'HTML'
        <div class="space-y-6 pb-24 animate-in fade-in duration-500 p-5 bg-[#F1F5F9]">
            <!-- Tracking Header -->
            <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm relative overflow-hidden">
                <div class="absolute -right-4 -bottom-4 opacity-10">
                    <x-lucide-calendar class="w-32 h-32 text-indigo-100" />
                </div>
                <div class="relative z-10 flex justify-between items-start">
                    <div>
                        <p class="text-[9px] font-black uppercase text-indigo-600 tracking-[0.2em] mb-1">Pregnancy Protocol</p>
                        <h2 class="text-2xl font-black text-slate-900 italic tracking-tighter uppercase leading-none">Week {{ $this->week }}</h2>
                    </div>
                    <div>
                        <p class="text-[9px] font-black uppercase text-indigo-600 tracking-[0.2em] mb-1">Expected Weight</p>
                        <p class="text-xs font-black text-slate-900 uppercase italic">{{ $child->expected_weight ?: 'TBD' }} kg</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[9px] font-black uppercase text-slate-400 tracking-widest">Due Date</p>
                        <p class="text-sm font-black text-slate-900 uppercase italic">{{ \Carbon\Carbon::parse($child->due_date)->format('M d, Y') }}</p>
                    </div>
                </div>
            </div>

            <!-- Size Comparison Card -->
            <div class="bg-indigo-600 rounded-[2.5rem] p-8 text-white shadow-xl shadow-indigo-600/20 relative overflow-hidden group active:scale-[0.98] transition-all" wire:click="toggleSize">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:scale-110 transition-transform">
                    <x-dynamic-component :component="'lucide-' . $this->sizeInfo[1]" class="w-24 h-24" />
                </div>
                <p class="text-[10px] font-black uppercase tracking-[0.3em] opacity-60 mb-2">Size Comparison</p>
                <h3 class="text-2xl font-black italic uppercase tracking-tighter mb-4">Baby is the size of a {{ $this->sizeInfo[0] }}</h3>
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 bg-white/20 rounded-full text-[9px] font-black uppercase tracking-widest">{{ $this->sizeType }} mode</span>
                    <span class="text-[9px] font-black uppercase opacity-60 tracking-widest">Tap to switch</span>
                </div>
            </div>

            <!-- Tactical Schedule -->
            @if($this->nextMedDue)
                        <p class="text-[8px] font-black text-amber-500 uppercase tracking-[0.2em] leading-none mb-1">Medication</p>
                        <span class="text-xs font-black text-slate-900 uppercase tracking-tight">{{ $this->nextMedDue }}</span>
                    </div>
                </div>
            @endif

            <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-900 italic leading-none">Schedule</h3>
                        <p class="text-[8px] font-bold text-slate-400 uppercase tracking-widest mt-1">Next Syncs</p>
                    </div>
                    <button onclick="Flux.modal('appt-modal').show()" class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600 active:scale-95 transition-all">
                        <x-lucide-calendar-plus class="w-4 h-4" />
                    </button>
                </div>

                <div class="space-y-4">
                    @forelse($this->appointments as $appt)
                        <div class="flex items-start gap-4 p-3 bg-slate-50 rounded-2xl relative overflow-hidden group">
                           <div class="w-10 h-10 rounded-xl bg-white flex flex-col items-center justify-center shadow-sm border border-slate-100 shrink-0">
                                <span class="text-[8px] font-black uppercase text-indigo-600 leading-none mb-0.5">{{ $appt->logged_at->format('M') }}</span>
                                <span class="text-sm font-black text-slate-900 leading-none">{{ $appt->logged_at->format('d') }}</span>
                           </div>
                           <div class="flex-1">
                                <h4 class="text-[11px] font-black text-slate-900 uppercase italic">{{ $appt->data['label'] }}</h4>
                                <p class="text-[8px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">{{ $appt->logged_at->format('g:i a') }}</p>
                                @if($appt->data['notes'])
                                    <p class="text-[9px] text-slate-500 mt-2 border-l-2 border-indigo-200 pl-2">{{ $appt->data['notes'] }}</p>
                                @endif
                           </div>
                        </div>
                    @empty
                        <div class="text-center py-6 opacity-30 italic text-[9px] uppercase font-black tracking-widest">No Intelligence Scheduled</div>
                    @endforelse
                </div>
            </div>

            <!-- Action Grid -->
            <div class="grid grid-cols-3 gap-3">
                <button onclick="Flux.modal('mom-activity-modal-preg').show()" class="bg-white border border-slate-100 h-24 rounded-3xl flex flex-col items-center justify-center gap-2 shadow-sm">
                    <x-lucide-activity class="w-6 h-6 text-slate-400" />
                    <span class="text-[10px] font-black uppercase text-slate-400">Log Activity</span>
                </button>

                <button onclick="Flux.modal('med-modal-preg').show()" class="bg-amber-400 h-24 rounded-3xl flex flex-col items-center justify-center gap-2 shadow-lg shadow-amber-400/20">
                    <x-lucide-pill class="w-6 h-6 text-white" />
                    <span class="text-[10px] font-black uppercase text-white">Log Meds</span>
                </button>
                <button class="bg-white border border-slate-100 h-24 rounded-3xl flex flex-col items-center justify-center gap-2 shadow-sm">
                    <x-lucide-camera class="w-6 h-6 text-slate-400" />
                    <span class="text-[10px] font-black uppercase text-slate-400">Bump Photo</span>
                </button>
                
                
            </div>
            <div class="grid grid-cols-1 gap-3">
                @if(!$this->child->labor_started_at)
                    <button wire:click="startLabor" class="col-span-2 bg-rose-500 h-16 rounded-2xl flex items-center justify-center gap-3 shadow-lg shadow-rose-500/20 active:scale-95 transition-all group">
                        <x-lucide-activity class="w-5 h-5 text-white animate-pulse" />
                        <span class="text-xs font-black uppercase text-white tracking-widest italic">Start Labor</span>
                    </button>
                @else
                    <div wire:poll.1s class="col-span-2 bg-rose-600 rounded-[2rem] p-6 text-center shadow-xl shadow-rose-500/30 border-2 border-white/20">
                        <p class="text-[10px] font-black text-rose-100 uppercase tracking-[0.3em] mb-3">Labor Active</p>
                        <div class="text-4xl font-black text-white italic tracking-tighter tabular-nums mb-4">
                            {{ now()->diff($this->child->labor_started_at)->format('%H:%I:%S') }}
                        </div>
                        <flux:button wire:click="$set('birthDate', '{{ now()->format('Y-m-d\TH:i') }}')" onclick="Flux.modal('labor-modal').show()" class="!bg-white !text-rose-600 w-full h-12 font-black uppercase italic tracking-widest rounded-xl">Baby Arrived</flux:button>
                    </div>
                @endif
            </div>

            <!-- Timeline -->
            <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm mt-2">
                <h3 class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-400 italic mb-6">Activity</h3>
                <div class="relative space-y-5">
                    <div class="absolute left-6 top-1 bottom-1 w-px bg-slate-50"></div>
                    @forelse($this->logs as $log)
                        <div wire:click="editLog({{ $log->id }})" class="flex items-center gap-4 relative z-10 cursor-pointer active:scale-95 transition-all group">
                            <div @class([
                                'w-10 h-10 rounded-xl flex items-center justify-center border-2 border-white',
                                'bg-amber-400 text-white' => $log->category === 'medicine',
                                'bg-indigo-600 text-white' => $log->category === 'appointment',
                                'bg-slate-50 text-slate-400' => $log->category !== 'medicine' && $log->category !== 'appointment'
                            ])>
                                @if($log->category === 'medicine') <x-lucide-pill class="w-4 h-4" />
                                @elseif($log->category === 'appointment') <x-lucide-calendar class="w-4 h-4" />
                                @else <x-lucide-check-circle-2 class="w-4 h-4" />
                                @endif
                            </div>
                            <div class="flex-1">
                                <p class="text-[10px] font-black text-slate-900 uppercase italic group-hover:text-indigo-600 transition-colors">{{ $log->data['label'] ?? 'Record Captured' }}</p>
                                <p class="text-[8px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">{{ $log->logged_at->format('g:i a') }} • {{ $log->logged_at->diffForHumans(short: true) }}</p>
                                @if(isset($log->data['notes']) || isset($log->data['dose']))
                                    <p class="text-[9px] text-slate-500 mt-2 border-l-2 border-indigo-200 pl-2">
                                        {{ $log->data['notes'] ?? '' }}
                                        {{ isset($log->data['dose']) ? "Dose: {$log->data['dose']}" : '' }}
                                    </p>
                                @endif
                            </div>
                            <x-lucide-chevron-right class="w-3 h-3 text-slate-200 group-hover:text-indigo-600" />
                        </div>
                    @empty
                        <p class="text-center py-4 text-slate-300 text-[9px] font-black uppercase italic tracking-[0.2em]">Silence in Sector 4</p>
                    @endforelse

                    @if($this->logs->count() >= $perPage)
                        <div class="flex justify-center pt-4">
                            <button wire:click="loadMore" class="text-[9px] font-black uppercase tracking-widest text-indigo-500 hover:text-indigo-600 transition-colors">Load More Intel +</button>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Modals -->
            <flux:modal name="mom-activity-modal-preg" variant="flyout" side="bottom" class="space-y-6 !rounded-t-[2.5rem] !p-8">
                <div class="sheet-handle"></div>
                <div class="text-center">
                    <h2 class="text-xl font-black uppercase italic tracking-tighter">Event Capture</h2>
                    <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest mt-1">Intelligence Gathering</p>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    @foreach(['Doctor Visit', 'Vitamin taken', 'Kick felt', 'Nursery update'] as $act)
                        <button wire:click="logActivity('{{ $act }}')" @click="Flux.modal('mom-activity-modal-preg').hide()" class="py-4 bg-slate-50 rounded-xl text-[10px] font-black uppercase text-slate-500 hover:bg-slate-100 transition-colors">{{ $act }}</button>
                    @endforeach
                </div>
                <flux:textarea wire:model="generalNotes" label="Notes" placeholder="Details..." class="!bg-slate-50 !border-none" />
            </flux:modal>

            <flux:modal name="labor-modal" variant="flyout" side="bottom" class="space-y-6 !rounded-t-[2.5rem] !p-8">
                <div class="sheet-handle"></div>
                <div class="text-center space-y-2">
                    <h2 class="text-2xl font-black uppercase italic tracking-tighter text-rose-600">Baby Arrived?</h2>
                    <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">Confirm arrival details.</p>
                </div>
                
                <div class="space-y-4">
                    <flux:input wire:model="babyName" label="Name" />
                    <flux:input wire:model="birthDate" type="datetime-local" label="Time" />
                    
                    <div class="space-y-2">
                        <label class="text-[9px] font-black uppercase text-slate-400 ml-1">Type of Birth</label>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach(['Natural Labour', 'C-Section'] as $type)
                                <button wire:click="$set('birthType', '{{ $type }}')" @class(['py-4 rounded-xl text-[10px] font-black uppercase border-2 transition-all', $birthType === $type ? 'bg-rose-500 border-rose-500 text-white' : 'bg-slate-50 border-transparent text-slate-400'])>{{ $type }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="space-y-3 pt-4">
                    <flux:button wire:click="markBabyArrived" class="w-full h-16 bg-rose-500 text-white font-black uppercase italic !rounded-2xl shadow-lg">Baby Arrived</flux:button>
                    <flux:button @click="Flux.modal('labor-modal').hide()" variant="ghost" class="w-full">Cancel</flux:button>
                </div>
            </flux:modal>

            <flux:modal name="appt-modal" variant="flyout" side="bottom" class="space-y-6 !rounded-t-[2.5rem] !p-8">
                <div class="sheet-handle"></div>
                <div class="text-center">
                    <h2 class="text-xl font-black uppercase italic tracking-tighter text-indigo-600">Appointment</h2>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Plan the next checkup.</p>
                </div>

                <div class="space-y-4">
                    <div class="space-y-2">
                        <label class="text-[9px] font-black uppercase text-slate-400 ml-1">Type</label>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach(['Doctor Visit', 'Midwife', 'Birthing Class'] as $type)
                                <button wire:click="$set('apptType', '{{ $type }}')" @class(['py-3 rounded-xl text-[9px] font-black uppercase border-2 transition-all', $apptType === $type ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-slate-50 border-transparent text-slate-400'])>{{ $type }}</button>
                            @endforeach
                        </div>
                    </div>
                    
                    <flux:input wire:model="apptDate" type="datetime-local" label="Time" />
                    <flux:textarea wire:model="apptNotes" label="Notes" placeholder="Questions for the doctor..." />
                </div>

                <div class="pt-4 space-y-3">
                    <flux:button wire:click="saveAppointment" @click="Flux.modal('appt-modal').hide()" class="w-full h-16 bg-indigo-600 text-white font-black uppercase italic !rounded-2xl shadow-lg">Save Appointment</flux:button>
                    <flux:button @click="Flux.modal('appt-modal').hide()" variant="ghost" class="w-full">Cancel</flux:button>
                </div>
            </flux:modal>

            <flux:modal name="med-modal-preg" variant="flyout" side="bottom" class="space-y-6 !rounded-t-[2.5rem] !p-8">
                <div class="sheet-handle"></div>
                <div class="text-center">
                    <h2 class="text-xl font-black uppercase italic tracking-tighter text-amber-500">Medicine</h2>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Log your vitamins or medicine.</p>
                </div>
                <div class="space-y-4">
                    <flux:input wire:model="medName" label="Medication Name" placeholder="e.g. Folic Acid" class="!bg-slate-50 !border-none" />
                    <flux:input wire:model="medDose" label="Dosage" placeholder="e.g. 400mcg" class="!bg-slate-50 !border-none" />
                    <flux:input wire:model="medNextHours" type="number" label="Next Sync Interval (Hours)" class="!bg-slate-50 !border-none" />
                </div>
                <div class="pt-4 space-y-3">
                    <flux:button wire:click="saveMedicine" @click="Flux.modal('med-modal-preg').hide()" class="w-full h-16 bg-amber-500 text-white font-black uppercase italic !rounded-2xl shadow-lg">Confirm Intake</flux:button>
                    <flux:button @click="Flux.modal('med-modal-preg').hide()" variant="ghost" class="w-full">Cancel</flux:button>
                </div>
            </flux:modal>
            <flux:modal name="edit-log-modal" variant="flyout" side="bottom" class="space-y-6 !rounded-t-[2.5rem] !p-8">
                <div class="sheet-handle"></div>
                <div class="text-center">
                    <h2 class="text-xl font-black uppercase italic tracking-tighter text-indigo-600">Edit Log</h2>
                </div>
                <div class="space-y-4">
                    <flux:input wire:model="editingLogLabel" label="Name" />
                    <flux:input wire:model="editingLogTime" type="datetime-local" label="Time" />
                    <flux:textarea wire:model="editingLogNotes" label="Notes" />
                </div>
                <div class="pt-4 space-y-3">
                    <flux:button wire:click="updateLog" @click="Flux.modal('edit-log-modal').hide()" class="w-full h-16 bg-indigo-600 text-white font-black uppercase italic !rounded-2xl shadow-lg">Save Changes</flux:button>
                    <flux:button wire:click="deleteLog({{ $editingLogId }})" @click="Flux.modal('edit-log-modal').hide()" variant="ghost" class="w-full text-rose-500 font-black uppercase text-[10px] tracking-widest">Delete Log</flux:button>
                </div>
            </flux:modal>
        </div>
        HTML;
    }
}