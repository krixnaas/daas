<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\DadProfile;
use Illuminate\Support\Facades\DB;
use Native\Mobile\Facades\Haptic;

new #[Layout('layouts.app')] class extends Component {
    public $activeTabId = null;
    public $showSettings = false;
    public $tabConfigs = []; // For editing

    public function mount()
    {
        $this->tabConfigs = $this->getSafeTabs();
        if (!$this->activeTabId && count($this->tabConfigs) > 0) {
            $this->activeTabId = $this->tabConfigs[0]['id'];
        }
    }

    public function getSafeTabs(): array
    {
        $profile = auth()->user()->dadProfile;
        return $profile->tab_config ?? [['id' => 'mom', 'label' => 'Mom', 'type' => 'mom']];
    }

    public function setActiveTab($id)
    {
        $this->activeTabId = $id;
        if (class_exists(Haptic::class)) Haptic::impact('light');
    }

    public function saveTabSettings()
    {
        $profile = auth()->user()->dadProfile;
        $profile->update(['tab_config' => $this->tabConfigs]);
        $this->showSettings = false;
        if (class_exists(Haptic::class)) Haptic::success();
    }

    public function moveTab($index, $direction)
    {
        $newIndex = $direction === 'up' ? $index - 1 : $index + 1;
        if (isset($this->tabConfigs[$newIndex])) {
            $temp = $this->tabConfigs[$index];
            $this->tabConfigs[$index] = $this->tabConfigs[$newIndex];
            $this->tabConfigs[$newIndex] = $temp;
        }
    }
}; ?>

<div class="min-h-screen bg-slate-50 dark:bg-zinc-950 flex flex-col font-sans antialiased select-none">
    
    <nav class="bg-white dark:bg-zinc-900 px-3 py-2 flex items-center justify-between border-b border-slate-100 dark:border-zinc-800 sticky top-0 z-20">
        <div>
            <h1 class="text-2xl font-black italic tracking-tighter text-slate-900 dark:text-white uppercase">DAAS</h1>
            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-indigo-600">Dad as a service</p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="Flux.modal('camera-panel').show()"
                class="relative w-10 h-10 rounded-xl bg-slate-50 dark:bg-zinc-800 flex items-center justify-center text-slate-500 active:scale-95 transition-all">
                <x-lucide-camera class="w-5 h-5" />
            </button>
            <button onclick="Flux.modal('timer-panel').show()"
                class="relative w-10 h-10 rounded-xl bg-slate-50 dark:bg-zinc-800 flex items-center justify-center text-slate-500 active:scale-95 transition-all">
                <x-lucide-timer class="w-5 h-5" />
            </button>
            <button onclick="Flux.modal('tactical-settings').show()" class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-zinc-800 flex items-center justify-center text-slate-500">
                <x-lucide-layout-grid class="w-5 h-5" />
            </button>
        </div>
    </nav>

    @php $safeTabs = $this->getSafeTabs(); @endphp
    <div class="bg-white dark:bg-zinc-900 border-b border-slate-100 dark:border-zinc-800 overflow-x-auto no-scrollbar sticky top-[63px] z-10">
        <div class="flex px-2">
            @foreach($safeTabs as $tab)
                <button wire:click="setActiveTab('{{ $tab['id'] }}')"
                    class="px-4 py-2 text-[11px] font-black uppercase tracking-widest transition-all border-b-2 whitespace-nowrap flex items-center gap-2
                    {{ $activeTabId == $tab['id'] ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-400' }}">
                    <span>{{ $tab['type'] === 'mom' ? '👩' : '👶' }}</span>
                    {{ $tab['label'] }}
                </button>
            @endforeach
        </div>
    </div>

    <main class="flex-1">
        <div class="max-w-md mx-auto space-y-6">
            @php $currentTab = collect($safeTabs)->firstWhere('id', $activeTabId) ?? $safeTabs[0]; @endphp

            @if($currentTab['type'] === 'mom')
                <livewire:mom-view 
                    :profile-id="auth()->user()->dadProfile->id" 
                    :mom-name="$currentTab['label']" 
                    :key="'mom-view-'.$currentTab['id']"
                />
            @elseif($currentTab['type'] === 'expectant')
                <livewire:pregnancy-view 
                    :child-id="str_replace('child_', '', $activeTabId)" 
                    :key="'pregnancy-view-'.$currentTab['id']"
                />
            @else
                <livewire:kid-view 
                    :kid-id="str_replace('child_', '', $activeTabId)" 
                    :kid-name="$currentTab['label']" 
                    :key="'kid-view-'.$currentTab['id']"
                />
            @endif
        </div>
    </main>

    <livewire:timer-view />
    <livewire:photo-capture />

    <!-- Tactical Settings Modal -->
    <flux:modal name="tactical-settings" variant="flyout" position="bottom" class="!rounded-t-[2.5rem] !p-8 space-y-6">
        <div class="sheet-handle"></div>
        <div class="text-center">
            <h2 class="text-xl font-black uppercase italic tracking-tighter text-indigo-600">HQ Settings</h2>
            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mt-1">Manage Sector Deployment</p>
        </div>

        <div class="space-y-4">
            @foreach($tabConfigs as $index => $tab)
                <div class="bg-slate-50 p-4 rounded-2xl flex items-center gap-4 group">
                    <div class="flex flex-col gap-1">
                        <button wire:click="moveTab({{ $index }}, 'up')" @disabled($index === 0) class="text-slate-300 hover:text-indigo-600 disabled:opacity-30"><x-lucide-chevron-up class="w-4 h-4" /></button>
                        <button wire:click="moveTab({{ $index }}, 'down')" @disabled($index === count($tabConfigs) - 1) class="text-slate-300 hover:text-indigo-600 disabled:opacity-30"><x-lucide-chevron-down class="w-4 h-4" /></button>
                    </div>
                    <div class="flex-1">
                        <label class="text-[8px] font-black uppercase text-slate-400">Sector Name</label>
                        <input type="text" wire:model="tabConfigs.{{ $index }}.label" class="w-full bg-transparent border-none p-0 text-sm font-black uppercase italic text-slate-900 focus:ring-0" />
                    </div>
                    <div class="text-xl">{{ $tab['type'] === 'mom' ? '👩' : '👶' }}</div>
                </div>
            @endforeach
        </div>

        <div class="pt-4 space-y-3">
            <flux:button wire:click="saveTabSettings" @click="Flux.modal('tactical-settings').hide()" class="w-full h-16 bg-slate-800 text-white font-black uppercase italic !rounded-2xl">Sync Deployment</flux:button>
            <flux:button @click="Flux.modal('tactical-settings').hide()" variant="ghost" class="w-full">Cancel</flux:button>
        </div>
    </flux:modal>
</div>