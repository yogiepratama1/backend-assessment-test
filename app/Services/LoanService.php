<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\ReceivedRepayment;
use App\Models\User;

class LoanService
{
    /**
     * Create a Loan
     *
     * @param  User  $user
     * @param  int  $amount
     * @param  string  $currencyCode
     * @param  int  $terms
     * @param  string  $processedAt
     *
     * @return Loan
     */
    public function createLoan(User $user, int $amount, string $currencyCode, int $terms, string $processedAt): Loan
    {
        $loan = Loan::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'terms' => $terms,
            'outstanding_amount' => $amount,
            'currency_code' => $currencyCode,
            'processed_at' => $processedAt,
            'status' => Loan::STATUS_DUE,
        ]);

        $data = [];

        for ($i = 1; $i <= $terms; $i++) {
            $dueDate = date('Y-m-d', strtotime($processedAt . ' + ' . $i . ' month'));
            $totalAmount = floor($amount / $terms);
            if ($i == $terms) {
                $totalAmount = floor($amount - ($totalAmount * ($terms - 1)));
            }

            $data[] = [
                'amount' => $totalAmount,
                'outstanding_amount' => $totalAmount,
                'currency_code' => $currencyCode,
                'due_date' => $dueDate
            ];
        }

        $loan->scheduledRepayments()->createMany($data);

        return $loan;
    }

    /**
     * Repay Scheduled Repayments for a Loan
     *
     * @param  Loan  $loan
     * @param  int  $amount
     * @param  string  $currencyCode
     * @param  string  $receivedAt
     *
     * @return ReceivedRepayment
     */
    public function repayLoan(Loan $loan, int $amount, string $currencyCode, string $receivedAt): ReceivedRepayment
    {
        //
    }
}
