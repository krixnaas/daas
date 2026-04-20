<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ActivityLog;
use App\Models\DadProfile;
use App\Models\Child;
use Native\Mobile\Facades\Haptic;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;

class PhotoCapture extends Component
{
    public $profileId;

    public function mount()
    {
        $this->profileId = auth()->user()->dadProfile->id;
    }

    #[Computed]
    public function subjects(): array
    {
        $profile = auth()->user()->dadProfile;
        $list = [];

        $list[] = [
            'type'  => 'mom',
            'id'    => $profile->id,
            'name'  => $profile->partner_name ?? 'Mom',
            'label' => 'Recovery',
            'age'   => null,
        ];

        foreach ($profile->children as $child) {
            $age = null;
            if ($child->date_of_birth && !$child->date_of_birth->isFuture()) {
                $days = (int) now()->diffInDays($child->date_of_birth);
                if ($days < 7)         $age = $days . 'd old';
                elseif ($days < 30)    $age = $days . 'd old';
                elseif ($days < 365)   $age = floor($days / 7) . 'w old';
                else                   $age = floor($days / 365) . 'y ' . (floor(($days % 365) / 30)) . 'mo';
            }
            $list[] = [
                'type'  => 'child',
                'id'    => $child->id,
                'name'  => $child->name,
                'label' => $age ?? 'New Born',
                'age'   => $age,
            ];
        }

        return $list;
    }

    #[Computed]
    public function recentPhotos()
    {
        return ActivityLog::where('dad_profile_id', $this->profileId)
            ->where('category', 'photo')
            ->latest('logged_at')
            ->take(12)
            ->get()
            ->map(fn($log) => [
                'id'    => $log->id,
                'url'   => !empty($log->data['path']) ? Storage::disk('public')->url($log->data['path']) : null,
                'label' => $log->data['context_name'] ?? 'Photo',
                'time'  => Carbon::parse($log->logged_at)->format('M d'),
            ])
            ->filter(fn($p) => $p['url'])
            ->values();
    }

    #[Computed]
    public function daysSincePhoto(): ?int
    {
        $last = ActivityLog::where('dad_profile_id', $this->profileId)
            ->where('category', 'photo')
            ->latest('logged_at')
            ->first();

        return $last ? (int) Carbon::parse($last->logged_at)->diffInDays(now()) : null;
    }

    public function savePhoto(string $imageData, string $subjectType, int|string $subjectId, string $subjectName): void
    {
        $base64  = preg_replace('/^data:image\/\w+;base64,/', '', $imageData);
        $binary  = base64_decode($base64);
        $filename = 'photos/' . now()->format('Ymd_His') . '_' . uniqid() . '.jpg';

        Storage::disk('public')->put($filename, $binary);

        ActivityLog::create([
            'dad_profile_id' => $this->profileId,
            'subject_id'     => $subjectId,
            'subject_type'   => $subjectType === 'child' ? 'App\Models\Child' : DadProfile::class,
            'category'       => 'photo',
            'data'           => [
                'path'         => $filename,
                'context_name' => $subjectName,
                'label'        => 'Photo: ' . $subjectName,
            ],
            'logged_at' => now(),
        ]);

        if (class_exists(Haptic::class)) Haptic::success();
    }

    public function deletePhoto(int $id): void
    {
        $log = ActivityLog::where('id', $id)
            ->where('dad_profile_id', $this->profileId)
            ->where('category', 'photo')
            ->first();

        if ($log) {
            if (!empty($log->data['path'])) {
                Storage::disk('public')->delete($log->data['path']);
            }
            $log->delete();
        }
        if (class_exists(Haptic::class)) Haptic::impact('medium');
    }

