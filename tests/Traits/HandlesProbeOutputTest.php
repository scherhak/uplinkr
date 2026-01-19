<?php

namespace Uplinkr\Tests\Traits;

use Illuminate\Console\Command;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Traits\HandlesProbeOutput;
use Uplinkr\Tests\TestCase;

class HandlesProbeOutputTest extends TestCase
{
    private $command;
    private $config;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->config = new UplinkrConfig(standardProject: 'default_project');
        
        // Create an anonymous class that uses the trait and mimics an Artisan command
        $this->command = new class extends Command {
            use HandlesProbeOutput;
            
            public array $outputs = [];
            public array $errors = [];

            public function info($string, $verbosity = null)
            {
                $this->outputs[] = $string;
            }

            public function error($string, $verbosity = null)
            {
                $this->errors[] = $string;
            }

            public function testResultMessages(array $result, ?string $project, UplinkrConfig $config)
            {
                $this->resultMessages($result, $project, $config);
            }
        };
    }

    public function test_it_outputs_reachable_message(): void
    {
        $result = [
            'probe_status' => 'reachable',
            'probe_message' => ['duration_ms' => 123.45]
        ];

        $this->command->testResultMessages($result, 'my-project', $this->config);

        $this->assertContains('Target URL is currently reachable (Response time: 123.45 ms)', $this->command->outputs);
        $this->assertContains('Result stored successfully in project my-project.', $this->command->outputs);
    }

    public function test_it_outputs_unreachable_message(): void
    {
        $result = [
            'probe_status' => 'unreachable',
            'status_header' => 500,
            'probe_message' => ['duration_ms' => 456.78]
        ];

        $this->command->testResultMessages($result, 'my-project', $this->config);

        $this->assertContains('Target URL is currently NOT reachable (Status response: 500 with response time: 456.78 ms)', $this->command->errors);
        $this->assertContains('Result stored successfully in project my-project.', $this->command->outputs);
    }

    public function test_it_uses_default_project_if_none_provided(): void
    {
        $result = [
            'probe_status' => 'reachable',
            'probe_message' => ['duration_ms' => 100]
        ];

        $this->command->testResultMessages($result, null, $this->config);

        $this->assertContains('Result stored successfully in project default_project.', $this->command->outputs);
    }
}
