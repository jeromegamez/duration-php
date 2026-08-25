<?php

declare(strict_types=1);

namespace Gamez\Duration\Tests;

use DateInterval;
use DateTimeImmutable;
use Gamez\Duration;
use Gamez\Duration\Exception\InvalidDuration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

class DurationTest extends TestCase
{
    public function testItCanBeNone(): void
    {
        $now = new DateTimeImmutable();

        $this->assertEquals($now, $now->add(Duration::none()));
    }

    #[DataProvider('validValues')]
    public function testItParsesAValue(mixed $value, string $expectedSpec): void
    {
        $this->assertSame($expectedSpec, (string) Duration::make($value));
    }

    /**
     * @return array<string, array{0: mixed, 1: string}>
     */
    public static function validValues(): array
    {
        return [
            'nothing (null)' => [null, 'PT0S'],
            'nothing (false)' => [false, 'PT0S'],
            'nothing (true)' => [true, 'PT0S'],
            'nothing ("")' => ['', 'PT0S'],
            'textual ("13 minutes 37 seconds")' => ['13 minutes 37 seconds', 'PT13M37S'],
            'minutes:seconds ("01:23")' => ['01:23', 'PT1M23S'],
            'hours:minutes:seconds ("01:23:45")' => ['01:23:45', 'PT1H23M45S'],
            'DateInterval Spec ("P1DT1H")' => ['P1DT1H', 'P1DT1H'],
            'DateInterval("PT24H")' => [new DateInterval('PT24H'), 'P1D'],
            'Duration("PT24H")' => [Duration::make('PT24H'), 'P1D'],
            'too verbose' => [Duration::make('P0Y0M0DT0H0M3600S'), 'PT1H'],
            'object with __toString()' => [new class() {
                public function __toString()
                {
                    return 'PT1H';
                }
            }, 'PT1H'],
        ];
    }

    public function testItsConstructorThrowsInvalidDurationErrors(): void
    {
        $this->expectException(InvalidDuration::class);
        new Duration('nonsense');
    }

    #[DataProvider('invalidValues')]
    public function testItRejectsInvalidValuesWhenUsingMake(mixed $value): void
    {
        $this->expectException(InvalidDuration::class);
        Duration::make($value);
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function invalidValues(): array
    {
        return [
            'object without __toString' => [new stdClass()],
            'nonsense' => ['nonsense'],
        ];
    }

    public function testItNeedsAUnit(): void
    {
        $this->expectException(InvalidDuration::class);
        Duration::make(60);
    }

    public function testItNeedsAParseableValue(): void
    {
        $this->expectException(InvalidDuration::class);
        Duration::make('xxx');
    }

    public function testItOptimizesTheDateIntervalSpec(): void
    {
        $this->assertSame('P1DT1H', (string) Duration::make('PT24H60M'));
    }

    public function testItCanBeAdded(): void
    {
        $first = Duration::make('22 hours');
        $second = Duration::make('17 minutes');
        $expected = Duration::make('PT1337M');

        $this->assertTrue($expected->equals($first->withAdded($second)));
    }

    public function testItCanBeSubtracted(): void
    {
        $first = Duration::make('23 hours');
        $second = Duration::make('43 minutes');
        $expected = Duration::make('PT1337M');

        $this->assertTrue($expected->equals($first->withSubtracted($second)));
    }

    public function testItCanNotResultInANegativeValue(): void
    {
        $this->expectException(InvalidDuration::class);
        Duration::none()->withSubtracted(Duration::make('1 second'));
    }

    public function testItCanBeDivided(): void
    {
        $given = Duration::make('13 minutes');
        $divisor = 2;
        $expected = Duration::make('PT6M30S');

        $this->assertTrue($expected->equals($given->dividedBy($divisor)));
    }

    public function testItCanBeMultiplied(): void
    {
        $given = Duration::make('13 minutes');
        $multiplicator = 2;
        $expected = Duration::make('PT26M');

        $this->assertTrue($expected->equals($given->multipliedBy($multiplicator)));
    }

    public function testItCanNotBeMultipliedWithANegativeValue(): void
    {
        $this->expectException(InvalidDuration::class);
        Duration::none()->multipliedBy(-1.1);
    }

    public function testItRoundsDividedSeconds(): void
    {
        $given = Duration::make('13 seconds');
        $divisor = 2;
        $expected = Duration::make('PT7S');

        $this->assertTrue($expected->equals($given->dividedBy($divisor)));
    }

    public function testItCanBeCompared(): void
    {
        $given = Duration::make('60 minutes');
        $equal = Duration::make('1 hour');
        $larger = Duration::make('61 minutes');
        $smaller = Duration::make('59 minutes');

        $this->assertTrue($given->equals($equal));
        $this->assertTrue($given->isLargerThan($smaller));
        $this->assertTrue($given->isSmallerThan($larger));
    }

    public function testItKnowsTheDifference(): void
    {
        $first = Duration::make('58 minutes');
        $second = Duration::make('2 hours 5 minutes');

        $difference = $first->diff($second);

        $expected = Duration::make('1 hour 7 minutes');

        $this->assertTrue($expected->equals($difference));
    }

    public function testItCanBeCastedToADateIntervalSpecString(): void
    {
        $this->assertSame('PT1H', (string) Duration::make('1 hour'));
    }

    public function testItCanBeJsonEncodedToADateIntervalSpecString(): void
    {
        $this->assertSame('"PT1H"', json_encode(Duration::make('1 hour')));
    }
}
