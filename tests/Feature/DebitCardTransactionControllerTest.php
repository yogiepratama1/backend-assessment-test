<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\DebitCard;
use Laravel\Passport\Passport;
use App\Models\DebitCardTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DebitCardTransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected DebitCard $debitCard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id
        ]);
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCardTransactions()
    {
        // get /debit-card-transactions
        DebitCardTransaction::factory(2)->create([
            'debit_card_id' => $this->debitCard->id
        ]);

        $response = $this->get('/api/debit-card-transactions?debit_card_id=' . $this->debitCard->id);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => [
                'amount',
                'currency_code',
            ]
        ]);
        $response->assertJsonCount(2);
        $this->assertDatabaseCount('debit_card_transactions', 2);
        $this->assertDatabaseHas('debit_card_transactions', [
            'debit_card_id' => $this->debitCard->id,
        ]);
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        // get /debit-card-transactions
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        // post /debit-card-transactions
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        // post /debit-card-transactions
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        // get /debit-card-transactions/{debitCardTransaction}
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        // get /debit-card-transactions/{debitCardTransaction}
    }

    // Extra bonus for extra tests :)
}
