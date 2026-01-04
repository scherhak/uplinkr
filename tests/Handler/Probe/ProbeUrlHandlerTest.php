<?php

namespace Uplinkr\Tests\Handler\Probe;

use Uplinkr\Tests\TestCase;
use Uplinkr\Handler\Probe\UrlHandler;
use Uplinkr\Handler\Probe\ResultHandler;
use Uplinkr\Interfaces\ProbeResultsStorageInterface;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Support\Sanitizer;

/**
 * Class ProbeUrlHandlerTest
 * @package Uplinkr\Tests\Handler
 */
class ProbeUrlHandlerTest extends TestCase
{
    private UrlHandler $probeUriHandler;

    /**
     * Prepares the test environment by setting up dependencies and configurations.
     * Initializes the UrlHandler with a mocked ProbeResultsStorageInterface and a real UplinkrConfig instance.
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $storageMock = $this->createMock(ProbeResultsStorageInterface::class);

        // Use a real config instance with default values for testing
        $config = new UplinkrConfig(
            storagePath: 'uplinkr',
            standardProject: 'standard_project',
            fileExtension: 'json'
        );

        $sanitizer = new Sanitizer($config);
        $resultHandler = new ResultHandler($config, $sanitizer);

        $this->probeUriHandler = new UrlHandler($storageMock, $config, $sanitizer, $resultHandler);
    }

    public function test_it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(UrlHandler::class, $this->probeUriHandler);
    }
}