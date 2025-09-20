<?php

declare(strict_types=1);

namespace App\Service;

final class CurrencyProvider
{
    public const SUPPORTED_CURRENCIES = ['EUR', 'USD', 'CZK', 'IDR', 'BRL'];

    public static function isSupported(string $code): bool
    {
        return in_array(strtoupper($code), self::SUPPORTED_CURRENCIES, true);
    }

    public static function getSupportedCurrencies(): array
    {
        return self::SUPPORTED_CURRENCIES;
    }
}