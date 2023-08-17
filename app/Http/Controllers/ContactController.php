<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\IdResource;
use App\Http\Resources\ContactResource;
use App\Http\Resources\ContactCollection;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\ContactCreateRequest;
use App\Http\Requests\ContactUpdateRequest;
use App\Exceptions\HttpResponseNotFoundException;

class ContactController extends Controller
{
    public function create(ContactCreateRequest $request): IdResource
    {
        $validated = $request->validated();
        $user = auth()->user();

        $contact = $user->contacts()->create([
            'first_name' => $validated['firstName'],
            'last_name' => $validated['lastName'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
        ]);

        return (new IdResource($contact))
            ->additional([
                'errors' => []
            ]);
    }

    public function get(Contact $contact): ContactResource
    {
        $this->authorize('view', $contact);
        return new ContactResource($contact);
    }

    public function update(ContactUpdateRequest $request, Contact $contact): IdResource
    {
        $this->authorize('update', $contact);
        
        $contact->fill([
            'first_name' => $request['firstName'],
            'last_name' => $request['lastName'],
            'email' => $request['email'],
            'phone' => $request['phone'],
        ])->save();

        return (new IdResource($contact))
            ->additional([
                'errors' => []
            ]);
    }

    public function delete(Contact $contact): JsonResponse
    {
        $this->authorize('delete', $contact);

        $contact->delete();
        
        return response()->json([
            'data' => true,
            'errors' => []
        ], 200);
    }

    public function search(Request $request): ContactCollection
    {
        $this->authorize('viewAny', Contact::class);

        $page = $request->input('page', 1);
        $size = $request->input('size', 10);

        $query = auth()->user()->contacts()
            ->where(function (Builder $builder) use ($request) {
                $name = $request->input('name');
                $email = $request->input('email');
                $phone = $request->input('phone');

                if ($name) {
                    $builder->where(fn (Builder $builder) => $builder
                        ->orWhere('first_name', 'like' , '%' . $name .'%')
                        ->orWhere('last_name', 'like' , '%' . $name .'%')
                    );
                }

                if ($email) {
                    $builder->where('email', 'like', '%' . $email . '%');
                }

                if ($phone) {
                    $builder->where('phone', 'like', '%' . $phone . '%');
                }
            });

        $contacts = $query->paginate(
            perPage: $size,
            page: $page,
        );

        return (new ContactCollection($contacts))
            ->additional([
                'errors' => [],
            ]);
    }
}
