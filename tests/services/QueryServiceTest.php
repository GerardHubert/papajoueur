<?php

namespace App\Tests\Services;

use App\Services\QueryService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class QueryServiceTest extends WebTestCase
{
    public function getData(): array
    {
        $data['key'] = $_ENV['RAWG_API_KEY'];
        $data['query'] = 'diablo';
        $data['platform'] = 4;
        $data['apiId'] = 388309;
        return $data;
    }

    public function testIfSearchIsSuccessful(): void
    {
        // $client = static::createClient();
        // $crawler = $client->request('GET', '/');

        $query = QueryService::search(
            $this->getData()['key'],
            $this->getData()['query'],
            $this->getData()['platform']
        );

        $this->assertEmpty($query['failure'], 'Api search failure');
    }

    public function testIfGetDetailsIsSuccessful(): void
    {
        $query = QueryService::findById(
            $this->getData()['apiId'],
            $this->getData()['key']
        );

        $this->assertEmpty($query['failure']);
    }

    public function testIfApiSearchResponseHasNoError(): void
    {
        $query = QueryService::search(
            $this->getData()['key'],
            $this->getData()['query'],
            $this->getData()['platform']
        );

        $data = json_decode($query['success'], true);
        $this->assertArrayNotHasKey('error', $data);
    }

    public function testIfApiDetailsResponseHasNoError(): void
    {
        $query = QueryService::findById(
            $this->getData()['apiId'],
            $this->getData()['key']
        );

        $data = json_decode($query['game'], true);
        $this->assertArrayNotHasKey('error', $data, 'array has error');
    }
}
