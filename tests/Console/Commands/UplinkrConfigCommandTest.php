<?php

namespace Uplinkr\Tests\Console\Commands;

use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Support\CliIcon;
use Uplinkr\Tests\TestCase;

class UplinkrConfigCommandTest extends TestCase
{
    public function test_it_displays_values_with_markup_like_content_as_literal_text(): void
    {
        config()->set('uplinkr', [
            'section<key>' => [
                'danger' => '<error>boom</error>',
            ],
        ]);

        $this->artisan('uplinkr:config')
            ->expectsOutput(CliIcon::INFO->label('Current Uplinkr Configuration'))
            ->expectsOutputToContain('section<key>.danger: <error>boom</error>')
            ->assertExitCode(CommandAlias::SUCCESS);
    }

    public function test_it_displays_empty_arrays_explicitly(): void
    {
        config()->set('uplinkr', [
            'section' => [
                'empty_list' => [],
            ],
        ]);

        $this->artisan('uplinkr:config')
            ->expectsOutputToContain('section.empty_list: []')
            ->assertExitCode(CommandAlias::SUCCESS);
    }
}
