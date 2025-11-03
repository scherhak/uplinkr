<?php

namespace Uplinkr\Tests\Translations;

use Uplinkr\Tests\TestCase;

class TranslationsTest extends TestCase
{
    /** @test */
    public function it_resolves_package_translation_keys()
    {
        app()->setLocale('en');

        $text = __('uplinkr::messages.checking', ['uri' => 'https://example.org']);

        $this->assertStringContainsString('Checking', $text);
        $this->assertStringContainsString('https://example.org', $text);
    }
}