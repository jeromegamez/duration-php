<?php

declare(strict_types=1);

namespace Gamez;

use DateInterval;
use DateTimeImmutable;
use Gamez\Duration\Exception\InvalidDuration;
use JsonSerializable;
use Stringable;
use Throwable;

final class Duration extends DateInterval implements JsonSerializable
{
    private const string NONE = 'PT0S';

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
     * @throws InvalidDuration if the specification cannot be parsed
     */
    public static function make(mixed $duration): self
    {
        if ($duration instanceof DateInterval) {
            return new self(self::toDateIntervalSpec(self::normalizeInterval($duration)));
        }

        if (in_array($duration, [0, null, false, true], true)) {
            return self::none();
        }

        if (is_object($duration) && !method_exists($duration, '__toString')) {
            throw InvalidDuration::because('The given object cannot be converted to a string');
        }

        if (!is_scalar($duration) && !($duration instanceof Stringable)) {
            throw InvalidDuration::because('A duration can only be created from a scalar value');
        }

        $stringValue = trim((string) $duration);

        if ('' === $stringValue) {
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

        if (str_starts_with($stringValue, 'P')) {
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

    public function withAdded(mixed $duration): self
    {
        $duration = $duration instanceof self ? $duration : self::make($duration);

        $now = self::now();
        $then = $now->add($this)->add($duration);

        return self::make($then->diff($now, true));
    }

    public function withSubtracted(mixed $duration): self
    {
        $duration = $duration instanceof self ? $duration : self::make($duration);

        $now = self::now();
        $then = $now->add($this)->sub($duration);

        if ($then < $now) {
            throw InvalidDuration::because('A duration cannot be smaller than zero');
        }

        return self::make($then->diff($now, true));
    }

    public function multipliedBy(int|float $multiplicator): self
    {
        if ($multiplicator < 0) {
            throw InvalidDuration::because('A duration cannot be multiplied with a value smaller than zero');
        }

        $now = self::now();
        $there = $now->add($this);

        $durationInSeconds = $there->getTimestamp() - $now->getTimestamp();
        $result = (int) round($durationInSeconds * $multiplicator);

        return self::make($result.' seconds');
    }

    public function dividedBy(int|float $divisor): self
    {
        $now = self::now();
        $there = $now->add($this);

        $durationInSeconds = $there->getTimestamp() - $now->getTimestamp();
        $result = (int) round($durationInSeconds / $divisor);

        return self::make($result.' seconds');
    }

    public function isLargerThan(mixed $other): bool
    {
        return 1 === $this->compareTo($other);
    }

    public function equals(mixed $other): bool
    {
        return 0 === $this->compareTo($other);
    }

    public function isSmallerThan(mixed $other): bool
    {
        return -1 === $this->compareTo($other);
    }

    public function diff(mixed $other): self
    {
        $other = $other instanceof self ? $other : self::make($other);

        $now = self::now();
        $here = $now->add($this);
        $there = $now->add($other);

        return self::make($here->diff($there, true));
    }

    public function compareTo(mixed $other): int
    {
        $other = $other instanceof self ? $other : self::make($other);

        $now = self::now();
        $here = $now->add($this);
        $there = $now->add($other);

        return $here <=> $there;
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
        /** @var DateTimeImmutable|null $now */
        static $now = null;

        if ($now === null) {
            $now = new DateTimeImmutable('@'.time());
        }

        return $now;
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

        if (str_ends_with($spec, 'T')) {
            $spec = substr($spec, 0, -1);
        }

        if ('P' === $spec) {
            return self::NONE;
        }

        return $spec;
    }
}
