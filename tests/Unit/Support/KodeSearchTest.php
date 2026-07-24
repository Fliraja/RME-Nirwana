<?php

namespace Tests\Unit\Support;

use App\Support\KodeSearch;
use PHPUnit\Framework\TestCase;

class KodeSearchTest extends TestCase
{
    public function test_normalize_removes_dots(): void
    {
        $this->assertSame('9952', KodeSearch::normalize('99.52'));
        $this->assertSame('A031', KodeSearch::normalize('A03.1'));
    }

    public function test_normalize_removes_spaces(): void
    {
        $this->assertSame('9952', KodeSearch::normalize(' 99 52 '));
    }

    public function test_normalize_leaves_plain_term(): void
    {
        $this->assertSame('9952', KodeSearch::normalize('9952'));
        $this->assertSame('demam', KodeSearch::normalize('demam'));
    }
}
