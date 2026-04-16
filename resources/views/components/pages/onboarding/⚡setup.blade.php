<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Native\Mobile\Facades\Haptic;

new #[Layout('layouts.app')] class extends Component {
    public $journeyType;
    
    // Expectant Dad Fields
    public $dueDate;
    public $partnerName;

    // Existing Dad Fields
    public $childName;
    public $childDob;

    public function mount()
    {
        $this->journeyType = session('selected_journey');

        // Safety check: if no journey selected, send them back
        if (!$this->journeyType) {
            return redirect()->route('selection');
        }
    }

    public function completeSetup()
    {
        $this->validate($this->rules());

        // 1. Create the Dad Profile
        $profile = auth()->user()->dadProfile()->create([
            'type' => $this->journeyType,
            // DAAS Logic: Generate tab_config JSON based on journey
            'tab_config' => $this->generateTabConfig(), 
        ]);

        // 2. Create the associated journey record
        if ($this->journeyType === 'expectant') {
            $profile->pregnancies()->create([
                'due_date' => $this->dueDate,
                'partner_name' => $this->partnerName,
            ]);
        } else {
            $profile->children()->create([
                'name' => $this->childName,
                'date_of_birth' => $this->childDob,
            ]);
        }

        if (class_exists(Haptic::class)) Haptic::success();

        return redirect()->route('dashboard');
    }

    protected function rules() {
        return $this->journeyType === 'expectant' 
            ? ['dueDate' => 'required|date', 'partnerName' => 'nullable|string']
            : ['childName' => 'required|string', 'childDob' => 'required|date'];
    }

    protected function generateTabConfig() {
        // Polymorphic logic: expectant dads get pregnancy trackers, existing dads get activity logs
        return $this->journeyType === 'expectant' 
            ? ['milestones', 'prep', 'countdown'] 
            : ['sleep', 'feed', 'meds', 'growth'];
    }
}; ?>

<div class="max-w-xl mx-auto px-6 py-12">
    <div class="mb-10">
        <flux:heading size="xl" class="font-black italic uppercase tracking-tighter">
            {{ $journeyType === 'expectant' ? 'Mission: Preparation' : 'Mission: Management' }}
        </flux:heading>
        <flux:subheading>Let's configure your DAAS dashboard for the road ahead.</flux:subheading>
    </div>

    <flux:card>
        <form wire:submit="completeSetup" class="space-y-6">
            
            @if($journeyType === 'expectant')
                <flux:input wire:model="dueDate" type="date" label="When is the big day? (Due Date)" />
                <flux:input wire:model="partnerName" label="Partner's Name (Optional)" placeholder="e.g. Sarah" />
            @else
                <flux:input wire:model="childName" label="Child's Name" placeholder="e.g. Jack" />
                <flux:input wire:model="childDob" type="date" label="Date of Birth" />
            @endif

            <div class="pt-4">
                <flux:button type="submit" variant="primary" class="w-full h-12">
                    Initialize Dashboard
                </flux:button>
            </div>
        </form>
    </flux:card>

    <div class="mt-8 text-center">
        <flux:button variant="subtle" size="sm" icon="arrow-uturn-left" href="{{ route('selection') }}" wire:navigate>
            Change Journey Type
        </flux:button>
    </div>
</div>