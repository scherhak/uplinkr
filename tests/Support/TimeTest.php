<?php

namespace Uplinkr\Tests\Support;

use Uplinkr\Support\Time;
use Uplinkr\Tests\TestCase;

class TimeTest extends TestCase
{
    public function test_now_returns_formatted_string(): void
    {
        $now = Time::now();
        
        $this->assertIsString($now);
        // Format YYYY-MM-DD HH:MM:SS
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $now);
    }
}
