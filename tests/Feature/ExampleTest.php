<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_the_application_returns_a_successful_response()
    {
//        mostrar excepciones
        $this->withoutExceptionHandling();
        $update = json_encode();

        $response = $this->post('/api/telegram/webhook', $update);

        $response->assertStatus(200);
    }
}
