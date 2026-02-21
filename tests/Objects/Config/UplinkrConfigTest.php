<?php

namespace Uplinkr\Tests\Objects\Config;

use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Tests\TestCase;

class UplinkrConfigTest extends TestCase
{
    public function test_default_values(): void
    {
        $config = new UplinkrConfig();

        $this->assertEquals('local', $config->getStorageDisc());
        $this->assertEquals('uplinkr', $config->getStoragePath());
        $this->assertEquals('probes', $config->getProbeResultsPath());
        $this->assertEquals('json', $config->getFileExtension());
        $this->assertEquals('@', $config->getProbeFilenameSeparator());
        $this->assertTrue($config->shouldPrettyPrintProbeResults());
        $this->assertEquals('archived', $config->getArchivedFolder());
        $this->assertEquals('standard_project', $config->getStandardProject());
        $this->assertEquals('enabled', $config->getStandardProjectStatus());
        $this->assertFalse($config->allowCompleteWipe());
        $this->assertEquals([], $config->getMailTo());
        $this->assertEquals('uplinkr', $config->getLogChannel());
        $this->assertEquals([], $config->getLogDefinition());
    }

    public function test_custom_values(): void
    {
        $config = new UplinkrConfig(
            storageDisk: 's3',
            storagePath: 'custom/path',
            probeResultsPath: 'custom_probes',
            standardLatency: 2000,
            probeFilenameSeparator: '#',
            fileExtension: 'log',
            prettyPrintProbeResults: false,
            archivedFolder: 'old',
            allowCompleteWipe: true,
            standardProject: 'my_project',
            standardProjectStatus: 'disabled',
            mailTo: ['test@example.com'],
            logChannel: 'custom_log',
            logDefinition: ['driver' => 'single']
        );

        $this->assertEquals('s3', $config->getStorageDisc());
        $this->assertEquals('custom/path', $config->getStoragePath());
        $this->assertEquals('custom_probes', $config->getProbeResultsPath());
        $this->assertEquals('log', $config->getFileExtension());
        $this->assertEquals('#', $config->getProbeFilenameSeparator());
        $this->assertFalse($config->shouldPrettyPrintProbeResults());
        $this->assertEquals('old', $config->getArchivedFolder());
        $this->assertEquals('my_project', $config->getStandardProject());
        $this->assertEquals('disabled', $config->getStandardProjectStatus());
        $this->assertTrue($config->allowCompleteWipe());
        $this->assertEquals(['test@example.com'], $config->getMailTo());
        $this->assertEquals('custom_log', $config->getLogChannel());
        $this->assertEquals(['driver' => 'single'], $config->getLogDefinition());
    }
}
