<?php

namespace Uplinkr\Tests\Handler;

use PHPUnit\Framework\TestCase;
use Uplinkr\Handler\ProbeUriHandler;
use Uplinkr\Interfaces\StorageInterface;
use Uplinkr\Objects\Config\UplinkrConfig;

/**
 * Class ProbeUriHandlerTest
 *
 * This class tests the sanitizeProjectName method in the ProbeUriHandler class.
 * The method sanitizes file and project names by removing or replacing problematic characters.
 */
class ProbeUriHandlerTest extends TestCase
{
    private ProbeUriHandler $probeUriHandler;

    protected function setUp(): void
    {
        $storageMock = $this->createMock(StorageInterface::class);

        // Use a real config instance with default values for testing
        $config = new UplinkrConfig(
            storagePath: 'uplinkr',
            standardProject: 'standard_project',
            fileExtension: 'log'
        );

        $this->probeUriHandler = new ProbeUriHandler($storageMock, $config);
    }

    /**
     * Test that sanitizeProjectName replaces special characters with dashes.
     */
    public function testReplacesSpecialCharactersWithDashes(): void
    {
        $result = $this->invokeSanitizeMethod('project:name?<>*|');
        $this->assertSame('project-name', $result);
    }

    /**
     * Test that sanitizeProjectName trims extra dashes and whitespace.
     */
    public function testTrimsExtraDashesAndWhitespace(): void
    {
        $result = $this->invokeSanitizeMethod('  --project--name--  ');
        $this->assertSame('project-name', $result);
    }

    /**
     * Test that sanitizeProjectName lowers uppercase characters.
     */
    public function testLowercasesCharacters(): void
    {
        $result = $this->invokeSanitizeMethod('ProjectName');
        $this->assertSame('projectname', $result);
    }

    /**
     * Test that sanitizeProjectName removes control characters.
     */
    public function testRemovesControlCharacters(): void
    {
        $result = $this->invokeSanitizeMethod("project\u{001F}name");
        $this->assertSame('projectname', $result);
    }

    /**
     * Test that sanitizeProjectName replaces multiple spaces with a single dash.
     */
    public function testReplacesMultipleSpacesWithSingleDash(): void
    {
        $result = $this->invokeSanitizeMethod('project         name');
        $this->assertSame('project-name', $result);
    }

    /**
     * Test that sanitizeProjectName replaces consecutive dashes with a single dash.
     */
    public function testCollapsesConsecutiveDashes(): void
    {
        $result = $this->invokeSanitizeMethod('project---name');
        $this->assertSame('project-name', $result);
    }

    /**
     * Test that sanitizeProjectName returns standard_project for null input.
     */
    public function testReturnsStandardProjectForNull(): void
    {
        $result = $this->invokeSanitizeMethod(null);
        $this->assertSame('standard_project', $result);
    }

    /**
     * Helper method to invoke the private sanitizeProjectName method.
     *
     * @param string|null $value The value to be sanitized.
     * @return string The sanitized string.
     */
    private function invokeSanitizeMethod(string|null $value): string
    {
        $reflection = new \ReflectionClass($this->probeUriHandler);
        $method = $reflection->getMethod('sanitizeProjectName');
        $method->setAccessible(true);

        return $method->invoke($this->probeUriHandler, $value);
    }
}