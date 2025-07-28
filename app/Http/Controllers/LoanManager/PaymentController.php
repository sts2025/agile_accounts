<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\Account;
use App\Models\GeneralLedgerTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Hash;

class PaymentController extends Controller
{
    /**
     * Store a newly created payment in storage.
     */
   public function store(Request $request)
{
    // 1. Validate the incoming data (receipt_number is no longer here)
    $validatedData = $request->validate([
        'loan_id' => ['required', Rule::exists('loans', 'id')->where('loan_manager_id', Auth::id())],
        'amount_paid' => 'required|numeric|min:0.01',
        'payment_date' => 'required|date',
        'payment_method' => 'required|string',
        'notes' => 'nullable|string',
    ]);

    // --- NEW: Generate a unique receipt number ---
    $year = now()->year;
    $latestPayment = Payment::whereYear('created_at', $year)->latest('id')->first();
    $nextId = $latestPayment ? (int)substr($latestPayment->receipt_number, 5) + 1 : 1;
    $receiptNumber = $year . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

    // Add the generated receipt number to the data to be saved
    $validatedData['receipt_number'] = $receiptNumber;

    // 2. Create the payment record
    $payment = Payment::create($validatedData);

    // The rest of the function remains the same
    $this->recordPaymentTransaction($payment);

    if ($this->isLoanFullyPaid($payment->loan)) {
        $payment->loan->status = 'paid';
        $payment->loan->save();
    }

    return redirect()->route('loans.show', $validatedData['loan_id'])
                     ->with('status', 'Payment recorded successfully! Receipt No: ' . $receiptNumber);
}

    /**
     * Generate a PDF receipt for a specific payment.
     */
    public function showReceipt(Payment $payment)
    {
        // Authorize: Ensure the user can only view receipts for their own loans.
        if (Auth::id() !== $payment->loan->loan_manager_id) {
            abort(403);
        }

        // Prepare the WhatsApp Message
        $clientName = $payment->loan->client->name;
        $amountPaid = "UGX " . number_format($payment->amount_paid, 0);
        $paymentDate = $payment->payment_date->format('d-M-Y');
        $receiptId = $payment->id;

        $message = "Dear {$clientName},\n\n";
        $message .= "This is a confirmation for your payment of *{$amountPaid}* received on {$paymentDate} for Loan ID #{$payment->loan_id}.\n\n";
        $message .= "Thank you for your business.\n";
        $message .= "Agile Accounts\n";
        $message .= "Receipt ID: {$receiptId}";
        $whatsappMessage = urlencode($message);

        // Load a view and pass BOTH the payment data and the message to it
        $pdf = Pdf::loadView('receipts.payment', compact('payment', 'whatsappMessage'));

        // Stream the PDF to the browser so the user can view or download it
        return $pdf->stream('receipt-'.$payment->id.'.pdf');
    }

    /**
     * Helper method to record the double-entry transaction for a received payment.
     */
    private function recordPaymentTransaction(Payment $payment)
    {
        $cashAccount = Account::where('name', 'Cash on Hand')->first();
        $receivableAccount = Account::where('name', 'Loans Receivable')->first();
        $incomeAccount = Account::where('name', 'Interest Income')->first();
        $loan = $payment->loan;
        $monthlyInterestRate = ($loan->interest_rate / 100) / 12;
        $totalPaidPreviously = $loan->payments()->where('id', '!=', $payment->id)->sum('amount_paid');
        $currentBalance = $loan->principal_amount - $totalPaidPreviously;
        $interestComponent = min($payment->amount_paid, $currentBalance * $monthlyInterestRate);
        $principalComponent = $payment->amount_paid - $interestComponent;

        if($cashAccount) GeneralLedgerTransaction::create(['account_id' => $cashAccount->id, 'loan_id' => $loan->id, 'transaction_date' => $payment->payment_date, 'description' => 'Payment received from ' . $loan->client->name, 'debit' => $payment->amount_paid, 'credit' => 0]);
        if($receivableAccount) GeneralLedgerTransaction::create(['account_id' => $receivableAccount->id, 'loan_id' => $loan->id, 'transaction_date' => $payment->payment_date, 'description' => 'Principal portion of payment from ' . $loan->client->name, 'debit' => 0, 'credit' => $principalComponent]);
        if($incomeAccount) GeneralLedgerTransaction::create(['account_id' => $incomeAccount->id, 'loan_id' => $loan->id, 'transaction_date' => $payment->payment_date, 'description' => 'Interest portion of payment from ' . $loan->client->name, 'debit' => 0, 'credit' => $interestComponent]);
    }
    
    /**
     * Helper to check if a loan is fully paid.
     */
    private function isLoanFullyPaid(Loan $loan)
    {
        $totalPaid = $loan->payments->sum('amount_paid');
        $principal = $loan->principal_amount;
        $monthlyInterestRate = ($loan->interest_rate / 100) / 12;
        $termInMonths = $loan->term;
        if ($denominator = (pow(1 + $monthlyInterestRate, $termInMonths) - 1)) {
            $numerator = $monthlyInterestRate * pow(1 + $monthlyInterestRate, $termInMonths);
            $monthlyPayment = $principal * ($numerator / $denominator);
            $totalLoanCost = $monthlyPayment * $termInMonths;
            return $totalPaid >= $totalLoanCost;
        }
        return false;
    }
    // In PaymentController.php

/**
 * Show the password confirmation form before editing a payment.
 */
public function showPasswordConfirmationForm(Payment $payment)
{
    // Authorize: Ensure the user owns the loan associated with this payment
    if (Auth::id() !== $payment->loan->loan_manager_id) {
        abort(403);
    }
    return view('loan-manager.payments.confirm-password', compact('payment'));
}

/**
 * Handle the password confirmation and redirect to the edit form.
 */
public function confirmPassword(Request $request, Payment $payment)
{
    // Authorize again
    if (Auth::id() !== $payment->loan->loan_manager_id) {
        abort(403);
    }

    // Validate that a password was submitted
    $request->validate(['password' => 'required|string']);

    // Check if the submitted password matches the logged-in user's password
    if (!Hash::check($request->password, Auth::user()->password)) {
        return back()->withErrors(['password' => 'The provided password does not match your current password.']);
    }

    // If password is correct, store a confirmation timestamp in the session
    // and redirect to the actual edit page.
    $request->session()->put('payment_edit_confirmed_at_' . $payment->id, now());

    return redirect()->route('payments.edit', $payment);
}
public function edit(Request $request, Payment $payment)
    {
        // This is our new security check. It ensures a user can only get to this page
        // if they have confirmed their password in the last 10 minutes.
        $confirmationKey = 'payment_edit_confirmed_at_' . $payment->id;
        if (!$request->session()->has($confirmationKey) || now()->diffInMinutes($request->session()->get($confirmationKey)) > 10) {
            return redirect()->route('payments.edit.confirm', $payment->id);
        }

        return view('loan-manager.payments.edit', compact('payment'));
    }

    /**
     * Update the specified payment in storage.
     */
    public function update(Request $request, Payment $payment)
    {
        if (Auth::id() !== $payment->loan->loan_manager_id) {
            abort(403);
        }
        $validatedData = $request->validate([
            'amount_paid' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $payment->update($validatedData);
        
        // After updating, forget the session key
        $request->session()->forget('payment_edit_confirmed_at_' . $payment->id);

        return redirect()->route('loans.show', $payment->loan_id)->with('status', 'Payment updated successfully!');
    }
}