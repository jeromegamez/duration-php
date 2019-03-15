<?php

declare(strict_types=1);

namespace Gamez\Duration\Tests;

use DateInterval;
use DateTimeImmutable;
use Gamez\Duration;
use Gamez\Duration\Exception\InvalidDuration;
use PHPUnit\Framework\TestCase;

class DurationTest extends TestCase
{
    /** @test */
    public function it_can_be_none()
    {
        $now = new DateTimeImmutable();

        $this->assertEquals($now, $now->add(Duration::none()->toDateInterval()));
    }

    /**
     * @test
     * @dataProvider validValues
     */
    public function it_parses_a_value($value, $expectedSpec)
    {
        $this->assertSame($expectedSpec, (string) Duration::make($value));
    }

    public function validValues()
    {
        return [
            'nothing ("")' => ['', 'PT0S'],
            'textual ("13 minutes 37 seconds")' => ['13 minutes 37 seconds', 'PT13M37S'],
            'minutes:seconds ("01:23")' => ['01:23', 'PT1M23S'],
            'hours:minutes:seconds ("01:23:45")' => ['01:23:45', 'PT1H23M45S'],
            'DateInterval Spec ("P1DT1H")' => ['P1DT1H', 'P1DT1H'],
            'DateInterval("PT24H")' => [new DateInterval('PT24H'), 'P1D'],
            'Duration("PT24H")' => [Duration::make('PT24H'), 'P1D'],
            'too verbose' => [Duration::make('P0Y0M0DT0H0M3600S'), 'PT1H'],
        ];
    }

    /** @test */
    public function it_optimizes_the_date_interval_spec()
    {
        $this->assertSame('P1DT1H', (string) Duration::make('PT24H60M'));
    }

    /** @test */
    public function it_can_be_added()
    {
        $first = Duration::make('22 hours');
        $second = Duration::make('17 minutes');
        $expected = Duration::make('PT1337M');

        $this->assertTrue($expected->equals($first->withAdded($second)));
    }

    /** @test */
    public function it_can_be_subtracted()
    {
        $first = Duration::make('23 hours');
        $second = Duration::make('43 minutes');
        $expected = Duration::make('PT1337M');

        $this->assertTrue($expected->equals($first->withSubtracted($second)));
    }

    /** @test */
    public function it_can_not_result_in_a_negative_value()
    {
        $this->expectException(InvalidDuration::class);
        Duration::none()->withSubtracted(Duration::make('1 second'));
    }

    /** @test */
    public function it_can_be_divided()
    {
        $given = Duration::make('13 minutes');
        $divisor = 2;
        $expected = Duration::make('PT6M30S');

        $this->assertTrue($expected->equals($given->dividedBy($divisor)));
    }

    /** @test */
    public function it_can_be_multiplied()
    {
        $given = Duration::make('13 minutes');
        $multiplicator = 2;
        $expected = Duration::make('PT26M');

        $this->assertTrue($expected->equals($given->multipliedBy($multiplicator)));
    }

    /** @test */
    public function it_can_not_be_multiplied_with_a_negative_value()
    {
        $this->expectException(InvalidDuration::class);
        Duration::none()->multipliedBy(-1.1);
    }

    /** @test */
    public function it_rounds_divided_seconds()
    {
        $given = Duration::make('13 seconds');
        $divisor = 2;
        $expected = Duration::make('PT7S');

        $this->assertTrue($expected->equals($given->dividedBy($divisor)));
    }

    /** @test */
    public function it_can_be_compared()
    {
        $given = Duration::make('60 minutes');
        $equal = Duration::make('1 hour');
        $larger = Duration::make('61 minutes');
        $smaller = Duration::make('59 minutes');

        $this->assertTrue($given->equals($equal));
        $this->assertTrue($given->isLargerThan($smaller));
        $this->assertTrue($given->isSmallerThan($larger));
    }

    /** @test */
    public function it_knows_the_difference()
    {
        $first = Duration::make('58 minutes');
        $second = Duration::make('2 hours 5 minutes');

        $difference = $first->diff($second);

        $expected = Duration::make('1 hour 7 minutes');

        $this->assertTrue($expected->equals($difference));
    }

    /** @test */
    public function it_can_be_casted_to_a_date_interval_spec_string()
    {
        $this->assertSame('PT1H', (string) Duration::make('1 hour'));
    }

    /** @test */
    public function it_can_be_json_encoded_to_a_date_interval_spec_string()
    {
        $this->assertSame('"PT1H"', json_encode(Duration::make('1 hour')));
    }
}
