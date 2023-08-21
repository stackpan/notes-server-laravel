<?php

namespace App\Http\Requests;

use App\Models\Note;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Mockery\Matcher\Not;

class CollaborationCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'noteId' => ['required', 'exists:App\Models\Note,id'],
            'userId' => [
                'required',
                'exists:App\Models\User,id',
                Rule::unique('collaborations', 'user_id')
                    ->where('note_id', $this->noteId)
            ],
        ];
    }

    public function checkUserIdIsNotOwnerId(): void
    {
        $ownerId = Note::find($this->noteId)->user->id;

        if ($this->userId === $ownerId) {
            throw new HttpResponseException(response()
                ->json([
                    'status' => 'fail',
                    'message' => 'Gagal menambahkan karena user adalah pemilik catatan'
                ], 400)
                ->withHeaders([
                    'Content-Type' => 'application/json; charset=utf-8'
                ]),
            );
        }
    }
}
