<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\Client;
use App\Models\LoanManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\ClientNotificationMail;

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
 * As of this version, also attempts to reach the CLIENT (not just staff)
 * by SMS or email according to their own clients.preferred_notification_
 * channel, using $clientMessage (a shorter, client-facing version of the
 * event — the staff $title/$body are written for an internal audience and
 * aren't sent to the client verbatim). SMS goes out via SmsService using
 * the tenant's own configured gateway (Business Settings); email uses
 * whatever mail driver this app is configured with. Both are entirely
 * best-effort — a delivery failure (or no gateway configured) never
 * blocks the caller, same as the in-app notification above.
 */
class NotificationService
{
    public static function notify(
        int $managerId,
        string $type,
        string $title,
        ?string $body = null,
        ?int $clientId = null,
        ?string $url = null,
        ?string $clientMessage = null
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

        if ($clientId && $clientMessage) {
            self::notifyClient($managerId, $clientId, $title, $clientMessage);
        }
    }

    /**
     * SMS/email delivery to the client themselves, per their own
     * preferred_notification_channel ('sms', 'email', or 'none' — no
     * channel set at all defaults to attempting SMS, since that's the
     * most commonly reachable channel for MFI clients).
     */
    private static function notifyClient(int $managerId, int $clientId, string $subject, string $message): void
    {
        try {
            $client = Client::find($clientId);
            if (!$client) {
                return;
            }

            $channel = $client->preferred_notification_channel ?? 'sms';
            if ($channel === 'none') {
                return;
            }

            $tenant = LoanManager::find($managerId);
            if (!$tenant) {
                return;
            }

            if ($channel === 'email') {
                if ($client->email) {
                    Mail::to($client->email)->send(new ClientNotificationMail(
                        subjectLine: $subject,
                        greetingName: $client->name,
                        bodyText: $message,
                        companyName: $tenant->company_name ?? 'Your Loan Manager',
                    ));
                }
            } elseif ($client->phone_number) {
                SmsService::send($tenant, $client->phone_number, $message);
            }
        } catch (\Throwable $e) {
            Log::warning('Client notification delivery failed', [
                'loan_manager_id' => $managerId,
                'client_id' => $clientId,
                'error' => $e->getMessage(),
            ]);
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
