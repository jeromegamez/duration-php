<?php

declare(strict_types=1);

namespace Gamez;

use DateInterval;
use DateTimeImmutable;
use Gamez\Duration\Exception\InvalidDuration;

final class Duration implements \JsonSerializable
{
    private const NONE = 'PT0S';

    /**
     * @var DateInterval
     */
    private $value;

    private function __construct(DateInterval $dateInterval)
    {
        $this->value = $this->normalizeInterval($dateInterval);
    }

    public static function make($value): self
    {
        if ($value instanceof DateInterval) {
            return new self($value);
        }

        $stringValue = (string) $value;

        if (preg_match('/^(\d+):(\d+)$/', $stringValue)) {
            [$minutes, $seconds] = array_map('intval', explode(':', $stringValue));

            return new self(new DateInterval("PT{$minutes}M{$seconds}S"));
        }

        if (preg_match('/^(\d+):(\d+):(\d+)$/', $stringValue)) {
            [$hours, $minutes, $seconds] = array_map('intval', explode(':', $stringValue));

            return new self(new DateInterval("PT{$hours}H{$minutes}M{$seconds}S"));
        }

        if (0 === strpos($stringValue, 'P')) {
            return new self(new DateInterval($stringValue));
        }

        return new self(DateInterval::createFromDateString($stringValue));
    }

    public static function none(): self
    {
        return new self(new DateInterval(self::NONE));
    }

    public function withAdded($duration): self
    {
        $duration = $duration instanceof self ? $duration : self::make($duration);

        $now = $this->now();
        $then = $now->add($this->toDateInterval())->add($duration->toDateInterval());

        return new self($then->diff($now, true));
    }

    public function withSubtracted($duration): self
    {
        $duration = $duration instanceof self ? $duration : self::make($duration);

        $now = $this->now();
        $then = $now->add($this->toDateInterval())->sub($duration->toDateInterval());

        if ($then < $now) {
            throw InvalidDuration::because('A duration cannot be smaller than zero');
        }

        return new self($then->diff($now, true));
    }

    public function multipliedBy($multiplicator): self
    {
        if ($multiplicator < 0) {
            throw InvalidDuration::because('A duration cannot be multiplied with a value smaller than zero');
        }

        $now = $this->now();
        $there = $now->add($this->toDateInterval());

        $durationInSeconds = $there->getTimestamp() - $now->getTimestamp();
        $result = (int) round($durationInSeconds * $multiplicator);

        return self::make($result.' seconds');
    }

    public function dividedBy($divisor): self
    {
        $now = $this->now();
        $there = $now->add($this->toDateInterval());

        $durationInSeconds = $there->getTimestamp() - $now->getTimestamp();
        $result = (int) round($durationInSeconds / $divisor);

        return self::make($result.' seconds');
    }

    public function isLargerThan($other): bool
    {
        return 1 === $this->compareTo($other);
    }

    public function equals($other): bool
    {
        return 0 === $this->compareTo($other);
    }

    public function isSmallerThan($other): bool
    {
        return -1 === $this->compareTo($other);
    }

    public function diff($other): self
    {
        $other = $other instanceof self ? $other : self::make($other);

        $now = $this->now();
        $here = $now->add($this->toDateInterval());
        $there = $now->add($other->toDateInterval());

        return new self($here->diff($there, true));
    }

    public function compareTo($other): int
    {
        $other = $other instanceof self ? $other : self::make($other);

        $now = $this->now();
        $here = $now->add($this->toDateInterval());
        $there = $now->add($other->toDateInterval());

        return $here <=> $there;
    }

    public function toDateInterval(): DateInterval
    {
        return $this->value;
    }

    public function jsonSerialize()
    {
        return $this->toDateIntervalSpec();
    }

    public function __toString()
    {
        return $this->toDateIntervalSpec();
    }

    private function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('@'.time());
    }

    private function normalizeInterval(DateInterval $value): DateInterval
    {
        $now = $this->now();
        $then = $now->add($value);

        return $now->diff($then);
    }

    private function toDateIntervalSpec(): string
    {
        $current = $this->value;

        $spec = 'P';
        $spec .= 0 !== $current->y ? $current->y.'Y' : '';
        $spec .= 0 !== $current->m ? $current->m.'M' : '';
        $spec .= 0 !== $current->d ? $current->d.'D' : '';

        $spec .= 'T';
        $spec .= 0 !== $current->h ? $current->h.'H' : '';
        $spec .= 0 !== $current->i ? $current->i.'M' : '';
        $spec .= 0 !== $current->s ? $current->s.'S' : '';

        if ('T' === substr($spec, -1)) {
            $spec = substr($spec, 0, -1);
        }

        if ('P' === $spec) {
            return self::NONE;
        }

        return $spec;
    }
}
