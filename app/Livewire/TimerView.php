<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ActivityLog;
use App\Models\DadProfile;
use Native\Mobile\Facades\Haptic;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;

class TimerView extends Component
{
    public $profileId;
    public $newTimerTitle = '';

    public function mount()
    {
        $this->profileId = auth()->user()->dadProfile->id;
    }

    #[Computed]
    public function activeTimers()
    {
        return ActivityLog::where('dad_profile_id', $this->profileId)
            ->where('category', 'timer')
            ->whereNull('data->ended_at')
            ->latest('logged_at')
            ->get()
            ->map(fn($log) => [
                'id'            => $log->id,
                'title'         => $log->data['title'] ?? 'Timer',
                'started_at'    => Carbon::parse($log->logged_at)->format('Y-m-d\TH:i:s'),
                'started_label' => Carbon::parse($log->logged_at)->format('g:i a'),
            ]);
    }

    #[Computed]
    public function completedTimers()
    {
        return ActivityLog::where('dad_profile_id', $this->profileId)
            ->where('category', 'timer')
            ->whereNotNull('data->ended_at')
            ->whereDate('logged_at', today())
            ->latest('logged_at')
            ->get()
            ->map(function ($log) {
                $sec = (int)($log->data['elapsed_seconds'] ?? 0);
                $h = floor($sec / 3600);
                $m = floor(($sec % 3600) / 60);
                $s = $sec % 60;
                return [
                    'id'            => $log->id,
                    'title'         => $log->data['title'] ?? 'Timer',
                    'duration'      => ($h > 0 ? $h.'h ' : '') . $m.'m ' . $s.'s',
                    'started_label' => Carbon::parse($log->logged_at)->format('g:i a'),
                    'ended_label'   => Carbon::parse($log->data['ended_at'])->format('g:i a'),
                ];
            });
    }

    public function startTimer()
    {
        $title = trim($this->newTimerTitle);
        if (!$title) return;

        ActivityLog::create([
            'dad_profile_id' => $this->profileId,
            'subject_id'     => $this->profileId,
            'subject_type'   => DadProfile::class,
            'category'       => 'timer',
            'data'           => ['title' => $title],
            'logged_at'      => now(),
        ]);

        $this->newTimerTitle = '';
        if (class_exists(Haptic::class)) Haptic::success();
    }

    public function stopTimer($id)
    {
        $log = ActivityLog::where('id', $id)
            ->where('dad_profile_id', $this->profileId)
            ->where('category', 'timer')
            ->first();

        if ($log) {
            $elapsed = Carbon::parse($log->logged_at)->diffInSeconds(now());
            $log->update([
                'data' => array_merge($log->data, [
                    'ended_at'        => now()->toDateTimeString(),
                    'elapsed_seconds' => $elapsed,
                ]),
            ]);
        }
        if (class_exists(Haptic::class)) Haptic::impact('medium');
    }

    public function deleteTimer($id)
    {
        ActivityLog::where('id', $id)
            ->where('dad_profile_id', $this->profileId)
            ->where('category', 'timer')
            ->delete();
        if (class_exists(Haptic::class)) Haptic::impact('medium');
    }

    public function render()
    {
        return <<<'HTML'
        <div>
            <flux:modal name="timer-panel" variant="flyout" position="bottom" class="!rounded-t-[2.5rem] !p-8 space-y-5">
                <div class="sheet-handle"></div>

                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-black uppercase italic tracking-tighter text-slate-900">Timers</h2>
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mt-0.5">
                            {{ $this->activeTimers->count() }} running
                        </p>
                    </div>
                    <div class="w-10 h-10 bg-indigo-50 rounded-xl flex items-center justify-center">
                        <x-lucide-timer class="w-5 h-5 text-indigo-600" />
                    </div>
                </div>

                {{-- Add New Timer --}}
                <div class="flex gap-3">
                    <input wire:model="newTimerTitle"
                           wire:keydown.enter="startTimer"
                           type="text"
                           placeholder="Timer name..."
                           class="flex-1 h-14 px-4 bg-slate-50 rounded-2xl border border-slate-100 text-sm font-black uppercase placeholder:text-slate-300 placeholder:font-medium placeholder:normal-case focus:ring-2 focus:ring-indigo-500 outline-none" />
                    <button wire:click="startTimer"
                        class="w-14 h-14 bg-indigo-600 rounded-2xl flex items-center justify-center shadow-lg shadow-indigo-600/20 active:scale-95 transition-all flex-shrink-0">
                        <x-lucide-play class="w-5 h-5 text-white" />
                    </button>
                </div>

                {{-- Active Timers --}}
                @if($this->activeTimers->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($this->activeTimers as $timer)
                            <div wire:key="timer-{{ $timer['id'] }}"
                                 x-data="{
                                     start: new Date('{{ $timer['started_at'] }}'),
                                     t: '0m 0s',
                                     init() {
                                         this.tick();
                                         setInterval(() => this.tick(), 1000);
                                     },
                                     tick() {
                                         const s = Math.max(0, Math.floor((new Date() - this.start) / 1000));
                                         const h = Math.floor(s / 3600);
                                         const m = Math.floor((s % 3600) / 60);
                                         const sec = s % 60;
                                         this.t = (h > 0 ? h + 'h ' : '') + m + 'm ' + String(sec).padStart(2,'0') + 's';
                                     }
                                 }" x-init="init()"
                                 class="bg-indigo-600 rounded-2xl p-5">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex-1 min-w-0 pr-3">
                                        <p class="text-[9px] font-black uppercase tracking-widest text-indigo-300">Running · since {{ $timer['started_label'] }}</p>
                                        <p class="text-base font-black uppercase italic text-white leading-tight mt-1 truncate">{{ $timer['title'] }}</p>
                                    </div>
                                    <button wire:click="stopTimer({{ $timer['id'] }})"
                                        class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center active:scale-95 transition-all flex-shrink-0">
                                        <x-lucide-square class="w-4 h-4 text-white fill-white" />
                                    </button>
                                </div>
                                <p x-text="t" class="text-4xl font-black tabular-nums text-white leading-none tracking-tight"></p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-4 text-center">
                        <x-lucide-timer class="w-10 h-10 text-slate-100 mx-auto mb-3" />
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-300">No active timers</p>
                        <p class="text-[9px] text-slate-300 mt-1">Type a name above and press play</p>
                    </div>
                @endif

                {{-- Completed Today --}}
                @if($this->completedTimers->isNotEmpty())
                    <div class="space-y-2 pt-2 border-t border-slate-100">
                        <p class="text-[9px] font-black uppercase tracking-widest text-slate-400">Completed Today</p>
                        @foreach($this->completedTimers as $timer)
                            <div wire:key="done-{{ $timer['id'] }}" class="flex items-center gap-3 bg-slate-50 rounded-2xl p-4">
                                <div class="flex-1 min-w-0">
                                    <p class="text-[11px] font-black uppercase italic text-slate-800 truncate">{{ $timer['title'] }}</p>
                                    <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">
                                        {{ $timer['started_label'] }} → {{ $timer['ended_label'] }}
                                    </p>
                                    <p class="text-[10px] font-black text-indigo-600 mt-0.5">{{ $timer['duration'] }}</p>
                                </div>
                                <button wire:click="deleteTimer({{ $timer['id'] }})"
                                    class="w-8 h-8 rounded-xl bg-white flex items-center justify-center text-rose-400 active:scale-95 transition-all flex-shrink-0 shadow-sm">
                                    <x-lucide-trash-2 class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif

            </flux:modal>
        </div>
        HTML;
    }
}
