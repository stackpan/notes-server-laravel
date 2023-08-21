<?php

namespace Tests\Util;

use Illuminate\Testing\Fluent\AssertableJson;

class Note {

    public static function noteJsonAsserter(array $note): callable
    {
        return fn(AssertableJson $json) => $json
            ->whereAll([
                'id' => $note['id'],
                'title' => $note['title'],
                'body' => $note['body'],
                'createdAt' => $note['created_at'],
                'updatedAt' => $note['updated_at'],
                'tags' => $note['tags'],
                'username' => $note['user']['username']
            ]);
    }

}
