<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\JournalEntry;
use App\Models\Loan;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Generic audit-trail observer, registered (in AppServiceProvider::boot())
 * on a deliberately small set of models where "who changed this and when"
 * matters for compliance: Loan, Client, Payment, JournalEntry. Not
 * registered on high-frequency ledger rows (MfiTransaction,
 * JournalEntryLine) or on models updated via increment()/decrement()
 * (Eloquent's increment/decrement issue a raw query-builder UPDATE and
 * don't fire model events at all, so those never reach this observer
 * regardless — savings/shares/FD balance changes stay visible through
 * their own transaction ledgers instead of doubling up here).
 *
 * Fields considered too noisy/irrelevant to log are stripped out (Laravel
 * timestamps, and anything ending in _at that isn't already excluded).
 */
class ActivityLogObserver
{
    private const IGNORED_FIELDS = ['created_at', 'updated_at', 'remember_token'];

    public function created(Model $model): void
    {
        $this->record('created', $model, null);
    }

    public function updated(Model $model): void
    {
        $changes = $this->diffChanges($model);

        if (empty($changes)) {
            return; // e.g. a touch() with nothing meaningful changed
        }

        $this->record('updated', $model, $changes);
    }

    public function deleted(Model $model): void
    {
        $this->record('deleted', $model, null);
    }

    private function diffChanges(Model $model): array
    {
        $changes = [];

        foreach ($model->getChanges() as $field => $newValue) {
            if (in_array($field, self::IGNORED_FIELDS, true)) {
                continue;
            }

            $oldValue = $model->getOriginal($field);

            // Skip values too large/unwieldy to store meaningfully in a diff
            // (e.g. long text/JSON columns) — record that it changed, not
            // the full before/after blob.
            if (is_string($newValue) && strlen($newValue) > 200) {
                $changes[$field] = ['old' => '(changed)', 'new' => '(changed)'];
                continue;
            }

            $changes[$field] = ['old' => $oldValue, 'new' => $newValue];
        }

        return $changes;
    }

    private function record(string $action, Model $model, ?array $changes): void
    {
        try {
            ActivityLog::create([
                'loan_manager_id' => $this->resolveLoanManagerId($model),
                'user_id' => Auth::id(),
                'action' => $action,
                'subject_type' => class_basename($model),
                'subject_id' => $model->getKey(),
                'description' => $this->describe($action, $model),
                'changes' => $changes,
                'ip_address' => request()?->ip(),
            ]);
        } catch (\Throwable $e) {
            // Audit logging must never break the underlying operation it's
            // observing — same best-effort philosophy as JournalPoster.
        }
    }

    private function resolveLoanManagerId(Model $model): ?int
    {
        if (isset($model->loan_manager_id)) {
            return $model->loan_manager_id;
        }

        if ($model instanceof Payment) {
            return $model->loan?->loan_manager_id;
        }

        return null;
    }

    private function describe(string $action, Model $model): string
    {
        return match (true) {
            $model instanceof Loan => sprintf(
                'Loan #%d (%s) %s',
                $model->getKey(),
                $model->client?->name ?? 'client #' . $model->client_id,
                $action
            ),
            $model instanceof Client => sprintf('Client "%s" %s', $model->name ?? ('#' . $model->getKey()), $action),
            $model instanceof Payment => sprintf(
                'Payment of %s on loan #%d %s',
                number_format((float) $model->amount_paid, 2),
                $model->loan_id,
                $action
            ),
            $model instanceof JournalEntry => sprintf(
                'Journal entry #%d (%s) %s',
                $model->getKey(),
                $model->narration ?? 'no narration',
                $action
            ),
            default => class_basename($model) . ' #' . $model->getKey() . ' ' . $action,
        };
    }
}
