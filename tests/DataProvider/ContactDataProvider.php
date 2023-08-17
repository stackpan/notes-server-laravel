<?php

namespace Tests\DataProvider;

use App\Models\Contact;
use Illuminate\Testing\Fluent\AssertableJson;

final class ContactDataProvider {

    public static function createContactSuccessProvider(): array
    {
        $contactFactory = Contact::factory();

        return [
            'firstName only' => [
                $contactFactory,
                fn (Contact $contact) => [
                    'firstName' => $contact->first_name,
                ],
                fn (Contact $contact) => [
                    'first_name' => $contact->first_name,
                    'last_name' => null,
                    'email' => null,
                    'phone' => null,
                ]
            ],
            'partially' => [
                $contactFactory,
                fn (Contact $contact) => [
                    'firstName' => $contact->first_name,
                    'email' => $contact->email,
                ],
                fn (Contact $contact) => [
                    'first_name' => $contact->first_name,
                    'last_name' => null,
                    'email' => $contact->email,
                    'phone' => null,
                ]
            ],
            'full' => [
                $contactFactory,
                fn (Contact $contact) => [
                    'firstName' => $contact->first_name,
                    'lastName' => $contact->last_name,
                    'email' => $contact->email,
                    'phone' => $contact->phone,
                ],
                fn (Contact $contact) => [
                    'first_name' => $contact->first_name,
                    'last_name' => $contact->last_name,
                    'email' => $contact->email,
                    'phone' => $contact->phone,
                ]
            ], 
        ];
    }

    public static function createContactInvalidPayloadProvider(): array
    {
        return [
            [
                [
                    'lastName' => fake()->lastName()
                ],
                fn (AssertableJson $json) => $json
                    ->whereType('errors.firstName', 'array')
            ],
            [
                [
                    'firstName' => fake()->firstName(),
                    'email' => 'invalidemail'
                ],
                fn (AssertableJson $json) => $json
                    ->whereType('errors.email', 'array')
            ],
            [
                [
                    'firstName' => fake()->firstName(),
                    'phone' => '1231242194238782734897198471'
                ],
                fn (AssertableJson $json) => $json
                    ->whereType('errors.phone', 'array')
            ],
            [
                [
                    'email' => 'invalidemail',
                ],
                fn (AssertableJson $json) => $json
                    ->whereType('errors.firstName', 'array')
                    ->whereType('errors.email', 'array')
            ]
        ];
    }

    public static function updateSuccessProvider(): array
    {
        $contactFactory = Contact::factory();

        return [
            'firstName only' => [
                $contactFactory,
                fn (Contact $contact) => [
                    'firstName' => 'Updated' . $contact->first_name,
                ],
                fn (Contact $contact) => [
                    'first_name' => $contact->first_name,
                ],
                fn (Contact $contact) => [
                    'first_name' => 'Updated' . $contact->first_name,
                ],
            ],
            'partially' => [
                $contactFactory,
                fn (Contact $contact) => [
                    'firstName' => 'Updated' . $contact->first_name,
                    'email' => 'updated' . $contact->email,
                ],
                fn (Contact $contact) => [
                    'first_name' => $contact->first_name,
                    'email' => $contact->email,
                ],
                fn (Contact $contact) => [
                    'first_name' => 'Updated' . $contact->first_name,
                    'email' => 'updated' . $contact->email,
                ],
            ],
            'full' => [
                $contactFactory,
                fn (Contact $contact) => [
                    'firstName' => 'Updated' . $contact->first_name,
                    'lastName' => 'Updated' . $contact->last_name,
                    'email' => 'updated' . $contact->email,
                    'phone' => '081234567890',
                ],
                fn (Contact $contact) => [
                    'first_name' => $contact->first_name,
                    'last_name' => $contact->last_name,
                    'email' => $contact->email,
                    'phone' => $contact->phone,
                ],
                fn (Contact $contact) => [
                    'first_name' => 'Updated' . $contact->first_name,
                    'last_name' => 'Updated' . $contact->last_name,
                    'email' => 'updated' . $contact->email,
                    'phone' => '081234567890',
                ],
            ], 
        ];
    }

