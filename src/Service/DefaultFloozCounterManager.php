<?php

declare(strict_types=1);

namespace Geekabel\MobileMoneyPayment\Service;

use Geekabel\MobileMoneyPayment\Exception\PaymentException;
use Geekabel\MobileMoneyPayment\Interface\FloozCounterManagerInterface;



class DefaultFloozCounterManager implements FloozCounterManagerInterface
{
    private string $counterFile;

    public function __construct(string $counterFile = null)
    {
        $this->counterFile = $counterFile ?? sys_get_temp_dir() . '/flooz_counter.txt';
    }

    public function getAndIncrementCounter(): int
    {
        $fp = fopen($this->counterFile, 'c+');
        if (!$fp) {
            throw new PaymentException("Unable to open counter file");
        }

        try {
            flock($fp, LOCK_EX);
            $counter = (int) fread($fp, 1024);
            $counter = max(1050, $counter + 1); // Ensure the counter starts at least at 1050
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, (string) $counter);
            fflush($fp);
            flock($fp, LOCK_UN);
        } finally {
            fclose($fp);
        }

        return $counter - 1; // Return the value before increment
    }
}