<?php

namespace App\Services\Notifications\Providers;

use App\Services\Notifications\Contracts\PushProviderInterface;
use App\Models\UserPushToken;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

class FirebasePushProvider implements PushProviderInterface
{
    private $messaging;

    public function __construct()
    {
        $credentials = config('services.firebase.credentials');

    if (!$credentials || !is_file($credentials)) {
        throw new \RuntimeException(
            'Firebase credentials file not found.'
        );
    }
        $factory = (new Factory)->withServiceAccount($credentials);
        $this->messaging = $factory->createMessaging();
    }

    public function send(string $deviceToken, string $title, string $body, array $data): ?string
    {
        $message = CloudMessage::new()
                    ->toToken($deviceToken)
                    ->withNotification(
                        FirebaseNotification::create($title, $body)
                    )
                    ->withData($data);

        try {
            $response = $this->messaging->send($message);
            return $response['name'] ?? null;
        } catch (\Kreait\Firebase\Exception\Messaging\InvalidDeviceToken $e) {
            $this->deactivateToken($deviceToken, 'InvalidDeviceToken');
            return null;
        } catch (\Kreait\Firebase\Exception\Messaging\NotFound $e) {
            $this->deactivateToken($deviceToken, 'TokenNotFound/Expired');
            return null;
        } catch (\Kreait\Firebase\Exception\Messaging\QuotaExceeded $e) {
            Log::error('Firebase Quota Exceeded: ' . $e->getMessage());
            return null;
        } catch (\Kreait\Firebase\Exception\Messaging\ServerUnavailable $e) {
            Log::error('Firebase Server Unavailable: ' . $e->getMessage());
            return null;
        } catch (\Kreait\Firebase\Exception\MessagingException $e) {
            Log::error('Firebase Messaging Error: ' . $e->getMessage());
            return null;
        } catch (\Exception $e) {
            Log::error('General Firebase Push Failed: ' . $e->getMessage());
            return null;
        }
    }

    private function deactivateToken(string $token, string $reason): void
    {
        $tokenHash = substr(sha1($token), 0, 8);
        Log::warning("Deactivating Firebase token [{$tokenHash}] due to {$reason}");
        
        UserPushToken::where('token', $token)
            ->where('is_active', true)
            ->update([
                'is_active'    => false,
                'last_used_at' => now(),
            ]);
    }

    public function name(): string
    {
        return 'firebase';
    }
}