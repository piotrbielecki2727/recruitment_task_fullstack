<?php

declare(strict_types=1);

namespace App\Service;

use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class NbpClient
{
    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly CacheInterface $cache
    ) {}

    public function fetchLatestTableA(): array
    {
        $cacheKey = 'nbp_table_a_' . date('Y-m-d');
        
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($cacheKey) {
            $this->logger->info('Cache MISS - fetching from NBP API', [
                'cacheKey' => $cacheKey,
                'reason' => 'Data not in cache or expired'
            ]);
            
            $item->expiresAfter($this->getSecondsUntilNextNbpUpdate());
            
            try {
                $url = 'https://api.nbp.pl/api/exchangerates/tables/A/?format=json';
                $response = $this->httpClient->request('GET', $url, [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Accept-Encoding' => 'gzip, deflate',
                        'Connection' => 'keep-alive',
                    ],
                    'http_errors' => false,
                    'timeout' => 10,
                    'connect_timeout' => 5,
                ]);

                $status = $response->getStatusCode();
                if ($status !== 200) {
                    $this->logger->error('NBP API error', [
                        'status' => $status, 
                        'endpoint' => $url,
                        'response' => substr((string) $response->getBody(), 0, 500)
                    ]);
                    throw new \RuntimeException("NBP API unavailable (HTTP $status)");
                }

                $body = (string) $response->getBody();
                if (empty($body)) {
                    throw new \RuntimeException('NBP API returned empty response');
                }

                try {
                    $json = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    $this->logger->error('NBP API returned invalid JSON', [
                        'endpoint' => $url,
                        'body_preview' => substr($body, 0, 200),
                        'error' => $e->getMessage()
                    ]);
                    throw new \RuntimeException('NBP API returned invalid JSON: ' . $e->getMessage());
                }

                if (!isset($json[0]['effectiveDate'], $json[0]['rates'])) {
                    $this->logger->error('NBP API returned unexpected structure', [
                        'endpoint' => $url,
                        'structure' => array_keys($json[0] ?? [])
                    ]);
                    throw new \RuntimeException('Unexpected NBP response structure');
                }

                if (!is_array($json[0]['rates']) || empty($json[0]['rates'])) {
                    throw new \RuntimeException('NBP API returned no currency rates');
                }

                $result = [
                    'effectiveDate' => $json[0]['effectiveDate'],
                    'rates' => $json[0]['rates'],
                ];
                
                $this->logger->info('NBP rates fetched and cached successfully', [
                    'effectiveDate' => $result['effectiveDate'],
                    'ratesCount' => count($result['rates']),
                    'cacheKey' => $cacheKey
                ]);
                
                return $result;
                
            } catch (\Exception $e) {
                $this->logger->error('Failed to fetch NBP rates', [
                    'error' => $e->getMessage(),
                    'endpoint' => $url ?? 'unknown',
                    'cacheKey' => $cacheKey
                ]);
                
                if ($e instanceof \RuntimeException) {
                    throw $e;
                }
                
                throw new \RuntimeException('Failed to fetch NBP rates: ' . $e->getMessage(), 0, $e);
            }
        });
    }

    public function fetchCurrencyHistory(string $code, ?string $endDate, int $lastDays): array
    {
        $code = strtoupper($code);
        $end = $endDate ?: date('Y-m-d');
        $cacheKey = sprintf('nbp_hist_%s_%s_%d_%s', $code, $end, $lastDays, date('Y-m-d'));
        
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($code, $end, $lastDays, $cacheKey) {
            $item->expiresAfter($this->getSecondsUntilNextNbpUpdate());
            
            $windowDays = max($lastDays * 3, $lastDays + 10);
            $startTs = strtotime($end . ' -' . ($windowDays - 1) . ' days');
            $start = date('Y-m-d', $startTs);
            $url = sprintf('https://api.nbp.pl/api/exchangerates/rates/A/%s/%s/%s?format=json', $code, $start, $end);

            $response = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'http_errors' => false,
                'timeout' => 10,
            ]);

            $status = $response->getStatusCode();
            if ($status !== 200) {
                $this->logger->error('NBP API error', ['status' => $status, 'endpoint' => $url]);
                throw new \RuntimeException('NBP API unavailable');
            }

            $body = (string) $response->getBody();
            $json = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            if (!isset($json['rates'])) {
                throw new \RuntimeException('Unexpected NBP response');
            }

            $result = $json['rates'];
            if (count($result) > $lastDays) {
                $result = array_slice($result, -$lastDays);
            }
            
            $this->logger->info('NBP currency history fetched and cached', [
                'code' => $code, 
                'endDate' => $end, 
                'days' => $lastDays,
                'resultCount' => count($result)
            ]);
            
            return $result;
        });
    }

    private function getSecondsUntilNextNbpUpdate(): int
    {
        $now = new \DateTime();
        $currentHour = (int) $now->format('H');
        $dayOfWeek = (int) $now->format('N');
        
        if ($currentHour < 12 && $dayOfWeek <= 5) {
            $nextUpdate = clone $now;
            $nextUpdate->setTime(12, 0, 0);
            return $nextUpdate->getTimestamp() - $now->getTimestamp();
        }
        
        $nextUpdate = clone $now;
        
        do {
            $nextUpdate->modify('+1 day');
            $dayOfWeek = (int) $nextUpdate->format('N');
        } while ($dayOfWeek > 5);
        
        $nextUpdate->setTime(12, 0, 0);
        
        return $nextUpdate->getTimestamp() - $now->getTimestamp();
    }
}