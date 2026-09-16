<?php

namespace App\Services;

use App\Models\LoanManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends an SMS through whichever gateway a tenant has configured in
 * Business Settings (loan_managers.sms_provider/sms_api_key/etc). Every
 * tenant brings their own account — this app never ships with a shared
 * platform SMS account, so nothing sends until an MFI fills their own
 * credentials in.
 *
 * Supported providers:
 *   - 'africastalking': https://developers.africastalking.com/docs/sms/sending/bulk
 *   - 'twilio': https://www.twilio.com/docs/sms/send-messages
 *   - 'log' (or anything else/unconfigured): writes to the Laravel log
 *     instead of calling a real API — safe default for tenants who
 *     haven't set up SMS yet, and useful for testing the notification
 *     wiring without spending real SMS credit.
 *
 * Always best-effort: every public method catches its own errors and
 * returns false rather than throwing, so a failed/misconfigured SMS send
 * never breaks the loan/payment action that triggered it.
 */
class SmsService
{
    public static function send(LoanManager $tenant, string $toPhone, string $message): bool
    {
        $toPhone = self::normalizePhone($toPhone);

        if (!$toPhone) {
            return false;
        }

        try {
            return match ($tenant->sms_provider) {
                'africastalking' => self::sendViaAfricasTalking($tenant, $toPhone, $message),
                'twilio' => self::sendViaTwilio($tenant, $toPhone, $message),
                default => self::sendViaLog($tenant, $toPhone, $message),
            };
        } catch (\Throwable $e) {
            Log::warning('SMS send failed', [
                'loan_manager_id' => $tenant->id,
                'provider' => $tenant->sms_provider,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private static function sendViaAfricasTalking(LoanManager $tenant, string $toPhone, string $message): bool
    {
        if (!$tenant->sms_username || !$tenant->sms_api_key) {
            return false;
        }

        $response = Http::asForm()
            ->withHeaders([
                'apiKey' => $tenant->sms_api_key,
                'Accept' => 'application/json',
            ])
            ->post('https://api.africastalking.com/version1/messaging', array_filter([
                'username' => $tenant->sms_username,
                'to' => $toPhone,
                'message' => $message,
                'from' => $tenant->sms_sender_id,
            ]));

        return $response->successful();
    }

    private static function sendViaTwilio(LoanManager $tenant, string $toPhone, string $message): bool
    {
        if (!$tenant->sms_api_key || !$tenant->sms_api_secret || !$tenant->sms_sender_id) {
            // For Twilio: sms_api_key = Account SID, sms_api_secret = Auth
            // Token, sms_sender_id = the Twilio "from" number.
            return false;
        }

        $response = Http::asForm()
            ->withBasicAuth($tenant->sms_api_key, $tenant->sms_api_secret)
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$tenant->sms_api_key}/Messages.json", [
                'To' => $toPhone,
                'From' => $tenant->sms_sender_id,
                'Body' => $message,
            ]);

        return $response->successful();
    }

    private static function sendViaLog(LoanManager $tenant, string $toPhone, string $message): bool
    {
        Log::info('SMS (no gateway configured — logged instead of sent)', [
            'loan_manager_id' => $tenant->id,
            'to' => $toPhone,
            'message' => $message,
        ]);

        return true;
    }

    /**
     * Best-effort E.164-ish cleanup: strips spaces/dashes. Doesn't try to
     * guess a country code for a local-format number — a tenant whose
     * clients are stored as "0771234567" needs their SMS provider to
     * accept that format, or to re-save numbers with a country code.
     */
    private static function normalizePhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $cleaned = preg_replace('/[^\d+]/', '', $phone);

        return $cleaned ?: null;
    }
}
