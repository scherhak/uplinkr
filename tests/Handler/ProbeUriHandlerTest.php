<?php

namespace Uplinkr\Tests\Handler;

use PHPUnit\Framework\TestCase;
use Uplinkr\Handler\ProbeUriHandler;
use Uplinkr\Interfaces\StorageInterface;

/**
 * Class ProbeUriHandlerTest
 *
 * This class tests the sanitizeProjectAndFileName method in the ProbeUriHandler class.
 * The method sanitizes file and project names by removing or replacing problematic characters.
 */
class ProbeUriHandlerTest extends TestCase
{
    private ProbeUriHandler $probeUriHandler;

    protected function setUp(): void
    {
        $storageMock = $this->createMock(StorageInterface::class);
        $this->probeUriHandler = new ProbeUriHandler($storageMock);
    }

    /**
     * Test that sanitizeProjectAndFileName replaces special characters with dashes.
     */
    public function testReplacesSpecialCharactersWithDashes(): void
    {
        $result = $this->invokeSanitizeMethod('project:name?<>*|');
        $this->assertSame('project-name', $result);
    }

    /**
     * Test that sanitizeProjectAndFileName trims extra dashes and whitespace.
     */
    public function testTrimsExtraDashesAndWhitespace(): void
    {
        $result = $this->invokeSanitizeMethod('  --project--name--  ');
        $this->assertSame('project-name', $result);
    }

    /**
     * Test that sanitizeProjectAndFileName lowers uppercase characters.
     */
    public function testLowercasesCharacters(): void
    {
        $result = $this->invokeSanitizeMethod('ProjectName');
        $this->assertSame('projectname', $result);
    }

    /**
     * Test that sanitizeProjectAndFileName removes control characters.
     */
    public function testRemovesControlCharacters(): void
    {
        $result = $this->invokeSanitizeMethod("project\u{001F}name");
        $this->assertSame('projectname', $result);
    }

    /**
     * Test that sanitizeProjectAndFileName replaces multiple spaces with a single dash.
     */
    public function testReplacesMultipleSpacesWithSingleDash(): void
    {
        $result = $this->invokeSanitizeMethod('project         name');
        $this->assertSame('project-name', $result);
    }

    /**
     * Test that sanitizeProjectAndFileName replaces consecutive dashes with a single dash.
     */
    public function testCollapsesConsecutiveDashes(): void
    {
        $result = $this->invokeSanitizeMethod('project---name');
        $this->assertSame('project-name', $result);
    }

    /**
     * Helper method to invoke the private sanitizeProjectAndFileName method.
     *
     * @param string $value The value to be sanitized.
     * @return string The sanitized string.
     */
    private function invokeSanitizeMethod(string $value): string
    {
        $reflection = new \ReflectionClass($this->probeUriHandler);
        $method = $reflection->getMethod('sanitizeProjectAndFileName');
        $method->setAccessible(true);

        return $method->invoke($this->probeUriHandler, $value);
    }
}