<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Root web diarahkan ke dokumentasi interaktif Scramble.
     */
    public function test_root_mengarahkan_ke_dokumentasi_api(): void
    {
        $this->get('/')->assertRedirect('/docs/api');
    }
}
