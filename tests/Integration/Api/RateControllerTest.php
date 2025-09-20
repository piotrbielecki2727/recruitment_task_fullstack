<?php

declare(strict_types=1);

namespace App\Tests\Integration\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class RateControllerTest extends WebTestCase
{
    public function testRatesEndpoint(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/rates');
        
        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $this->assertJson($client->getResponse()->getContent());
        
        $data = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('effectiveDate', $data);
        $this->assertArrayHasKey('items', $data);
        $this->assertIsArray($data['items']);
        
        $this->assertGreaterThan(0, count($data['items']));
        
        if (!empty($data['items'])) {
            $item = $data['items'][0];
            $this->assertArrayHasKey('code', $item);
            $this->assertArrayHasKey('mid', $item);
            $this->assertArrayHasKey('buy', $item);
            $this->assertArrayHasKey('sell', $item);
        }
    }

    public function testHistoryEndpoint(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/rates/USD/history');
        
        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $this->assertJson($client->getResponse()->getContent());
        
        $data = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('code', $data);
        $this->assertArrayHasKey('items', $data);
        $this->assertEquals('USD', $data['code']);
        $this->assertIsArray($data['items']);
        
        $this->assertGreaterThan(0, count($data['items']));
        
        if (!empty($data['items'])) {
            $item = $data['items'][0];
            $this->assertArrayHasKey('date', $item);
            $this->assertArrayHasKey('mid', $item);
            $this->assertArrayHasKey('buy', $item);
            $this->assertArrayHasKey('sell', $item);
        }
    }

    public function testInvalidCurrency(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/rates/XXX/history');
        
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $client->getResponse()->getStatusCode());
        $this->assertJson($client->getResponse()->getContent());
        
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('Unsupported currency', $data['error']);
    }
}