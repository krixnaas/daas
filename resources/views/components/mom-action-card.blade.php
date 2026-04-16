<flux:modal name="mom-activity-modal" class="space-y-6 !rounded-[2.5rem]">
    <div class="text-center"><h2 class="text-xl font-black uppercase italic">Log Activity</h2></div>
    <flux:input wire:model="activityText" label="Activity" placeholder="e.g. Walk, Shower" />
    <flux:textarea wire:model="activityNotes" label="Notes" placeholder="Optional notes..." />
    <flux:button wire:click="saveActivity" variant="primary" class="w-full h-16 rounded-3xl bg-slate-800 border-slate-800 uppercase font-black italic">Capture</flux:button>
</flux:modal>