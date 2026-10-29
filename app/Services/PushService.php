<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/** Sends a Web Push message to every device a user has turned notifications on for. */
class PushService
{
    public function enabled(): bool
    {
        return filled(config('talenthub.push.public_key')) && filled(config('talenthub.push.private_key'));
    }

    /** @param  array{title?:string,body?:string,url?:string}  $data */
    public function sendToUser(User $user, array $data): void
    {
        if (! $this->enabled()) {
            return;
        }

        $subscriptions = PushSubscription::where('user_id', $user->id)->get();
        if ($subscriptions->isEmpty()) {
            return;
        }

        try {
            $auth = ['VAPID' => [
                'subject' => config('talenthub.push.subject'),
                'publicKey' => config('talenthub.push.public_key'),
                'privateKey' => config('talenthub.push.private_key'),
            ]];

            $webPush = $this->makeWebPush($auth);

            $payload = json_encode([
                'title' => Str::limit((string) ($data['title'] ?? 'TalentHub'), 80),
                'body' => Str::limit((string) ($data['body'] ?? ''), 180),
                'url' => $data['url'] ?? '/',
            ]);

            foreach ($subscriptions as $s) {
                $webPush->queueNotification(Subscription::create([
                    'endpoint' => $s->endpoint,
                    'publicKey' => $s->public_key,
                    'authToken' => $s->auth_token,
                    'contentEncoding' => $s->content_encoding,
                ]), $payload);
            }

            foreach ($webPush->flush() as $report) {
                if ($report->isSubscriptionExpired()) {
                    PushSubscription::where('endpoint_hash', sha1($report->getEndpoint()))->delete();
                } elseif (! $report->isSuccess()) {
                    Log::info('Push not delivered', ['reason' => $report->getReason()]);
                }
            }
        } catch (\Throwable $e) {
            // Push is a bonus channel: it must never break the action that triggered it.
            Log::warning('Push failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Newer versions of the library take an HTTP client as the third argument, older ones a timeout in
     * seconds. Either way, never wait long for a push service: the user's request is waiting on us.
     */
    private function makeWebPush(array $auth): WebPush
    {
        $param = (new \ReflectionClass(WebPush::class))->getConstructor()?->getParameters()[2] ?? null;

        if ($param && str_contains((string) $param->getType(), 'ClientInterface')) {
            return new WebPush($auth, [], new \GuzzleHttp\Client(['timeout' => 5, 'connect_timeout' => 3]));
        }

        return new WebPush($auth, [], 5);
    }
}