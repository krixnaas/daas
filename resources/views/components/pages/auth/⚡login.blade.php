<?php

use App\Services\DeviceIdentity;
use Illuminate\Http\Client\ConnectionException;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Native\Laravel\Facades\Window;
use Native\Mobile\Browser;
use Native\Mobile\Facades\Browser as FacadesBrowser;
use Native\Mobile\Facades\Haptic;
use Native\Mobile\Facades\Haptics;

new #[Layout('layouts.guest')] class extends Component {
    public $email = '';
    public $password = '';
    public $remember = false;

    public function login()
    {
        $credentials = $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $this->remember)) {
            session()->regenerate();
            

            return redirect()->intended(route('selection'));
        }

        $this->addError('email', trans('auth.failed'));
    }

    public function login2()
    {
         $credentials = $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        $deviceInfo = app(DeviceIdentity::class)->getDeviceInfo(); 
        
        try
        {
            $response = Http::api()->post('/auth/login', [
                'email' => $credentials['email'],
                'password' => $credentials['password'],
                'device_name' => $deviceInfo['model']
            ]);

        }catch(ConnectionException){
            $this->addError('email', trans('auth.failed'));
            return;
        }

        if($response->successful() && $response->json('token')){
            session(['auth_token' => $response->json('token'), 'token_verified_at' => now()]);
            //event(new \Illuminate\Auth\Events\Registered($user));
            return $this->redirect(route('selection'), navigate:true);
        }
    }

    public function loginWithGoogle()
    {
        $credentials = $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        try{
            $response = Http::api()->get('/auth/google/redirect')   ;
            
        }catch(ConnectionException){
            $this->addError('email', trans('auth.failed'));
            return;
        }

        $url = $response->json('url'); 

        if(! $url) {
            $this -> addError('email', trans('auth.failed'));
            return;     
        }

        FacadesBrowser::auth($url); 
    }
}; ?>

<div class="relative min-h-screen flex items-center justify-center p-6 overflow-hidden bg-slate-50 dark:bg-zinc-950">
    <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-blue-500/5 blur-[120px] rounded-full"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-indigo-500/5 blur-[120px] rounded-full"></div>

    <div class="w-full max-w-[400px] z-10">
        <div class="text-center mb-10 space-y-2">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-slate-900 dark:bg-white rounded-2xl mb-4 shadow-xl shadow-slate-200 dark:shadow-none">
                <span class="text-white dark:text-black font-black italic text-2xl tracking-tighter">D</span>
            </div>
            <flux:heading size="xl" class="font-black tracking-tighter italic uppercase text-slate-900 dark:text-white">
                Dad As A Service
            </flux:heading>
            <flux:subheading class="text-slate-500 dark:text-zinc-400 font-medium">
                The high-performance toolkit for the modern dad.
            </flux:subheading>
        </div>

        <flux:card class="p-8 shadow-2xl shadow-slate-200/50 dark:shadow-none border-slate-200/60 dark:border-zinc-800/50">
            <form wire:submit="login" class="space-y-6">
                <flux:input 
                    wire:model="email" 
                    label="Work or Personal Email" 
                    type="email" 
                    placeholder="name@company.com"
                    icon="envelope"
                    required 
                    autofocus 
                />

                <div class="space-y-1">
                    <flux:input 
                        wire:model="password" 
                        label="Secure Password" 
                        type="password" 
                        placeholder="••••••••"
                        icon="key"
                        viewable
                        required 
                    />
                    <div class="flex justify-end">
                        <flux:link href="#" variant="subtle" size="sm" class="font-semibold">Forgot your password?</flux:link>
                    </div>
                </div>

                <div class="flex items-center py-2">
                    <flux:checkbox wire:model="remember" label="Keep me signed in" class="font-medium" />
                </div>

                <flux:button type="submit" variant="primary" class="w-full h-12 text-md font-bold shadow-lg shadow-blue-500/20">
                    Access Dashboard
                </flux:button>
            </form>
        </flux:card>

        <div class="mt-10 text-center">
            <flux:text size="sm" class="text-slate-400 font-medium">
                New to the platform? 
                <flux:link href="/register" wire:navigate class="text-slate-900 dark:text-white font-bold ml-1">Create an account</flux:link>
            </flux:text>
        </div>
    </div>
</div>