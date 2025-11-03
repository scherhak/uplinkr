<?php

namespace Uplinkr\Tests\Translations;

use Uplinkr\Tests\TestCase;

class TranslationsTest extends TestCase
{
    /** @test */
    public function it_resolves_package_translation_keys(): void
    {
        app()->setLocale('en');

        $text = __('uplinkr::messages.checking', ['uri' => 'scherhak.com']);

        $this->assertStringContainsString('Checking', $text);
        $this->assertStringContainsString('scherhak.com', $text);
    }
}