<?php

declare(strict_types=1);

namespace Geekabel\MobileMoneyPayment\Interface;

interface FloozCounterManagerInterface
{
    /**
     * Get the current counter value and increment it for the next use.
     *
     * @return int The current counter value
     */
    public function getAndIncrementCounter(): int;
}