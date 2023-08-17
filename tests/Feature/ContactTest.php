<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Contact;
use Database\Factories\ContactFactory;
use Tests\DataProvider\ContactDataProvider;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProviderExternal;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->withToken()->create();
    }

    #[DataProviderExternal(ContactDataProvider::class, 'createContactSuccessProvider')]
    public function testCreateContactSuccess(
        ContactFactory $contactFactory,
        callable $getPayload,
        callable $getExpectedDbHas,
    ): void
    {
        $contact = $contactFactory->make();

        $response = $this->withHeaders([
                'Authorization' => $this->user->token,
            ])
            ->post('/api/contacts/', $getPayload($contact));
        
        $response
            ->assertStatus(201)
            ->assertJson(fn (AssertableJson $json) => $json
                ->hasAll(['data', 'data.id', 'errors'])
                ->whereType('data.id', 'integer')
        );

        $this->assertDatabaseHas('contacts', $getExpectedDbHas($contact));
    }

    #[DataProviderExternal(ContactDataProvider::class, 'createContactInvalidPayloadProvider')]
    public function testCreateContactInvalidPayload(array $payload, callable $jsonAssert): void
    {
        $response = $this->withHeaders([
            'Authorization' => $this->user->token,
        ])
        ->post('/api/contacts', $payload);

        $response
            ->assertStatus(400)
            ->assertJson($jsonAssert);
    }

    public function testCreateContactUnauthorized(): void
    {
        $contact = Contact::factory()->make();

        $response = $this->post('/api/contacts', [
            'firstName' => $contact->first_name
        ]);

        $response
            ->assertStatus(401)
            ->assertJson([
                'errors' => [
                    'message' => 'Unauthorized.'
                ]
            ]);
    }

    public function testGetSuccess(): void
    {
        $contact = Contact::factory()->for($this->user)->create();

        $response = $this->withHeaders([
            'Authorization' => $this->user->token,
            ])
            ->get('/api/contacts/' . $contact->id);

        $response
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $contact->id,
                    'firstName' => $contact->first_name,
                    'lastName' => $contact->last_name,
                    'email' => $contact->email,
                    'phone' => $contact->phone,
                ],
                'errors' => []
            ]);
    }

    public function testGetNotFound(): void
    {
        $response = $this->withHeaders([
            'Authorization' => $this->user->token,
            ])
            ->get('/api/contacts/1');

        $response
            ->assertStatus(404)
            ->assertJson([
                'errors' => [
                    'message' => 'Resource not found.'
                ]
            ]);
    }

    public function testGetInvalidPath(): void
    {
        $contact = Contact::factory()->for($this->user)->create();

        $response = $this->withHeaders([
            'Authorization' => $this->user->token,
            ])
            ->get('/api/contacts/invalidId');

        $response
            ->assertStatus(404)
            ->assertJson([
                'errors' => [
                    'message' => 'Resource not found.'
                ]
            ]);
    }

    public function testGetOthersContactShouldNotFound(): void
    {
        $others = User::factory()->random()->create();
        $contact = Contact::factory()->for($others)->create();

        $response = $this->withHeaders([
                'Authorization' => $this->user->token,
            ])
            ->get('/api/contacts/' . $contact->id);

        $response
            ->assertStatus(404)
            ->assertJson([
                'errors' => [
                    'message' => 'Resource not found.'
                ]
            ]);
    }

    public function testGetUnauthenticated(): void
    {
        $contact = Contact::factory()->for($this->user)->create();

        $response = $this->get('/api/contacts/' . $contact->id);

        $response
        ->assertStatus(401)
        ->assertJson([
            'errors' => [
                'message' => 'Unauthorized.'
            ]
        ]);
    }

    #[DataProviderExternal(ContactDataProvider::class, 'updateSuccessProvider')]
    public function testUpdateSuccess(
        ContactFactory $contactFactory,
        callable $getPayload,
        callable $getExpectedDbMissing,
        callable $getExpectedDbHas,
    ): void
    {
        $contact = $contactFactory->for($this->user)->create();

        $response = $this->withHeaders([
                'Authorization' => $this->user->token,
            ])
            ->put('/api/contacts/' . $contact->id, $getPayload($contact));

        $response
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json
                ->hasAll(['data', 'data.id', 'errors'])
            );

        $this->assertDatabaseMissing('contacts', $getExpectedDbMissing($contact));
        $this->assertDatabaseHas('contacts', $getExpectedDbHas($contact));
    }

    public function testUpdateNotFound(): void
    {
        $contact = Contact::factory()->for($this->user)->make();

        $response = $this->withHeaders([
            'Authorization' => $this->user->token,
        ])
        ->put('/api/contacts/1', [
            'firstName' => 'Updated' . $contact->first_name,
            'lastName' => 'Updated' . $contact->last_name,
        ]);

        $response
            ->assertStatus(404)
            ->assertJson([
                'errors' => [
                    'message' => 'Resource not found.'
                ]
            ]);
    }

    #[DataProviderExternal(ContactDataProvider::class, 'updateInvalidPayloadProvider')]
    public function testUpdateInvalidPayload(
        array $payload,
        callable $jsonAssert,
    ): void
    {
        $contact = Contact::factory()->for($this->user)->create();

        $response = $this->withHeaders([
                'Authorization' => $this->user->token,
            ])
            ->put('/api/contacts/' . $contact->id, $payload);

        $response
            ->assertStatus(400)
            ->assertJson($jsonAssert);
    }

    public function testUpdateInvalidPath(): void
    {
        $contact = Contact::factory()->for($this->user)->create();

        $response = $this->withHeaders([
            'Authorization' => $this->user->token,
            ])
            ->put('/api/contacts/invalidId', [
                'firstName' => 'Updated' . $contact->first_name,
                'lastName' => 'Updated' . $contact->last_name,
            ]);

        $response
            ->assertStatus(404)
            ->assertJson([
                'errors' => [
                    'message' => 'Resource not found.'
                ]
            ]);
    }

    public function testUpdateOthersContactShouldNotFound(): void
    {
        $others = User::factory()->random()->create();
        $contact = Contact::factory()->for($others)->create();

        $response = $this->withHeaders([
                'Authorization' => $this->user->token,
            ])
            ->put('/api/contacts/' . $contact->id, [
                'firstName' => 'Updated' . $contact->first_name,
                'lastName' => 'Updated' . $contact->last_name,
            ]);

        $response
            ->assertStatus(404)
            ->assertJson([
                'errors' => [
                    'message' => 'Resource not found.'
                ]
            ]);
    }

    public function testUpdateUnauthenticated(): void
    {
        $contact = Contact::factory()->for($this->user)->create();

        $response = $this
            ->put('/api/contacts/' . $contact->id, [
                'firstName' => 'Updated' . $contact->first_name,
                'lastName' => 'Updated' . $contact->last_name,
            ]);

        $response
            ->assertStatus(401)
            ->assertJson([
                'errors' => [
                    'message' => 'Unauthorized.'
                ]
            ]);
    }

    public function testDeleteSuccess(): void
    {
        $contact = Contact::factory()->for($this->user)->create();

        $response = $this
            ->withHeaders([
                'Authorization' => $this->user->token,
            ])
            ->delete('/api/contacts/' . $contact->id);
    
        $response
            ->assertStatus(200)
            ->assertJson([
                'data' => true,
                'errors' => []
            ]);

        $this->assertModelMissing($contact);
    }

    public function testDeleteNotFound(): void
    {
        $response = $this
            ->withHeaders([
                'Authorization' => $this->user->name,
            ])
            ->delete('/api/contacts/1');    
        
        $response
            ->assertStatus(404)
            ->assertJson([
                'errors' => [
                    'message' => 'Resource not found.'
                ]
            ]);
    }

    public function testDeleteInvalidPath(): void
    {
        $contact = Contact::factory()->for($this->user)->create();

        $response = $this
            ->withHeaders([
                'Authorization' => $this->user->name,
            ])
            ->delete('/api/contacts/invalid-path');    
        
        $response
            ->assertStatus(404)
            ->assertJson([
                'errors' => [
                    'message' => 'Resource not found.'
                ]
            ]);
    }

    public function testDeleteOthersContactShouldNotFound(): void
    {
        $others = User::factory()->random()->create();
        $contact = Contact::factory()->for($others)->create();

        $response = $this->withHeaders([
                'Authorization' => $this->user->token,
            ])
            ->delete('/api/contacts/' . $contact->id);

        $response
            ->assertStatus(404)
            ->assertJson([
                'errors' => [
                    'message' => 'Resource not found.'
                ]
            ]);
    }

    public function testDeleteUnauthenticated(): void
    {
        $contact = Contact::factory()->for($this->user)->create();

        $response = $this
            ->delete('/api/contacts/' . $contact->id);

        $response
            ->assertStatus(401)
            ->assertJson([
                'errors' => [
                    'message' => 'Unauthorized.'
                ]
            ]);
    }

    #[DataProviderExternal(ContactDataProvider::class, 'searchSuccessProvider')]
    public function testSearchSuccess(callable $getQueryParams, callable $getJsonAssert): void
    {
        $contactsRawCollection = collect([]);

        for ($i = 1; $i <= 9; $i++) {
            $flag = $i % 2 === 0 ? 'even' : 'odd';

            $contactsRawCollection->push([
                'first_name' => fake()->firstName() . '#first#' . $flag . '#' . $i,
                'last_name' => fake()->lastName() . '#last#' . $flag . '#' . $i,
                'email' => fake()->email() . '#' . $flag . '#' . $i,
                'phone' => fake()->phoneNumber(),
                'user_id' => $this->user->id,
            ]);
        }

        Contact::upsert($contactsRawCollection->toArray(), [
            'first_name', 'last_name', 'email', 'phone'
        ]);

        $contacts = $this->user->contacts;

        $response = $this
            ->withHeaders([
                'Authorization' => $this->user->token,
            ])
            ->get('/api/contacts' . $getQueryParams($contacts[0]));

        $response
            ->assertStatus(200)
            ->assertJson($getJsonAssert($contacts[0]));
    }

    public function testSearchUnauthenticated(): void
    {
        $response = $this
            ->get('/api/contacts');

        $response
            ->assertStatus(401)
            ->assertJson([
                'errors' => [
                    'message' => 'Unauthorized.'
                ]
            ]);
    }

}
