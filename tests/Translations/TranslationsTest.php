<?php

namespace Uplinkr\Tests\Translations;

use PHPUnit\Framework\Attributes\Test;
use Uplinkr\Tests\TestCase;

/**
 * Class TranslationsTest
 * @package Uplinkr\Tests\Translations
 */
class TranslationsTest extends TestCase
{
    #[Test]
    public function it_resolves_package_translation_keys(): void
    {
        app()->setLocale('en');

        $text = __('uplinkr::messages.probe_checking', ['url' => 'https://uplinkr.dev']);

        $this->assertStringContainsString('Should the check of https://uplinkr.dev be started?', $text);
        $this->assertStringContainsString('uplinkr.dev', $text);
    }
}