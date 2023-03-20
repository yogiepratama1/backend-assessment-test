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
        $otherUser = User::factory()->create();
        $otherDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser
        ]);
        $otherDebitCardTransaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $otherDebitCard->id
        ]);

        $response = $this->get('/api/debit-card-transactions?debit_card_id=' . $otherDebitCardTransaction->debit_card_id);
        $response->assertStatus(403);
        $this->assertDatabaseCount('debit_card_transactions', 1);
        $this->assertDatabaseHas('debit_card_transactions', [
            'id' => $otherDebitCardTransaction->id,
        ]);
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        // post /debit-card-transactions
        $response = $this->post('/api/debit-card-transactions', [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 100000,
            'currency_code' => DebitCardTransaction::CURRENCY_IDR,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'amount',
            'currency_code',
        ]);
        $response->assertJsonFragment([
            'amount' => 100000,
            'currency_code' => DebitCardTransaction::CURRENCY_IDR,
        ]);
        $this->assertDatabaseHas('debit_card_transactions', [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 100000,
            'currency_code' => DebitCardTransaction::CURRENCY_IDR,
        ]);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        // post /debit-card-transactions
        $otherUser = User::factory()->create();
        $otherDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser
        ]);

        $response = $this->post('/api/debit-card-transactions', [
            'debit_card_id' => $otherDebitCard->id,
            'amount' => 100000,
            'currency_code' => DebitCardTransaction::CURRENCY_IDR,
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseCount('debit_card_transactions', 0);
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        $debitCardTransaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->debitCard->id
        ]);

        $response = $this->get('/api/debit-card-transactions/' . $debitCardTransaction->id);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'amount',
            'currency_code',
        ]);
        $response->assertJsonFragment([
            'amount' => $debitCardTransaction->amount,
            'currency_code' => $debitCardTransaction->currency_code,
        ]);
        $this->assertDatabaseHas('debit_card_transactions', [
            'id' => $debitCardTransaction->id,
            'debit_card_id' => $this->debitCard->id,
        ]);
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        // get /debit-card-transactions/{debitCardTransaction}
    }

    // Extra bonus for extra tests :)
}
