<?php

namespace Uplinkr\Tests\Support;

use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Support\Sanitizer;
use Uplinkr\Tests\TestCase;

/**
 * Class SanitizerTest
 * @package Uplinkr\Tests\Support
 */
class SanitizerTest extends TestCase
{
    private Sanitizer $sanitizer;

    /**
     * Prepares the test environment by setting up dependencies and configurations.
     * Initializes the Sanitizer with a real UplinkrConfig instance.
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $config = new UplinkrConfig(
            storagePath: 'uplinkr',
            standardProject: 'standard_project',
            fileExtension: 'log'
        );

        $this->sanitizer = new Sanitizer($config);
    }

    /**
     * Test that sanitizeProjectName replaces special characters with dashes.
     */
    public function testReplacesSpecialCharactersWithDashes(): void
    {
        $result = $this->sanitizer->project('project:name?<>*|');
        $this->assertSame('project-name', $result);
    }

    /**
     * Test that sanitizeProjectName trims extra dashes and whitespace.
     */
    public function testTrimsExtraDashesAndWhitespace(): void
    {
        $result = $this->sanitizer->project('  --project--name--  ');
        $this->assertSame('project-name', $result);
    }

    /**
     * Test that sanitizeProjectName lowers uppercase characters.
     */
    public function testLowercasesCharacters(): void
    {
        $result = $this->sanitizer->project('ProjectName');
        $this->assertSame('projectname', $result);
    }

    /**
     * Test that sanitizeProjectName removes control characters.
     */
    public function testRemovesControlCharacters(): void
    {
        $result = $this->sanitizer->project("project\u{001F}name");
        $this->assertSame('projectname', $result);
    }

    /**
     * Test that sanitizeProjectName replaces multiple spaces with a single dash.
     */
    public function testReplacesMultipleSpacesWithSingleDash(): void
    {
        $result = $this->sanitizer->project('project         name');
        $this->assertSame('project-name', $result);
    }

    /**
     * Test that sanitizeProjectName replaces consecutive dashes with a single dash.
     */
    public function testCollapsesConsecutiveDashes(): void
    {
        $result = $this->sanitizer->project('project---name');
        $this->assertSame('project-name', $result);
    }

    /**
     * Test that sanitizeProjectName returns standard_project for null input.
     */
    public function testReturnsStandardProjectForNull(): void
    {
        $result = $this->sanitizer->project(null);
        $this->assertSame('standard_project', $result);
    }
}