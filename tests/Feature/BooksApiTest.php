<?php

namespace Tests\Feature;

use Tests\TestCase;

class BooksApiTest extends TestCase
{
    public function test_books_endpoint_responds(): void
    {
        $response = $this->get('/api/books');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'count', 'next', 'previous', 'results'
        ]);
    }
}


