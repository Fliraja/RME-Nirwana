<?php

namespace Tests\Unit\Support;

use App\Support\PrioritasResolver;
use PHPUnit\Framework\TestCase;

class PrioritasResolverTest extends TestCase
{
    public function test_valid_item_becomes_primary_regardless_of_input_order(): void
    {
        $items = [
            ['kode' => 'A', 'validcode' => '0'],
            ['kode' => 'B', 'validcode' => '0'],
            ['kode' => 'C', 'validcode' => '1'],
        ];
        // C primer (1); sisanya urut input: A=2, B=3
        $this->assertSame(['C' => 1, 'A' => 2, 'B' => 3], PrioritasResolver::hitung($items));
    }

    public function test_first_valid_wins_when_multiple_valid(): void
    {
        $items = [
            ['kode' => 'A', 'validcode' => '1'],
            ['kode' => 'B', 'validcode' => '1'],
        ];
        $this->assertSame(['A' => 1, 'B' => 2], PrioritasResolver::hitung($items));
    }

    public function test_fallback_first_item_when_no_valid(): void
    {
        $items = [
            ['kode' => 'A', 'validcode' => '0'],
            ['kode' => 'B', 'validcode' => '0'],
        ];
        $this->assertSame(['A' => 1, 'B' => 2], PrioritasResolver::hitung($items));
    }

    public function test_dedupe_keeps_first_occurrence(): void
    {
        $items = [
            ['kode' => 'A', 'validcode' => '0'],
            ['kode' => 'A', 'validcode' => '0'],
            ['kode' => 'B', 'validcode' => '1'],
        ];
        $this->assertSame(['B' => 1, 'A' => 2], PrioritasResolver::hitung($items));
    }

    public function test_empty(): void
    {
        $this->assertSame([], PrioritasResolver::hitung([]));
    }
}
