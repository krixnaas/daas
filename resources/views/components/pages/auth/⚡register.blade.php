<?php

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Native\Mobile\Facades\Device;
use Native\Mobile\Facades\Haptic;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function register()
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        // $deviceInfo = $this->getDeviceInfo(); 
       
        // try{
        //     $response = Http::api()->post('/auth/register', [
        //         'name' => $validated['name'], 
        //         'email' => $validated['email'], 
        //         'password' => $validated['password'], 
        //         'device_name' => $deviceInfo['model'],
        //     ]);
        // }catch(ConnectionException){

        //     return 'Unable to connect. Please check your connection.'; 
        // }

        // if($response ->successful() && $response->json('token')){
        //     session(['auth_token' => $response->json('token'), 'token_verified_at' => now()]);

             $user = User::create($validated);
             event(new \Illuminate\Auth\Events\Registered($user));
        // }

        Auth::login($user);

        return $this->redirect(route('selection'), true);
    }
    
    public function register2()
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $deviceInfo = $this->getDeviceInfo(); 
       
        try{
            $response = Http::api()->post('/auth/register', [
                'name' => $validated['name'], 
                'email' => $validated['email'], 
                'password' => $validated['password'], 
                'device_name' => $deviceInfo['model'],
            ]);
        }catch(ConnectionException){

            return 'Unable to connect. Please check your connection.'; 
        }

        if($response ->successful() && $response->json('token')){
            session(['auth_token' => $response->json('token'), 'token_verified_at' => now()]);

            // $user = User::create($validated);
            // event(new \Illuminate\Auth\Events\Registered($user));
        }

        //Auth::login($user);

        return $this->redirect(route('selection'), true);
    }
   
}; 


?>

<div class="relative min-h-screen flex items-center justify-center p-6 bg-slate-50 dark:bg-zinc-950">
    <div class="w-full max-w-[400px] z-10">
        <div class="text-center mb-10 space-y-2">
            <flux:heading size="xl" class="font-black tracking-tighter italic uppercase">
                Join the Mission
            </flux:heading>
            <flux:subheading>Create your DAAS account to get started.</flux:subheading>
        </div>

        <flux:card class="p-8 shadow-2xl shadow-slate-200/50 dark:shadow-none border-slate-200/60 dark:border-zinc-800/50">
            <form wire:submit="register" class="space-y-6">
                <flux:input 
                    wire:model="name" 
                    label="Your Name" 
                    placeholder="e.g., Big Dog" 
                    icon="user"
                    required 
                    autofocus 
                />

                <flux:input 
                    wire:model="email" 
                    label="Email Address" 
                    type="email" 
                    placeholder="dad@example.com"
                    icon="envelope"
                    required 
                />

                <flux:input 
                    wire:model="password" 
                    label="Set Password" 
                    type="password" 
                    icon="lock-closed"
                    viewable
                    required 
                />

                <flux:input 
                    wire:model="password_confirmation" 
                    label="Confirm Password" 
                    type="password" 
                    icon="check-circle"
                    required 
                />

                <div class="pt-2">
                    <flux:button type="submit" variant="primary" class="w-full h-12 font-bold shadow-lg shadow-blue-500/20">
                        Create Account
                    </flux:button>
                </div>
            </form>
        </flux:card>

        <div class="mt-8 text-center">
            <flux:text size="sm" class="text-slate-500 font-medium">
                Already part of the team? 
                <flux:link href="/login" wire:navigate class="text-slate-900 dark:text-white font-bold ml-1">Sign In</flux:link>
            </flux:text>
        </div>
    </div>
</div>