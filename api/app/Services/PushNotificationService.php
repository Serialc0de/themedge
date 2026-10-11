<?php

namespace App\Services;

use App\Models\Article;
use App\Models\LiveBlog;
use App\Models\PushNotificationLog;
use App\Models\PushSubscription;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class PushNotificationService
{
    protected ?WebPush $webPush = null;
    protected ?string $initError = null;

    public function __construct()
    {
        $publicKey = config('services.vapid.public_key');
        $privateKey = config('services.vapid.private_key');
        $subject = config('services.vapid.subject', 'mailto:admin@themedge.com');

        if (! $publicKey || ! $privateKey) {
            $this->initError = 'VAPID keys mancanti';
            Log::warning('PushNotificationService: VAPID keys mancanti');
            return;
        }

        try {
            $auth = [
                'VAPID' => [
                    'subject' => $subject,
                    'publicKey' => $publicKey,
                    'privateKey' => $privateKey,
                ],
            ];

            // web-push v11: costruttore = (array $auth, array $defaultOptions = [], ?ClientInterface $client = null)
            // Se non passiamo un client PSR-18, la libreria ne crea uno di default
            $this->webPush = new WebPush($auth);
        } catch (\Throwable $e) {
            $this->initError = $e->getMessage();
            Log::error('PushNotificationService init failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function isReady(): bool
    {
        return $this->webPush !== null;
    }

    public function getInitError(): ?string
    {
        return $this->initError;
    }

    public function sendToAll(
        string $title,
        string $body,
        ?string $url = null,
        ?string $icon = null,
        string $type = 'custom',
        ?int $sentBy = null,
        ?Article $article = null,
        ?LiveBlog $liveBlog = null
    ): array {
        if (! $this->webPush) {
            return [
                'success' => 0,
                'failed' => 0,
                'total' => 0,
                'error' => 'WebPush non inizializzato' . ($this->initError ? ': ' . $this->initError : ''),
            ];
        }

        $subscriptions = PushSubscription::active()->get();

        // Log anche se 0 subscription
        $log = PushNotificationLog::create([
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'icon' => $icon ?? '/icon-192.png',
            'type' => $type,
            'article_id' => $article?->id,
            'live_blog_id' => $liveBlog?->id,
            'total_subscriptions' => $subscriptions->count(),
            'sent_by' => $sentBy,
        ]);

        if ($subscriptions->isEmpty()) {
            $log->update([
                'sent_count' => 0,
                'failed_count' => 0,
            ]);

            return [
                'success' => 0,
                'failed' => 0,
                'total' => 0,
                'log_id' => $log->id,
                'message' => 'Nessuna subscription attiva',
            ];
        }

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $url ?? '/',
            'icon' => $icon ?? '/icon-192.png',
            'badge' => '/badge-72.png',
            'tag' => 'themedge-' . $type . '-' . $log->id,
            'renotify' => true,
            'requireInteraction' => in_array($type, ['breaking', 'live_blog']),
            'data' => [
                'url' => $url ?? '/',
                'type' => $type,
                'log_id' => $log->id,
            ],
        ]);

        $sent = 0;
        $failed = 0;

        foreach ($subscriptions as $sub) {
            try {
                $subscription = Subscription::create([
                    'endpoint' => $sub->endpoint,
                    'publicKey' => $sub->public_key,
                    'authToken' => $sub->auth_token,
                    'contentEncoding' => $sub->content_encoding ?? 'aesgcm',
                ]);

                $report = $this->webPush->sendOneNotification($subscription, $payload);

                if ($report->isSuccess()) {
                    $sent++;
                    $sub->markAsUsed();
                } else {
                    $failed++;
                    $reason = $report->getReason();
                    Log::warning('Push failed', [
                        'endpoint' => substr($sub->endpoint, 0, 60),
                        'reason' => $reason,
                    ]);

                    if (
                        str_contains($reason, 'unsubscribed') ||
                        str_contains($reason, '410') ||
                        str_contains($reason, 'Gone')
                    ) {
                        $sub->deactivate();
                    }
                }
            } catch (\Throwable $e) {
                $failed++;
                Log::error('Push exception: ' . $e->getMessage(), [
                    'endpoint' => substr($sub->endpoint, 0, 60),
                ]);

                if (
                    str_contains($e->getMessage(), '410') ||
                    str_contains($e->getMessage(), 'unsubscribed') ||
                    str_contains($e->getMessage(), 'invalid')
                ) {
                    $sub->deactivate();
                }
            }
        }

        $log->update([
            'sent_count' => $sent,
            'failed_count' => $failed,
        ]);

        return [
            'success' => $sent,
            'failed' => $failed,
            'total' => $subscriptions->count(),
            'log_id' => $log->id,
        ];
    }

    public function sendBreakingNews(Article $article): array
    {
        return $this->sendToAll(
            '🚨 ULTIM\'ORA',
            $article->title,
            config('app.public_site_url', 'https://themedge.com') . '/articolo/' . $article->slug,
            $article->featured_image_url,
            'breaking',
            null,
            $article
        );
    }

    public function sendLiveBlogUpdate(LiveBlog $liveBlog, string $content): array
    {
        return $this->sendToAll(
            '🔴 ' . $liveBlog->title,
            strip_tags(substr($content, 0, 150)),
            config('app.public_site_url', 'https://themedge.com') . '/diretta/' . $liveBlog->slug,
            $liveBlog->featured_image_url,
            'live_blog',
            null,
            null,
            $liveBlog
        );
    }
}
