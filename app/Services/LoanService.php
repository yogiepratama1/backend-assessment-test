<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Loan;
use App\Models\User;
use App\Models\ReceivedRepayment;
use App\Models\ScheduledRepayment;
use Illuminate\Support\Facades\DB;

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
        $receivedRepayment = ReceivedRepayment::create([
            'loan_id' => $loan->id,
            'amount' => $amount,
            'currency_code' => $currencyCode,
            'received_at' => $receivedAt,
        ]);

        $scheduledRepayments = $loan->scheduledRepayments()
            ->where('status', ScheduledRepayment::STATUS_DUE)
            ->orderBy('due_date', 'asc')
            ->get();

        $remainingAmount = $amount;

        foreach ($scheduledRepayments as $scheduledRepayment) {
            if ($remainingAmount == 0) {
                break;
            }
            if ($remainingAmount < $scheduledRepayment->outstanding_amount) {
                $outstandingAmount = $scheduledRepayment->outstanding_amount - $remainingAmount;
                $scheduledRepayment->update([
                    'outstanding_amount' => $outstandingAmount,
                    'status' => ScheduledRepayment::STATUS_PARTIAL
                ]);
                $remainingAmount = 0;
            } else {
                $remainingAmount = $remainingAmount - $scheduledRepayment->outstanding_amount;
                $scheduledRepayment->update([
                    'outstanding_amount' => 0,
                    'status' => ScheduledRepayment::STATUS_REPAID
                ]);
            }
        }

        $outstandingAmount = $loan->scheduledRepayments()->sum('outstanding_amount');

        if ($outstandingAmount == 0) {
            $loan->update([
                'outstanding_amount' => $outstandingAmount,
                'status' => Loan::STATUS_REPAID,
            ]);
        } else {
            $loan->update([
                'outstanding_amount' => $outstandingAmount,
                'status' => Loan::STATUS_DUE,
            ]);
        }


        return $receivedRepayment;
    }
}
