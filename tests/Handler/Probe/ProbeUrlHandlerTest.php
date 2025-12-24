<?php

namespace Handler\Probe;

use PHPUnit\Framework\TestCase;
use Uplinkr\Handler\Probe\UrlHandler;
use Uplinkr\Interfaces\StorageInterface;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Support\Sanitizer;

/**
 * Class ProbeUrlHandlerTest
 * @package Uplinkr\Tests\Handler
 */
class ProbeUrlHandlerTest extends TestCase
{
    /**
     * Prepares the test environment by setting up dependencies and configurations.
     * Initializes the UrlHandler with a mocked StorageInterface and a real UplinkrConfig instance.
     * @return void
     */
    protected function setUp(): void
    {
        $storageMock = $this->createMock(StorageInterface::class);

        // Use a real config instance with default values for testing
        $config = new UplinkrConfig(
            storagePath: 'uplinkr',
            standardProject: 'standard_project',
            fileExtension: 'json'
        );

        $sanitizer = new Sanitizer($config);

        $this->probeUriHandler = new UrlHandler($storageMock, $config, $sanitizer);
    }
}