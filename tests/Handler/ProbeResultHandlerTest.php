<?php

namespace Uplinkr\Tests\Handler;

use PHPUnit\Framework\TestCase;
use Uplinkr\Handler\ProbeResultHandler;

class ProbeResultHandlerTest extends TestCase
{
    /**
     * Test that the build method correctly constructs the probe result with valid inputs.
     */
    public function testBuildMethodCorrectlyConstructsResult(): void
    {
        // Arrange
        $initialResult = [
            'status_header' => 200,
            'headers' => ['Content-Type' => 'application/json'],
        ];

        $durationTime = 1.523; // in seconds
        $probeMessage = [
            'message' => 'Success',
            'lang_key' => 'success.key',
        ];
        $status = 'reachable';
        $settings = [
            'protocol' => 'https',
            'url' => 'test.com',
        ];

        $handler = new ProbeResultHandler($initialResult);

        // Act
        $result = $handler->build($durationTime, $probeMessage, $status, $settings);

        // Assert
        $this->assertEquals($initialResult['status_header'], $result['status_header']);
        $this->assertEquals($initialResult['headers'], $result['headers']);
        $this->assertEquals($durationTime, $result['time_to_load']);
        $this->assertArrayHasKey('probe_message', $result);
        $this->assertSame('Success', $result['probe_message']['message']);
        $this->assertSame('success.key', $result['probe_message']['lang_key']);
        $this->assertEquals(1523, $result['probe_message']['duration_ms']);
        $this->assertEquals(1.52, $result['probe_message']['duration_s']);
        $this->assertEquals($status, $result['status']);
        $this->assertEquals($settings, $result['settings']);
        $this->assertArrayHasKey('executed', $result);
    }

    /**
     * Test that the build method works with an empty initial result array.
     */
    public function testBuildMethodHandlesEmptyInitialResult(): void
    {
        // Arrange
        $initialResult = [];
        $durationTime = 2.345;
        $probeMessage = [
            'message' => 'Unreachable',
            'lang_key' => 'error.unreachable',
        ];
        $status = 'unreachable';
        $settings = [
            'protocol' => 'http',
            'url' => 'example.com',
        ];

        $handler = new ProbeResultHandler($initialResult);

        // Act
        $result = $handler->build($durationTime, $probeMessage, $status, $settings);

        // Assert
        $this->assertEquals($durationTime, $result['time_to_load']);
        $this->assertArrayHasKey('probe_message', $result);
        $this->assertSame('Unreachable', $result['probe_message']['message']);
        $this->assertSame('error.unreachable', $result['probe_message']['lang_key']);
        $this->assertEquals(2345, $result['probe_message']['duration_ms']);
        $this->assertEquals(2.35, $result['probe_message']['duration_s']);
        $this->assertEquals($status, $result['status']);
        $this->assertEquals($settings, $result['settings']);
        $this->assertArrayHasKey('executed', $result);
    }

    /**
     * Test that the build method rounds the duration times correctly.
     */
    public function testBuildMethodRoundsDurationValuesCorrectly(): void
    {
        // Arrange
        $initialResult = [];
        $durationTime = 1.78965;
        $probeMessage = [];
        $status = 'reachable';
        $settings = [
            'protocol' => 'ftp',
            'url' => 'ftp.example.com',
        ];

        $handler = new ProbeResultHandler($initialResult);

        // Act
        $result = $handler->build($durationTime, $probeMessage, $status, $settings);

        // Assert
        $this->assertEquals(1.79, $result['probe_message']['duration_s']);
        $this->assertEquals(1789.65, $result['probe_message']['duration_ms']);
    }

    /**
     * Test that the build method correctly merges the initial result into the final result.
     */
    public function testBuildMethodMergesInitialResult(): void
    {
        // Arrange
        $initialResult = [
            'previous_data' => 'some_value',
        ];
        $durationTime = 0.567;
        $probeMessage = ['message' => 'Testing'];
        $status = 'not-reachable';
        $settings = [
            'protocol' => 'https',
            'url' => 'nonexistent.com',
        ];

        $handler = new ProbeResultHandler($initialResult);

        // Act
        $result = $handler->build($durationTime, $probeMessage, $status, $settings);

        // Assert
        $this->assertArrayHasKey('previous_data', $result);
        $this->assertSame('some_value', $result['previous_data']);
        $this->assertEquals($durationTime, $result['time_to_load']);
        $this->assertEquals($probeMessage['message'], $result['probe_message']['message']);
        $this->assertEquals($status, $result['status']);
        $this->assertEquals($settings, $result['settings']);
    }
}