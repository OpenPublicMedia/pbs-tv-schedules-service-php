<?php
declare(strict_types=1);


namespace OpenPublicMedia\PbsTvSchedulesService\Test;

use BadMethodCallException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use OpenPublicMedia\PbsTvSchedulesService\Client;
use OpenPublicMedia\PbsTvSchedulesService\Exception\ApiKeyRequiredException;
use OpenPublicMedia\PbsTvSchedulesService\Exception\CallSignRequiredException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * Class ClientTest
 *
 * @coversDefaultClass \OpenPublicMedia\PbsTvSchedulesService\Client
 *
 * @package OpenPublicMedia\PbsTvSchedulesService\Test
 */
class ClientTest extends TestCase
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var MockHandler
     */
    protected $mockHandler;

    /**
     * Create client with mock handler.
     */
    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();
        $this->client = new Client(
            'api_key',
            'call_sign',
            Client::LIVE,
            ['handler' => $this->mockHandler]
        );
    }

    /**
     * @param string $name
     *   Base file name for a JSON fixture file.
     *
     * @return ResponseInterface
     *   Guzzle 200 response with JSON body content.
     */
    private function jsonResponse(string $name): ResponseInterface
    {
        return new Response(
            200,
            ['Content-Type' => 'application/json'],
            file_get_contents(__DIR__ . '/fixtures/' . $name . '.json')
        );
    }

    /**
     * @covers ::get
     */
    public function testApiKeyRequired(): void
    {
        $this->mockHandler->append(new Response(204));
        $clientNoCallSign = new Client(
            'api_key',
            null,
            Client::LIVE,
            ['handler' => $this->mockHandler]
        );
        $this->expectException(CallSignRequiredException::class);
        $clientNoCallSign->get('test');
    }

    /**
     * @covers ::get
     */
    public function testCallSignRequired(): void
    {
        $this->mockHandler->append(new RequestException(
            'API key required.',
            new Request('GET', 'test'),
            new Response(403)
        ));
        $clientNoKey = new Client(
            null,
            'call_sign',
            Client::LIVE,
            ['handler' => $this->mockHandler]
        );
        $this->expectException(ApiKeyRequiredException::class);
        $clientNoKey->get('test');
    }

    /**
     * @covers ::get
     */
    public function testGuzzleException(): void
    {
        $this->mockHandler->append(new RequestException(
            'Bad request.',
            new Request('GET', 'test'),
            new Response(400)
        ));
        $this->expectException(RuntimeException::class);
        $this->client->get('test');
    }

    /**
     * @covers ::get
     */
    public function testApiUnexpectedResponse(): void
    {
        $this->mockHandler->append(new Response(204));
        $this->expectException(RuntimeException::class);
        $this->client->get('test');
    }

    /**
     * @covers ::getListings
     */
    public function testGetListings(): void
    {
        $this->mockHandler->append($this->jsonResponse('getListings-20190704'));
        $listings = $this->client->getListings('20190704');
        $this->assertIsArray($listings);
        $this->assertCount(6, $listings);
        $this->assertIsObject($listings[1]);
        $this->assertObjectHasAttribute('cid', $listings[1]);
        $this->assertObjectHasAttribute('listings', $listings[1]);
        $this->assertCount(2, $listings[1]->listings);
        $this->assertObjectHasAttribute('description', $listings[1]->listings[0]);
    }

    /**
     * @covers ::getListings
     */
    public function testGetListingsKidsOnly(): void
    {
        $this->mockHandler->append($this->jsonResponse('getListings-20190704-kids_only'));
        $listings = $this->client->getListings('20190704', true);
        $this->assertIsArray($listings);
        $this->assertCount(6, $listings);
        $this->assertIsObject($listings[1]);
        $this->assertObjectHasAttribute('cid', $listings[1]);
        $this->assertObjectHasAttribute('listings', $listings[1]);
        $this->assertCount(1, $listings[1]->listings);
    }

    /**
     * @covers ::getToday
     */
    public function testGetToday(): void
    {
        $this->mockHandler->append($this->jsonResponse('getToday'));
        $listings = $this->client->getToday();
        $this->assertIsArray($listings);
        $this->assertCount(6, $listings);
        $this->assertIsObject($listings[1]);
        $this->assertObjectHasAttribute('cid', $listings[1]);
        $this->assertObjectHasAttribute('listings', $listings[1]);
        $this->assertCount(2, $listings[1]->listings);
        $this->assertObjectNotHasAttribute('description', $listings[1]->listings[0]);
    }

    /**
     * @covers ::getToday
     */
    public function testGetTodayKidsOnly(): void
    {
        $this->mockHandler->append($this->jsonResponse('getToday-kids_only'));
        $listings = $this->client->getToday(true);
        $this->assertIsArray($listings);
        $this->assertCount(6, $listings);
        $this->assertIsObject($listings[1]);
        $this->assertObjectHasAttribute('cid', $listings[1]);
        $this->assertObjectHasAttribute('listings', $listings[1]);
        $this->assertCount(1, $listings[1]->listings);
    }

    /**
     * @covers ::getShow
     */
    public function testGetShow(): void
    {
        $id = 'episode_57384';
        $this->mockHandler->append($this->jsonResponse('getShow-' . $id));
        $show = $this->client->getShow($id);
        $this->assertIsObject($show);
        $this->assertObjectHasAttribute('cid', $show);
        $this->assertObjectHasAttribute('show_id', $show);
        $this->assertEquals($id, $show->show_id);
        $this->assertObjectHasAttribute('upcoming_shows', $show);
        $this->assertIsArray($show->upcoming_shows);
    }

    /**
     * @covers ::getProgram
     */
    public function testGetProgram(): void
    {
        $id = '7877';
        $this->mockHandler->append($this->jsonResponse('getProgram-' . $id));
        $program = $this->client->getProgram($id);
        $this->assertIsObject($program);
        $this->assertObjectHasAttribute('cid', $program);
        $this->assertObjectHasAttribute('program_id', $program);
        $this->assertEquals($id, $program->program_id);
        $this->assertObjectHasAttribute('upcoming_episodes', $program);
        $this->assertIsArray($program->upcoming_episodes);
    }

    /**
     * @covers ::getPrograms
     */
    public function testGetPrograms(): void
    {
        $this->mockHandler->append($this->jsonResponse('getPrograms'));
        $programs = $this->client->getPrograms();
        $this->assertIsArray($programs);
        $this->assertIsObject($programs[0]);
        $this->assertObjectHasAttribute('cid', $programs[0]);
    }

    /**
     * @covers ::search
     */
    public function testSearch(): void
    {
        $term = 'jamestown';
        $this->mockHandler->append($this->jsonResponse('search-' . $term));
        $results = $this->client->search($term);
        $this->assertIsObject($results);
        $this->assertObjectHasAttribute('program_results', $results);
        $this->assertIsArray($results->program_results);
        $this->assertCount(2, $results->program_results);
        $this->assertObjectHasAttribute('show_results', $results);
        $this->assertIsArray($results->show_results);
        $this->assertCount(5, $results->show_results);

        $this->expectException(BadMethodCallException::class);
        $this->client->search($term, false, true);
    }

    /**
     * @covers ::search
     */
    public function testSearchNoCallSign(): void
    {
        $term = 'jamestown';
        $this->mockHandler->append($this->jsonResponse('search-' . $term . '-no_call_sign'));
        $results = $this->client->search($term, false);
        $this->assertIsObject($results);
        $this->assertObjectHasAttribute('program_results', $results);
        $this->assertIsArray($results->program_results);
        $this->assertCount(5, $results->program_results);
        $this->assertObjectHasAttribute('show_results', $results);
        $this->assertIsArray($results->show_results);
        $this->assertCount(7, $results->show_results);
    }

    /**
     * @covers ::search
     */
    public function testSearchKidsOnly(): void
    {
        $term = 'pinkalicious';
        $this->mockHandler->append($this->jsonResponse('search-' . $term . '-kids_only'));
        $results = $this->client->search($term, true, true);
        $this->assertIsObject($results);
        $this->assertObjectHasAttribute('program_results', $results);
        $this->assertIsArray($results->program_results);
        $this->assertCount(1, $results->program_results);
        $this->assertObjectHasAttribute('show_results', $results);
        $this->assertIsArray($results->show_results);
        $this->assertCount(2, $results->show_results);
    }

    /**
     * @covers ::searchPrograms
     */
    public function testSearchPrograms(): void
    {
        $term = 'jamestown';
        $this->mockHandler->append($this->jsonResponse('search-' . $term));
        $results = $this->client->searchPrograms($term);
        $this->assertIsArray($results);
        $this->assertCount(2, $results);
    }

    /**
     * @covers ::searchShows
     */
    public function testSearchShows(): void
    {
        $term = 'jamestown';
        $this->mockHandler->append($this->jsonResponse('search-' . $term));
        $results = $this->client->searchShows($term);
        $this->assertIsArray($results);
        $this->assertCount(5, $results);
    }

    /**
     * @covers ::getChannels
     */
    public function testGetChannels(): void
    {
        $this->mockHandler->append($this->jsonResponse('getChannels'));
        $channels = $this->client->getChannels();
        $this->assertIsArray($channels);
        $this->assertCount(2, $channels);
        $this->assertIsObject($channels[0]);
        $this->assertObjectHasAttribute('cid', $channels[0]);
        $this->assertObjectHasAttribute('name', $channels[0]);
        $this->assertEquals('CenturyLink Prism - Seattle', $channels[0]->name);
    }

    /**
     * @covers ::getChannels
     */
    public function testGetChannelsByZipCode(): void
    {
        $this->mockHandler->append($this->jsonResponse('getChannels-98030'));
        $channels = $this->client->getChannels('98030');
        $this->assertIsArray($channels);
        $this->assertCount(2, $channels);
        $this->assertIsObject($channels[0]);
        $this->assertObjectHasAttribute('cid', $channels[0]);
        $this->assertObjectHasAttribute('name', $channels[0]);
        $this->assertEquals('DISH Seattle', $channels[0]->name);
    }

    /**
     * @covers ::getFeeds
     */
    public function testGetFeeds(): void
    {
        $this->mockHandler->append($this->jsonResponse('getToday'));
        $feeds = $this->client->getFeeds();
        $this->assertIsArray($feeds);
        $this->assertCount(6, $feeds);
        $this->assertIsObject($feeds[0]);
        $this->assertObjectHasAttribute('cid', $feeds[0]);
        $this->assertObjectHasAttribute('short_name', $feeds[0]);
        $this->assertEquals('KCTSDT4', $feeds[0]->short_name);
    }
}