    public function render()
    {
        return <<<'HTML'
        <div>
            <flux:modal name="camera-panel" variant="flyout" position="bottom" class="!rounded-t-[2.5rem] !p-0"
                x-data="{
                    step: 'gallery',
                    stream: null,
                    preview: null,
                    subject: null,
                    subjects: @json($this->subjects),

                    selectSubject(s) {
                        this.subject = s;
                        this.startCamera();
                    },

                    async startCamera() {
                        this.step = 'camera';
                        await this.$nextTick();
                        try {
                            this.stream = await navigator.mediaDevices.getUserMedia({
                                video: { facingMode: 'environment', width: { ideal: 1920 }, height: { ideal: 1080 } },
                                audio: false
                            });
                            const v = this.$refs.video;
                            v.srcObject = this.stream;
                            v.play();
                        } catch(e) {
                            this.$refs.fileInput.click();
                            this.step = 'gallery';
                        }
                    },

                    stopCamera() {
                        if (this.stream) {
                            this.stream.getTracks().forEach(t => t.stop());
                            this.stream = null;
                        }
                    },

                    capture() {
                        const v = this.$refs.video;
                        const w = v.videoWidth || 1280;
                        const h = v.videoHeight || 720;
                        const canvas = document.createElement('canvas');
                        canvas.width = w; canvas.height = h;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(v, 0, 0);
                        this.applyOverlay(ctx, w, h);
                        this.preview = canvas.toDataURL('image/jpeg', 0.80);
                        this.stopCamera();
                        this.step = 'preview';
                    },

                    fromFile(e) {
                        const file = e.target.files[0];
                        if (!file) return;
                        const reader = new FileReader();
                        reader.onload = ev => {
                            const img = new Image();
                            img.onload = () => {
                                const canvas = document.createElement('canvas');
                                canvas.width = img.width; canvas.height = img.height;
                                const ctx = canvas.getContext('2d');
                                ctx.drawImage(img, 0, 0);
                                this.applyOverlay(ctx, img.width, img.height);
                                this.preview = canvas.toDataURL('image/jpeg', 0.80);
                                this.step = 'preview';
                            };
                            img.src = ev.target.result;
                        };
                        reader.readAsDataURL(file);
                        e.target.value = '';
                    },

                    applyOverlay(ctx, w, h) {
                        const now   = new Date();
                        const date  = now.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                        const time  = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                        const name  = this.subject?.name ?? '';
                        const sub   = (this.subject?.age ?? this.subject?.label ?? '').toUpperCase();

                        // Bottom gradient
                        const grad = ctx.createLinearGradient(0, h * 0.65, 0, h);
                        grad.addColorStop(0, 'rgba(0,0,0,0)');
                        grad.addColorStop(1, 'rgba(0,0,0,0.72)');
                        ctx.fillStyle = grad;
                        ctx.fillRect(0, 0, w, h);

                        const fs  = Math.max(28, Math.floor(h * 0.058));
                        const pad = Math.floor(w * 0.045);

                        ctx.shadowColor = 'rgba(0,0,0,0.6)';
                        ctx.shadowBlur  = 10;

                        // Name — bottom left
                        ctx.textAlign  = 'left';
                        ctx.fillStyle  = '#ffffff';
                        ctx.font       = `900 italic ${fs}px system-ui,sans-serif`;
                        ctx.fillText(name, pad, h - Math.floor(h * 0.06));

                        // Age/label — below name
                        if (sub) {
                            ctx.font      = `700 ${Math.floor(fs * 0.52)}px system-ui,sans-serif`;
                            ctx.fillStyle = 'rgba(255,255,255,0.72)';
                            ctx.fillText(sub, pad, h - Math.floor(h * 0.025));
                        }

                        // Date + time — bottom right
                        ctx.textAlign  = 'right';
                        ctx.font       = `600 ${Math.floor(fs * 0.52)}px system-ui,sans-serif`;
                        ctx.fillStyle  = 'rgba(255,255,255,0.85)';
                        ctx.fillText(date, w - pad, h - Math.floor(h * 0.06));
                        ctx.fillText(time, w - pad, h - Math.floor(h * 0.025));

                        // DAAS — top right
                        ctx.textAlign  = 'right';
                        ctx.font       = `900 ${Math.floor(h * 0.024)}px system-ui,sans-serif`;
                        ctx.fillStyle  = 'rgba(255,255,255,0.38)';
                        ctx.shadowBlur = 0;
                        ctx.fillText('DAAS', w - pad, Math.floor(h * 0.055));
                    },

                    async save() {
                        if (!this.preview || !this.subject) return;
                        await $wire.savePhoto(this.preview, this.subject.type, this.subject.id, this.subject.name);
                        this.reset();
                    },

                    retake() {
                        this.preview = null;
                        this.startCamera();
                    },

                    reset() {
                        this.stopCamera();
                        this.preview  = null;
                        this.subject  = null;
                        this.step     = 'gallery';
                    }
                }"
            >
                {{-- Hidden file input fallback --}}
                <input type="file" x-ref="fileInput" accept="image/*" capture="environment"
                    class="hidden" x-on:change="fromFile($event)" />

