<?php

declare(strict_types=1);

namespace App\Service;

final class RateCalculator
{
    public function calculate(string $code, float $mid): array
    {
        $upper = strtoupper($code);
        if (in_array($upper, ['EUR', 'USD'], true)) {
            $buy = round($mid - 0.15, 4);
            $sell = round($mid + 0.11, 4);
            return ['buy' => $buy, 'sell' => $sell];
        }
        $sell = round($mid + 0.20, 4);
        return ['buy' => null, 'sell' => $sell];
    }
}


