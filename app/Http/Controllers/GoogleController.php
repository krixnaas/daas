<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Socialite\Socialite;

class GoogleController extends Controller
{
    public function redirect(): JsonResponse
    {
        $url = Socialite::driver('google')->stateless()->redirect()->getTargetUrl();

        return response()->json([
            'url' => $url
        ]);
    }

    public function callback() : RedirectResponse
    {
        try{
        $googleUser = Socialite::driver('google')->stateless()->user();
        }catch(\Exception $e){
            return redirect('nativephp://auth/callback?error=1&message='.urlencode('Google login failed. Please try again.'));
        }

        $user = User::firstOrCreate([
            'google_id' => $googleUser->getId()],
            [
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'avatar' => $googleUser->getAvatar(),
            ]
        );

        $token = $user->creaToken('google')->plainTextToken;

        return redirect('nativephp://auth/callback?token='.$token);
        
    }
}
