<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\CurrencyProvider;
use App\Service\NbpClient;
use App\Service\RateCalculator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class RateController extends AbstractController
{
    public function __construct(
        private readonly NbpClient $nbpClient,
        private readonly RateCalculator $rateCalculator,
    ) {}

    #[Route('/api/rates', name: 'api_rates_index', methods: ['GET'])]
    public function listRates(): JsonResponse
    {
        try {
            $table = $this->nbpClient->fetchLatestTableA();
            $effectiveDate = $table['effectiveDate'];
            
            $midRates = $this->createMidRatesMap($table['rates']);
            $result = $this->calculateRatesForSupportedCurrencies($midRates, $effectiveDate);

            $response = $this->json(['effectiveDate' => $effectiveDate, 'items' => $result]);
            
            $response->setMaxAge(3600); 
            $response->setSharedMaxAge(3600); 
            $response->headers->set('Vary', 'Accept-Encoding');
            
            return $response;
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to fetch current rates', 
                'details' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/rates/{code}/history', name: 'api_rates_history', requirements: ['code' => '[A-Za-z]{3}'], methods: ['GET'])]
    public function history(string $code, Request $request): JsonResponse
    {
        $code = strtoupper($code);
        
        if (!CurrencyProvider::isSupported($code)) {
            return $this->json(['error' => 'Unsupported currency'], 400);
        }
        
        $validationError = $this->validateHistoryRequest($request);
        if ($validationError) {
            return $this->json(['error' => $validationError], 400);
        }
        
        $date = $request->query->get('date');
        $days = $request->query->getInt('days', 14);
        $days = max(1, min(90, $days)); 

        try {
            $series = $this->nbpClient->fetchCurrencyHistory($code, $date, $days + 1);
            $data = $this->enrichHistoryWithBuySell($series, $code);

            $response = $this->json(['code' => $code, 'items' => $data]);
            
            $response->setMaxAge(1800); 
            $response->setSharedMaxAge(1800);
            $response->headers->set('Vary', 'Accept-Encoding');
            
            return $response;
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to fetch currency history', 
                'details' => $e->getMessage()
            ], 500);
        }
    }

    private function createMidRatesMap(array $rates): array
    {
        $midMap = [];
        foreach ($rates as $rate) {
            $midMap[strtoupper($rate['code'])] = (float) $rate['mid'];
        }
        return $midMap;
    }

    private function calculateRatesForSupportedCurrencies(array $midRates, string $effectiveDate): array
    {
        $result = [];
        foreach (CurrencyProvider::getSupportedCurrencies() as $code) {
            if (!isset($midRates[$code])) {
                continue;
            }
            
            $mid = $midRates[$code];
            $calc = $this->rateCalculator->calculate($code, $mid);
            
            $result[] = [
                'code' => $code,
                'date' => $effectiveDate,
                'mid' => $mid,
                'buy' => $calc['buy'],
                'sell' => $calc['sell'],
            ];
        }
        return $result;
    }

    private function validateHistoryRequest(Request $request): ?string
    {
        $date = $request->query->get('date');
        
        if ($date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return 'Invalid date format. Use YYYY-MM-DD';
        }
        
        if ($date && strtotime($date) > time()) {
            return 'Date cannot be in the future';
        }
        
        return null;
    }

    private function enrichHistoryWithBuySell(array $series, string $code): array
    {
        $data = [];
        foreach ($series as $row) {
            $calc = $this->rateCalculator->calculate($code, (float) $row['mid']);
            $data[] = [
                'date' => $row['effectiveDate'],
                'mid' => (float) $row['mid'],
                'buy' => $calc['buy'],
                'sell' => $calc['sell'],
            ];
        }
        return $data;
    }
}


