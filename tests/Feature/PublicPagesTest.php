<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_return_200(): void
    {
        $this->get('/')
            ->assertOk();

        $this->get('/pricing')
            ->assertOk();

        $this->get('/demo')
            ->assertOk();

        $this->get('/privacy')
            ->assertOk();

        $this->get('/terms')
            ->assertOk();
    }
}
