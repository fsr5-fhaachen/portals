<?php

namespace Tests\Unit;

use App\Helpers\FormResponseStatistics;
use PHPUnit\Framework\TestCase;

class FormResponseStatisticsTest extends TestCase
{
    public function test_counts_scalar_values_per_field(): void
    {
        $result = FormResponseStatistics::count([
            ['lorem' => 'ipsum'],
            ['lorem' => 'dolor'],
            ['lorem' => 'ipsum'],
        ]);

        $this->assertSame('lorem', $result['lorem']['name']);
        $this->assertSame(2, $result['lorem']['ipsum']);
        $this->assertSame(1, $result['lorem']['dolor']);
    }

    public function test_counts_each_option_of_a_multi_select_answer(): void
    {
        // Regression: an array-valued answer used to throw
        // "Illegal offset type" and crash the statistics endpoint.
        $result = FormResponseStatistics::count([
            ['toppings' => ['cheese', 'bacon']],
            ['toppings' => ['cheese']],
        ]);

        $this->assertSame(2, $result['toppings']['cheese']);
        $this->assertSame(1, $result['toppings']['bacon']);
    }

    public function test_skips_null_answers_and_non_scalar_entries(): void
    {
        $result = FormResponseStatistics::count([
            ['note' => null, 'nested' => [['deep' => 'x']], 'ok' => 'yes'],
        ]);

        $this->assertArrayNotHasKey('note', $result);
        $this->assertArrayNotHasKey('nested', $result);
        $this->assertSame(1, $result['ok']['yes']);
    }

    public function test_empty_input_returns_empty_result(): void
    {
        $this->assertSame([], FormResponseStatistics::count([]));
    }
}
