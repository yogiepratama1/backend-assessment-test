<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DebitCardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCards()
    {
        DebitCard::factory(2)->active()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->get('/api/debit-cards');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => [
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active',
            ]
        ]);
        $response->assertJsonCount(2);
        $this->assertDatabaseCount('debit_cards', 2);
        $this->assertDatabaseHas('debit_cards', [
            'user_id' => $this->user->id,
        ]);
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        $otherUser = User::factory()->create();
        DebitCard::factory()->active()->create([
            'user_id' => $otherUser->id,
        ]);
        DebitCard::factory()->active()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->get('/api/debit-cards');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => [
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active',
            ]
        ]);
        $response->assertJsonCount(1);
        $response->assertJsonMissing([
            'user_id' => $otherUser->id
        ]);
        $this->assertDatabaseCount('debit_cards', 2);
    }

    public function testCustomerCanCreateADebitCard()
    {
        $response = $this->post('/api/debit-cards', [
            'type' => 'MasterCard'
        ]);
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'id',
            'number',
            'type',
            'expiration_date',
            'is_active',
        ]);
        $response->assertJsonFragment([
            'type' => 'MasterCard'
        ]);
        $this->assertDatabaseHas('debit_cards', [
            'user_id' => $this->user->id,
            'type' => 'MasterCard',
        ]);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        $debitCard = DebitCard::factory()->active()->create([
            'user_id' => $this->user->id
        ]);
        $response = $this->get('/api/debit-cards/' . $debitCard->id);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'number',
            'type',
            'expiration_date',
            'is_active',
        ]);
        $response->assertJsonFragment([
            'id' => $debitCard->id,
        ]);
        $this->assertDatabaseHas('debit_cards', [
            'user_id' => $this->user->id,
            'id' => $debitCard->id,
            'number' => $debitCard->number,
            'type' => $debitCard->type,
            'expiration_date' => $debitCard->expiration_date
        ]);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        $debitCard = DebitCard::factory()->active()->create();

        $response = $this->get('/api/debit-cards/' . $debitCard->id);
        $response->assertStatus(403);
    }

    public function testCustomerCanActivateADebitCard()
    {
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'disabled_at' => now(),
        ]);

        $response = $this->put('/api/debit-cards/' . $debitCard->id, [
            'is_active' => true
        ]);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'number',
            'type',
            'expiration_date',
        ]);
        $response->assertJsonFragment([
            'id' => $debitCard->id,
            'is_active' => true
        ]);
        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
            'disabled_at' => null
        ]);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        $debitCard = DebitCard::factory()->active()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->put('/api/debit-cards/' . $debitCard->id, [
            'is_active' => false
        ]);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'number',
            'type',
            'expiration_date',
        ]);
        $response->assertJsonFragment([
            'id' => $debitCard->id,
            'is_active' => false
        ]);
        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
            'disabled_at' => now()
        ]);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        $debitCard = DebitCard::factory()->active()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->put('/api/debit-cards/' . $debitCard->id, [
            'is_active' => 'validasi yang tidak valid'
        ]);
        $response->assertStatus(302);
        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
            'disabled_at' => null
        ]);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        $debitCard = DebitCard::factory()->active()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->delete('/api/debit-cards/' . $debitCard->id);
        $response->dump();
        $response->assertStatus(204);
        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
            'deleted_at' => now()
        ]);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        // delete api/debit-cards/{debitCard}
    }

    // Extra bonus for extra tests :)
}
