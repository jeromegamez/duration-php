<?php

declare(strict_types=1);

namespace Gamez;

use DateInterval;
use DateTimeImmutable;
use Gamez\Duration\Exception\InvalidDuration;
use JsonSerializable;
use Throwable;

final class Duration extends DateInterval implements JsonSerializable
{
    private const NONE = 'PT0S';

    /**
     * @param string $spec An interval/duration specification
     *
     * @throws InvalidDuration if the specification cannot be parsed
     */
    public function __construct(string $spec)
    {
        try {
            parent::__construct($spec);
        } catch (Throwable $e) {
            throw InvalidDuration::because($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @param mixed $value An interval/duration
     *
     * @throws InvalidDuration if the specification cannot be parsed
     */
    public static function make($value): self
    {
        if ($value instanceof DateInterval) {
            return new self(self::toDateIntervalSpec(self::normalizeInterval($value)));
        }

        if (in_array($value, [0, null, false, true], true)) {
            return self::none();
        }

        if (is_object($value) && !method_exists($value, '__toString')) {
            throw InvalidDuration::because('The given object cannot be converted to a string');
        }

        $stringValue = trim((string) $value);

        if ('' === $value) {
            return self::none();
        }

        if (ctype_digit($stringValue)) {
            throw InvalidDuration::because('A duration needs a unit');
        }

        if (preg_match('/^(\d+):(\d+)$/', $stringValue)) {
            [$minutes, $seconds] = array_map('intval', explode(':', $stringValue));

            return new self("PT{$minutes}M{$seconds}S");
        }

        if (preg_match('/^(\d+):(\d+):(\d+)$/', $stringValue)) {
            [$hours, $minutes, $seconds] = array_map('intval', explode(':', $stringValue));

            return new self("PT{$hours}H{$minutes}M{$seconds}S");
        }

        if (0 === strpos($stringValue, 'P')) {
            return new self(
                self::toDateIntervalSpec(
                    self::normalizeInterval(
                        new self($stringValue)
                    )
                )
            );
        }

        try {
            $interval = DateInterval::createFromDateString($stringValue);
        } catch (Throwable $e) {
            throw InvalidDuration::because("'{$stringValue}' is not a valid duration");
        }

        return new self(
            self::toDateIntervalSpec(
                self::normalizeInterval($interval)
            )
        );
    }

    public static function none(): self
    {
        return new self(self::NONE);
    }

    /**
     * @param mixed $duration An interval/duration
     */
    public function withAdded($duration): self
    {
        $duration = $duration instanceof self ? $duration : self::make($duration);

        $now = self::now();
        $then = $now->add($this->toDateInterval())->add($duration->toDateInterval());

        return self::make($then->diff($now, true));
    }

    /**
     * @param mixed $duration An interval/duration
     */
    public function withSubtracted($duration): self
    {
        $duration = $duration instanceof self ? $duration : self::make($duration);

        $now = self::now();
        $then = $now->add($this->toDateInterval())->sub($duration->toDateInterval());

        if ($then < $now) {
            throw InvalidDuration::because('A duration cannot be smaller than zero');
        }

        return self::make($then->diff($now, true));
    }

    /**
     * @param int|float $multiplicator
     */
    public function multipliedBy($multiplicator): self
    {
        if ($multiplicator < 0) {
            throw InvalidDuration::because('A duration cannot be multiplied with a value smaller than zero');
        }

        $now = self::now();
        $there = $now->add($this->toDateInterval());

        $durationInSeconds = $there->getTimestamp() - $now->getTimestamp();
        $result = (int) round($durationInSeconds * $multiplicator);

        return self::make($result.' seconds');
    }

    /**
     * @param int|float $divisor
     */
    public function dividedBy($divisor): self
    {
        $now = self::now();
        $there = $now->add($this->toDateInterval());

        $durationInSeconds = $there->getTimestamp() - $now->getTimestamp();
        $result = (int) round($durationInSeconds / $divisor);

        return self::make($result.' seconds');
    }

    /**
     * @param mixed $other An interval/duration
     */
    public function isLargerThan($other): bool
    {
        return 1 === $this->compareTo($other);
    }

    /**
     * @param mixed $other An interval/duration
     */
    public function equals($other): bool
    {
        return 0 === $this->compareTo($other);
    }

    /**
     * @param mixed $other An interval/duration
     */
    public function isSmallerThan($other): bool
    {
        return -1 === $this->compareTo($other);
    }

    /**
     * @param mixed $other An interval/duration
     */
    public function diff($other): self
    {
        $other = $other instanceof self ? $other : self::make($other);

        $now = self::now();
        $here = $now->add($this->toDateInterval());
        $there = $now->add($other->toDateInterval());

        return self::make($here->diff($there, true));
    }

    /**
     * @param mixed $other An interval/duration
     */
    public function compareTo($other): int
    {
        $other = $other instanceof self ? $other : self::make($other);

        $now = self::now();
        $here = $now->add($this->toDateInterval());
        $there = $now->add($other->toDateInterval());

        return $here <=> $there;
    }

    public function toDateInterval(): DateInterval
    {
        return $this;
    }

    public function jsonSerialize(): string
    {
        return self::toDateIntervalSpec($this);
    }

    public function __toString(): string
    {
        return self::toDateIntervalSpec($this);
    }

    private static function now(): DateTimeImmutable
    {
        static $now;

        /* @noinspection PhpUnhandledExceptionInspection */
        return $now = $now ?? new DateTimeImmutable('@'.time());
    }

    private static function normalizeInterval(DateInterval $value): DateInterval
    {
        $now = self::now();
        $then = $now->add($value);

        return $now->diff($then);
    }

    private static function toDateIntervalSpec(DateInterval $interval): string
    {
        $spec = 'P';
        $spec .= 0 !== $interval->y ? $interval->y.'Y' : '';
        $spec .= 0 !== $interval->m ? $interval->m.'M' : '';
        $spec .= 0 !== $interval->d ? $interval->d.'D' : '';

        $spec .= 'T';
        $spec .= 0 !== $interval->h ? $interval->h.'H' : '';
        $spec .= 0 !== $interval->i ? $interval->i.'M' : '';
        $spec .= 0 !== $interval->s ? $interval->s.'S' : '';

        if ('T' === substr($spec, -1)) {
            $spec = substr($spec, 0, -1);
        }

        if ('P' === $spec) {
            return self::NONE;
        }

        return $spec;
    }
}
