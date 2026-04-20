<?php

namespace App\Services;

use Illuminate\Support\Str;
use Native\Mobile\Facades\Device;

class DeviceIdentity {
     public function getDeviceInfo(): array 
    {
        if(! $this->isNativeAvailable()){
            return [
                'model' => 'Unknown', 
                'os' => 'Unknown',
                'platform' => 'Unknown'
            ];
        }

        $info = Device::getInfo();
        
        if(is_array($info))
        {
            return [
                'model' => $info['model'] ?? 'Unknown',
                'os' => $info['os'] ?? 'Unknown',
                'platform' => $info['platform'] ?? 'Unknown'
            ];
        }
        
        if(is_string($info))
        {
            $decoded = json_decode($info, true);
            return [
                'model' => $decoded['model'] ?? 'Unknown',
                'os' => $decoded['os'] ?? 'Unknown',
                'platform' => $decoded['platform'] ?? 'Unknown'
            ];
        }
        return [
            'model' => 'Unknown', 
            'os' => 'Unknown', 
            'platform' => 'Unknown'
        ];
    }

    public function isNativeAvailable(): bool
    {
        return false; 
    }

    protected function resolveDeviceId(): string
    {
        if($this->isNativeAvailable()){
            $nativeId = Device::getId();
            if($nativeId)   return $nativeId;
        }
        return Str::uuid()->toString();
    }

    protected function ensureUsername(string $deviceId): void
    {
    }

}