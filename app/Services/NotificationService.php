<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\Client;
use App\Models\LoanManager;

/**
 * Shared helper for creating in-app notifications from loan/payment
 * lifecycle events. Fans a single event out to a small, relevant set of
 * recipients (the client's assigned officer, plus the tenant owner) rather
 * than broadcasting to every staff member — same "small, high-value set"
 * philosophy as ActivityLogObserver.
 *
 * Best-effort, same as JournalPoster/ActivityLogObserver: notification
 * failures must never break the underlying loan/payment operation they're
 * attached to, so every write is wrapped in a try/catch that silently
 * swallows errors.
 *
 * No SMS/email gateway is wired up yet — clients.preferred_notification_
 * channel is captured for that future, but delivery today is in-app only
 * (bell icon in the manager layout). Swap/extend the channel here once a
 * gateway (e.g. an SMS API) is chosen.
 */
class NotificationService
{
    public static function notify(
        int $managerId,
        string $type,
        string $title,
        ?string $body = null,
        ?int $clientId = null,
        ?string $url = null
    ): void {
        try {
            foreach (self::resolveRecipients($managerId, $clientId) as $userId) {
                AppNotification::create([
                    'loan_manager_id' => $managerId,
                    'user_id' => $userId,
                    'client_id' => $clientId,
                    'type' => $type,
                    'title' => $title,
                    'body' => $body,
                    'url' => $url,
                    'created_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            // Never let a notification failure block the operation it's
            // attached to.
        }
    }

    /**
     * The client's assigned officer (if any) plus the tenant owner,
     * deduplicated. Falls back to just the owner when there's no client
     * context or no officer assigned.
     *
     * @return array<int, int>
     */
    private static function resolveRecipients(int $managerId, ?int $clientId): array
    {
        $recipients = [];

        $manager = LoanManager::find($managerId);
        if ($manager && $manager->user_id) {
            $recipients[] = $manager->user_id;
        }

        if ($clientId) {
            $client = Client::find($clientId);
            if ($client && $client->assigned_user_id) {
                $recipients[] = $client->assigned_user_id;
            }
        }

        return array_values(array_unique($recipients));
    }
}
