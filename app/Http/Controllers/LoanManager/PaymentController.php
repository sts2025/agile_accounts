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

class PaymentController extends Controller
{
    /**
     * Store a newly created payment in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'loan_id' => ['required', Rule::exists('loans', 'id')->where('loan_manager_id', Auth::id())],
            'amount_paid' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'receipt_number' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $payment = Payment::create($validatedData);
        
        $this->recordPaymentTransaction($payment);
        
        if ($this->isLoanFullyPaid($payment->loan)) {
            $payment->loan->status = 'paid';
            $payment->loan->save();
        }

        return redirect()->route('loans.show', $validatedData['loan_id'])
                         ->with('status', 'Payment has been recorded successfully!');
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
}