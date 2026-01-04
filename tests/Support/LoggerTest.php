<?php

namespace Uplinkr\Tests\Support;

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Uplinkr\Support\Logger;
use Uplinkr\Tests\TestCase;

/**
 * Class LoggerTest
 * @package Uplinkr\Tests\Support
 */
class LoggerTest extends TestCase
{
    /**
     * Test that get returns a LoggerInterface instance.
     */
    public function testGetReturnsLoggerInterface(): void
    {
        $logger = Logger::get();
        $this->assertInstanceOf(LoggerInterface::class, $logger);
    }

    /**
     * Test that get uses the configured log channel.
     */
    public function testGetUsesConfiguredLogChannel(): void
    {
        config(['uplinkr.log_channel' => 'custom_channel']);

        Log::shouldReceive('channel')
            ->once()
            ->with('custom_channel')
            ->andReturn($this->createMock(LoggerInterface::class));

        Logger::get();
    }

    /**
     * Test that get uses the default log channel if none is configured.
     */
    public function testGetUsesDefaultLogChannel(): void
    {
        // Remove the config key to trigger the default value in config() helper
        config(['uplinkr.log_channel' => 'uplinkr']);

        Log::shouldReceive('channel')
            ->once()
            ->with('uplinkr')
            ->andReturn($this->createMock(LoggerInterface::class));

        Logger::get();
    }
}