    public static function updateInvalidPayloadProvider(): array
    {
        return [
            [
                [
                    'lastName' => fake()->lastName()
                ],
                fn (AssertableJson $json) => $json
                    ->whereType('errors.firstName', 'array')
            ],
            [
                [
                    'firstName' => fake()->firstName(),
                    'email' => 'invalidemail'
                ],
                fn (AssertableJson $json) => $json
                    ->whereType('errors.email', 'array')
            ],
            [
                [
                    'firstName' => fake()->firstName(),
                    'phone' => '1231242194238782734897198471'
                ],
                fn (AssertableJson $json) => $json
                    ->whereType('errors.phone', 'array')
            ],
            [
                [
                    'email' => 'invalidemail',
                ],
                fn (AssertableJson $json) => $json
                    ->whereType('errors.firstName', 'array')
                    ->whereType('errors.email', 'array')
            ]
        ];
    }

    public static function searchSuccessProvider(): array
    {
        return [
            'by firstName #1' => [
                fn (Contact $contact) => '?name=first%23odd',
                fn (Contact $contact) => 
                    fn (AssertableJson $json) => $json
                        ->has('data', 5)
                        ->has('errors')
                        ->has('links')
                        ->has('meta')
            ],
            'by firstName #2' => [
                fn (Contact $contact) => '?name=first%23even',
                fn (Contact $contact) => 
                    fn (AssertableJson $json) => $json
                        ->has('data', 4)
                        ->has('errors')
                        ->has('links')
                        ->has('meta')
            ],
            'by firstName #3' => [
                fn (Contact $contact) => '?name=' . $contact->first_name,
                fn (Contact $contact) => 
                    fn (AssertableJson $json) => $json
                        ->has('data', 1)
                        ->has('errors')
                        ->has('links')
                        ->has('meta')
            ],
            'by lastName #1' => [
                fn (Contact $contact) => '?name=last%23odd',
                fn (Contact $contact) => 
                    fn (AssertableJson $json) => $json
                        ->has('data', 5)
                        ->has('errors')
                        ->has('links')
                        ->has('meta')
            ],
            'by lastName #2' => [
                fn (Contact $contact) => '?name=first%23even',
                fn (Contact $contact) => 
                    fn (AssertableJson $json) => $json
                        ->has('data', 4)
                        ->has('errors')
                        ->has('links')
                        ->has('meta')
            ],
            'by lastName #3' => [
                fn (Contact $contact) => '?name=' . $contact->last_name,
                fn (Contact $contact) => 
                    fn (AssertableJson $json) => $json
                        ->has('data', 1)
                        ->has('errors')
                        ->has('links')
                        ->has('meta')
            ],
            'by email #1' => [
                fn (Contact $contact) => '?email=odd',
                fn (Contact $contact) => 
                    fn (AssertableJson $json) => $json
                        ->has('data', 5)
                        ->has('errors')
                        ->has('links')
                        ->has('meta')
            ],
            'by email #2' => [
                fn (Contact $contact) => '?email=even',
                fn (Contact $contact) => 
                    fn (AssertableJson $json) => $json
                        ->has('data', 4)
                        ->has('errors')
                        ->has('links')
                        ->has('meta')
            ],
            'by email #3' => [
                fn (Contact $contact) => '?email=' . $contact->email,
                fn (Contact $contact) => 
                    fn (AssertableJson $json) => $json
                        ->has('data', 1)
                        ->has('errors')
                        ->has('links')
                        ->has('meta')
            ],
            'by phone' => [
                fn (Contact $contact) => '?phone=' . $contact->phone,
                fn (Contact $contact) => 
                    fn (AssertableJson $json) => $json
                        ->has('data', 1)
                        ->has('errors')
                        ->has('links')
                        ->has('meta')
            ],
            'by combination' => [
                fn (Contact $contact) => '?name=%23even&email=%234',
                fn (Contact $contact) => 
                    fn (AssertableJson $json) => $json
                        ->has('data', 1)
                        ->has('errors')
                        ->has('links')
                        ->has('meta')
            ],
            'by combination not found' => [
                fn (Contact $contact) => '?name=%23even&email=%233',
                fn (Contact $contact) => 
                    fn (AssertableJson $json) => $json
                        ->has('data', 0)
                        ->has('errors')
                        ->has('links')
                        ->has('meta')
            ],
            'by size' => [
                fn (Contact $contact) => '?size=4',
                fn (Contact $contact) => 
                    fn (AssertableJson $json) => $json
                        ->has('data', 4)
                        ->has('errors')
                        ->has('links')
                        ->has('meta')
            ],
            'by page' => [
                fn (Contact $contact) => '?size=4&page=2',
                fn (Contact $contact) => 
                    fn (AssertableJson $json) => $json
                        ->has('data', 4)
                        ->has('errors')
                        ->has('links')
                        ->has('meta')
                        ->where('meta.current_page', 2)
            ],
        ];
    }

}
