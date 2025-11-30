<?php

namespace Uplinkr\Tests\Translations;

use Uplinkr\Tests\TestCase;

/**
 * Class TranslationsTest
 * @package Uplinkr\Tests\Translations
 */
class TranslationsTest extends TestCase
{
    /** @test */
    public function it_resolves_package_translation_keys(): void
    {
        app()->setLocale('en');

        $text = __('uplinkr::messages.url_checking', ['url' => 'https://uplinkr.dev']);

        $this->assertStringContainsString('Should the check of https://uplinkr.dev be started?', $text);
        $this->assertStringContainsString('uplinkr.dev', $text);
    }
}