                {{-- ── GALLERY STEP ───────────────────────── --}}
                <div x-show="step === 'gallery'" x-cloak class="p-8 space-y-6">
                    <div class="sheet-handle -mt-4 mb-0"></div>

                    <div class="flex items-start justify-between">
                        <div>
                            <h2 class="text-xl font-black uppercase italic tracking-tighter">Photos</h2>
                            @if($this->daysSincePhoto === null)
                                <p class="text-[10px] font-black uppercase tracking-widest text-rose-500 mt-1 animate-pulse">No photos yet — capture a moment!</p>
                            @elseif($this->daysSincePhoto >= 3)
                                <p class="text-[10px] font-black uppercase tracking-widest text-rose-500 mt-1">⚑ {{ $this->daysSincePhoto }} days since last photo</p>
                            @else
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mt-1">Last captured {{ $this->daysSincePhoto }}d ago</p>
                            @endif
                        </div>
                        <x-lucide-camera class="w-6 h-6 text-slate-200 mt-1" />
                    </div>

                    {{-- Subject picker --}}
                    <div class="space-y-2">
                        <p class="text-[9px] font-black uppercase tracking-widest text-slate-400">Who is this photo for?</p>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($this->subjects as $i => $s)
                                <button x-on:click="selectSubject(subjects[{{ $i }}])"
                                    class="flex items-center gap-3 p-4 bg-slate-50 rounded-2xl active:scale-[0.97] transition-all text-left border-2 border-transparent active:border-indigo-200">
                                    <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center shadow-sm flex-shrink-0">
                                        @if($s['type'] === 'mom')
                                            <x-lucide-heart class="w-5 h-5 text-rose-500" />
                                        @else
                                            <x-lucide-baby class="w-5 h-5 text-indigo-500" />
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[11px] font-black uppercase italic text-slate-800 truncate">{{ $s['name'] }}</p>
                                        <p class="text-[9px] font-bold uppercase tracking-widest text-slate-400">{{ $s['label'] }}</p>
                                    </div>
                                    <x-lucide-camera class="w-4 h-4 text-slate-300 ml-auto flex-shrink-0" />
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Recent photos --}}
                    @if($this->recentPhotos->isNotEmpty())
                        <div class="space-y-3">
                            <p class="text-[9px] font-black uppercase tracking-widest text-slate-400">Recent</p>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach($this->recentPhotos as $photo)
                                    <div wire:key="photo-{{ $photo['id'] }}"
                                        class="relative aspect-square rounded-2xl overflow-hidden bg-slate-100 group">
                                        <img src="{{ $photo['url'] }}"
                                            class="w-full h-full object-cover" loading="lazy" />
                                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/50 to-transparent p-1.5">
                                            <p class="text-[7px] font-black uppercase text-white tracking-widest">{{ $photo['time'] }}</p>
                                            <p class="text-[7px] text-white/70 truncate">{{ $photo['label'] }}</p>
                                        </div>
                                        <button wire:click="deletePhoto({{ $photo['id'] }})"
                                            class="absolute top-1.5 right-1.5 w-6 h-6 bg-black/50 rounded-full items-center justify-center hidden group-active:flex">
                                            <x-lucide-x class="w-3 h-3 text-white" />
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="py-6 text-center border-2 border-dashed border-slate-100 rounded-3xl">
                            <x-lucide-image class="w-10 h-10 text-slate-200 mx-auto mb-3" />
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-300">Gallery is empty</p>
                        </div>
                    @endif
                </div>

                {{-- ── CAMERA STEP ────────────────────────── --}}
                <div x-show="step === 'camera'" x-cloak
                    class="relative bg-black rounded-t-[2.5rem] overflow-hidden" style="min-height: 65dvh">
                    <video x-ref="video" autoplay playsinline muted
                        class="w-full object-cover rounded-t-[2.5rem]" style="min-height: 65dvh; max-height: 80dvh"></video>

                    {{-- Live overlay preview --}}
                    <div class="absolute bottom-0 left-0 right-0 px-6 pt-16 pb-8 bg-gradient-to-t from-black/75 to-transparent">
                        <div class="flex items-end justify-between mb-8">
                            <div>
                                <p x-text="subject?.name ?? ''"
                                    class="text-2xl font-black italic text-white uppercase leading-none drop-shadow-lg"></p>
                                <p x-text="(subject?.age ?? subject?.label ?? '').toUpperCase()"
                                    class="text-[10px] font-black text-white/70 uppercase tracking-widest mt-1"></p>
                            </div>
                            <p class="text-[10px] font-black text-white/40 uppercase tracking-widest">DAAS</p>
                        </div>

                        <div class="flex items-center justify-between">
                            <button x-on:click="reset()"
                                class="w-14 h-14 rounded-full bg-white/20 flex items-center justify-center active:scale-95 transition-all">
                                <x-lucide-x class="w-6 h-6 text-white" />
                            </button>

                            {{-- Shutter --}}
                            <button x-on:click="capture()"
                                class="w-20 h-20 rounded-full bg-white flex items-center justify-center shadow-2xl active:scale-95 transition-all">
                                <div class="w-16 h-16 rounded-full border-[3px] border-slate-300"></div>
                            </button>

                            {{-- Gallery fallback --}}
                            <button x-on:click="$refs.fileInput.click()"
                                class="w-14 h-14 rounded-full bg-white/20 flex items-center justify-center active:scale-95 transition-all">
                                <x-lucide-image class="w-6 h-6 text-white" />
                            </button>
                        </div>
                    </div>
                </div>

                {{-- ── PREVIEW STEP ───────────────────────── --}}
                <div x-show="step === 'preview'" x-cloak
                    class="relative bg-black rounded-t-[2.5rem] overflow-hidden">
                    <img x-bind:src="preview"
                        class="w-full object-contain rounded-t-[2.5rem]" style="max-height: 75dvh" />
                    <div class="absolute bottom-0 left-0 right-0 px-6 pb-8 pt-12 bg-gradient-to-t from-black/80 to-transparent">
                        <div class="flex gap-3">
                            <button x-on:click="retake()"
                                class="flex-1 h-14 rounded-2xl bg-white/20 text-white font-black uppercase italic text-sm active:scale-[0.98] transition-all border border-white/20">
                                Retake
                            </button>
                            <button x-on:click="save()"
                                class="flex-1 h-14 rounded-2xl bg-white text-slate-900 font-black uppercase italic text-sm active:scale-[0.98] transition-all shadow-xl">
                                Save Photo
                            </button>
                        </div>
                    </div>
                </div>

            </flux:modal>
        </div>
        HTML;
    }
}
