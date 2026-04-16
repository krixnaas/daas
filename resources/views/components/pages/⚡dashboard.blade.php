<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\DadProfile;
use Illuminate\Support\Facades\DB;
use Native\Mobile\Facades\Haptic;

new #[Layout('layouts.app')] class extends Component {
    public $activeTabId = null;
    public $showSettings = false;
    
    // Logging Properties (Mixed from your RN logic)
    public $feedType = 'breast_left';
    public $breastScale = 4;
    public $feedAmount = '';
    public $nappyType = 'dirty'; // dirty or wet
    public $nappyAmount = 'medium'; // mapped to ++
    public $sleepStart;
    public $weight, $height, $head;

    public function mount()
    {
        $tabs = $this->getSafeTabs();
        if (!$this->activeTabId && count($tabs) > 0) {
            $this->activeTabId = $tabs[0]['id'];
        }
        $this->sleepStart = now()->format('Y-m-d H:i');
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

    // --- Save Methods ---

    public function saveFeed()
    {
        $this->logActivity('feed', [
            'method' => $this->feedType,
            'scale' => str_contains($this->feedType, 'breast') ? $this->breastScale : null,
            'amount' => !str_contains($this->feedType, 'breast') ? $this->feedAmount : null,
        ]);
        $this->dispatch('modal-close');
    }

    public function saveNappy()
    {
        $this->logActivity('nappy', [
            'type' => $this->nappyType,
            'level' => $this->nappyAmount,
        ]);
        $this->dispatch('modal-close');
    }

    private function logActivity($type, $meta)
    {
        $childId = str_replace('child_', '', $this->activeTabId);
        $profile = auth()->user()->dadProfile;
        
        $profile->children()->find($childId)->activityLogs()->create([
            'type' => $type,
            'meta' => $meta,
            'recorded_at' => now(),
        ]);

        if (class_exists(Haptic::class)) Haptic::success();
    }
}; ?>

<div class="min-h-screen bg-slate-50 dark:bg-zinc-950 flex flex-col font-sans antialiased select-none">
    
    <nav class="bg-white dark:bg-zinc-900 px-3 py-2 flex items-center justify-between border-b border-slate-100 dark:border-zinc-800 sticky top-0 z-20">
        <div>
            <h1 class="text-2xl font-black italic tracking-tighter text-slate-900 dark:text-white uppercase">DAAS</h1>
            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-indigo-600">Command Center</p>
        </div>
        <button wire:click="$set('showSettings', true)" class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-zinc-800 flex items-center justify-center text-slate-500">
            <x-lucide-settings-2 class="w-5 h-5" />
        </button>
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
            @else
                <livewire:kid-view 
                    :kid-id="str_replace('child_', '', $activeTabId)" 
                    :kid-name="$currentTab['label']" 
                    :key="'kid-view-'.$currentTab['id']"
                />
            @endif
        </div>
    </main>


</div>