<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Bulk client onboarding via CSV upload — for MFI/SACCO staff migrating an
 * existing member register rather than typing each client in one at a
 * time. Deliberately row-by-row, not all-or-nothing: a typo in row 40 of a
 * 500-row file shouldn't cost the other 499 valid clients, so each row is
 * validated and saved independently (own DB transaction) and the failures
 * are reported back by row number/reason rather than aborting the batch.
 *
 * Validation rules mirror ClientController::store() exactly (same required
 * fields, same per-tenant uniqueness on phone/national_id) so a row that
 * would be accepted through the manual "Add Client" form is accepted here
 * too, and nothing sneaks through one path that the other would reject.
 */
class ClientImportController extends Controller
{
    private const TEMPLATE_HEADERS = [
        'name', 'phone_number', 'address', 'national_id', 'email',
        'date_of_birth', 'gender', 'business_occupation',
        'next_of_kin_name', 'next_of_kin_phone', 'next_of_kin_relationship',
        'client_type', 'business_name', 'business_registration_number',
    ];

    public function showForm()
    {
        return view('loan-manager.clients.import');
    }

    public function downloadTemplate()
    {
        $csv = implode(',', self::TEMPLATE_HEADERS) . "\n";
        $csv .= "Jane Mukasa,0700111222,\"Plot 4, Kampala Rd\",CM12345,jane@example.com,1990-05-14,Female,Retail shop,John Mukasa,0700333444,Spouse,individual,,\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="client-import-template.csv"',
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $managerId = Auth::user()->loanManager->id;

        $handle = fopen($request->file('csv_file')->getRealPath(), 'r');
        if (!$handle) {
            return back()->with('error', 'Could not read the uploaded file.');
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return back()->with('error', 'The file appears to be empty.');
        }

        $header = array_map(fn ($h) => strtolower(trim($h)), $header);

        $created = 0;
        $skipped = [];
        $rowNumber = 1; // header was row 1
        // Track phone/national_id seen so far in this file — the per-row
        // Rule::unique() check only guards against what's already in the
        // DB, not against a duplicate two rows above it in the same batch.
        $seenPhones = [];
        $seenNationalIds = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            // Skip fully blank lines (trailing newline in the file, etc.)
            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $data = [];
            foreach ($header as $i => $key) {
                $data[$key] = isset($row[$i]) ? trim($row[$i]) : null;
            }
            $data = array_filter($data, fn ($v) => $v !== null && $v !== '');

            $validator = Validator::make($data, [
                'name' => 'required|string|max:255',
                'phone_number' => ['required', 'string', 'max:20', Rule::unique('clients')->where('loan_manager_id', $managerId)],
                'address' => 'required|string|max:255',
                'national_id' => ['nullable', 'string', 'max:20', Rule::unique('clients')->where('loan_manager_id', $managerId)],
                'email' => 'nullable|email|max:255',
                'date_of_birth' => 'nullable|date|before:today',
                'gender' => 'nullable|string|in:Male,Female,Other,male,female,other',
                'business_occupation' => 'nullable|string|max:255',
                'next_of_kin_name' => 'nullable|string|max:255',
                'next_of_kin_phone' => 'nullable|string|max:20',
                'next_of_kin_relationship' => 'nullable|string|max:100',
                'client_type' => 'nullable|string|in:individual,business,Individual,Business',
                'business_name' => 'nullable|string|max:255',
                'business_registration_number' => 'nullable|string|max:100',
            ]);

            if ($validator->fails()) {
                $skipped[] = [
                    'row' => $rowNumber,
                    'name' => $data['name'] ?? '(no name)',
                    'reasons' => $validator->errors()->all(),
                ];
                continue;
            }

            $valid = $validator->validated();

            $phoneKey = isset($valid['phone_number']) ? strtolower($valid['phone_number']) : null;
            $nationalIdKey = isset($valid['national_id']) ? strtolower($valid['national_id']) : null;

            if ($phoneKey && isset($seenPhones[$phoneKey])) {
                $skipped[] = [
                    'row' => $rowNumber,
                    'name' => $data['name'] ?? '(no name)',
                    'reasons' => ["Duplicate phone number within this file (also on row {$seenPhones[$phoneKey]})."],
                ];
                continue;
            }
            if ($nationalIdKey && isset($seenNationalIds[$nationalIdKey])) {
                $skipped[] = [
                    'row' => $rowNumber,
                    'name' => $data['name'] ?? '(no name)',
                    'reasons' => ["Duplicate national ID within this file (also on row {$seenNationalIds[$nationalIdKey]})."],
                ];
                continue;
            }
            $valid['gender'] = isset($valid['gender']) ? ucfirst(strtolower($valid['gender'])) : null;
            $valid['client_type'] = isset($valid['client_type']) ? strtolower($valid['client_type']) : 'individual';
            $valid['loan_manager_id'] = $managerId;

            try {
                DB::transaction(function () use ($valid) {
                    Client::create($valid);
                });
                $created++;
                if ($phoneKey) {
                    $seenPhones[$phoneKey] = $rowNumber;
                }
                if ($nationalIdKey) {
                    $seenNationalIds[$nationalIdKey] = $rowNumber;
                }
            } catch (\Throwable $e) {
                $skipped[] = [
                    'row' => $rowNumber,
                    'name' => $data['name'] ?? '(no name)',
                    'reasons' => ['Could not be saved: ' . $e->getMessage()],
                ];
            }
        }

        fclose($handle);

        return view('loan-manager.clients.import-results', [
            'created' => $created,
            'skipped' => $skipped,
        ]);
    }
}
