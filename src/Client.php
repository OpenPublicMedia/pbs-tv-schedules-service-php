<?php


namespace OpenPublicMedia\PbsTvSchedulesService;

use BadMethodCallException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use OpenPublicMedia\PbsTvSchedulesService\Exception\ApiKeyRequiredException;
use OpenPublicMedia\PbsTvSchedulesService\Exception\CallSignRequiredException;
use RuntimeException;

/**
 * PBS TV Schedules Service (TVSS) API Client.
 *
 * @url https://docs.pbs.org/display/tvsapi
 *
 * @package OpenPublicMedia\PbsTvSchedulesService
 */
class Client
{
    /**
     * Live base URL for the API.
     *
     * @url https://docs.pbs.org/display/tvsapi#TVSchedulesService(TVSS)API-ProductionAPIEndpoints
     */
    const LIVE = "https://services.pbs.org/tvss/";

    /**
     * Client for handling API requests
     *
     * @var GuzzleClient
     */
    protected $client;

    /**
     * Station call sign.
     *
     * @var string
     */
    public $callSign;

    /**
     * Client constructor.
     *
     * Not all endpoints require an API key or call sign so all parameters to
     * this method are optional.
     *
     * @param string $api_key
     *   API key provided by PBS.
     * @param string $call_sign
     *   Station call sign.
     * @param string $base_uri
     *   Base API URI.
     */
    public function __construct($api_key = '', $call_sign = '', $base_uri = self::LIVE)
    {
        $this->callSign = strtolower($call_sign);
        $options = ['base_uri' => $base_uri];
        if (!empty($api_key)) {
            $options['headers'] = ['X-PBSAUTH' => $api_key];
        }
        $this->client = new GuzzleClient($options);
    }

    /**
     * @param string $endpoint
     *   API endpoint to query.
     * @param bool $include_call_sign
     *   Whether or not to include the call sign in the request URI.
     *
     * @return object
     *   JSON decoded object with response data.
     */
    public function get($endpoint, $include_call_sign = true)
    {
        if ($include_call_sign) {
            $this->callSignRequired();
            $endpoint = $this->callSign . '/' . $endpoint;
        }
        try {
            $response = $this->client->request('get', $endpoint);
        } catch (GuzzleException $e) {
            if ($e->getCode() == 403) {
                throw new ApiKeyRequiredException();
            } else {
                // Implementors should handle all other exceptions.
                throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
            }
        }
        if ($response->getStatusCode() != 200) {
            throw new RuntimeException($response->getReasonPhrase(), $response->getCode(), $response);
        }
        $json = json_decode($response->getBody());
        return $json;
    }

    /**
     * @param $date
     *   Date filter in the format YYYYMMDD.
     * @param bool $kids_only
     *   Whether to return only kids listing results.
     *
     * @return array
     *  Listings grouped under contained Channel objects.
     */
    public function getListings($date, $kids_only = false)
    {
        $uri = 'day/' . $date;
        if ($kids_only) {
            $uri .= '/kids';
        }
        $response = $this->get($uri);
        return $response->feeds;
    }

    /**
     * This endpoint excludes the "description" property for each listing. That
     * property is returned by the getListings() method.
     *
     * @param bool $kids_only
     *   Whether to return only kids listing results.
     *
     * @return array
     *  Listings grouped under contained Channel objects.
     */
    public function getToday($kids_only = false)
    {
        $uri = 'today';
        if ($kids_only) {
            $uri .= '/kids';
        }
        $response = $this->get($uri);
        return $response->feeds;
    }

    /**
     * @param $show_id
     *   Show ID from API data.
     *
     * @return object
     *   Single object with metadata properties.
     */
    public function getShow($show_id)
    {
        $response = $this->get('upcoming/show/' . $show_id);
        return $response;
    }

    /**
     * @param $program_id
     *   Program ID from API data.
     *
     * @return object
     *   Single object with metadata properties.
     */
    public function getProgram($program_id)
    {
        $response = $this->get('upcoming/program/' . $program_id);
        return $response;
    }

    /**
     * Warning: this endpoint produces a large result and can be slow.
     *
     * @return array
     *   All program objects in the API.
     */
    public function getPrograms()
    {
        $response = $this->get('programs', false);
        return $response->programs;
    }

    /**
     * @param $term
     *   Text to search for (no partial matching).
     * @param bool $include_call_sign
     *   Whether to include the provided call sign with the request.
     * @param bool $kids_only
     *   Whether to limit the result to kids content.
     *
     * @return object
     *   Two properties, "program_results" and "show_results", are arrays
     *   containing results for the corresponding object types.
     */
    public function search($term, $include_call_sign = true, $kids_only = false)
    {
        if (!$include_call_sign && $kids_only) {
            throw new BadMethodCallException('Call sign must be included for kids only search.');
        }
        $uri = 'search';
        if ($kids_only) {
            $uri .= '-kids';
        }
        $uri .= '/' . $term;
        $response = $this->get($uri, $include_call_sign);
        return $response;
    }

    /**
     * @param $term
     *   Text to search for (no partial matching).
     * @param bool $include_call_sign
     *   Whether to include the provided call sign with the request.
     * @param bool $kids_only
     *   Whether to limit the result to kids content.
     *
     * @return array
     *   All Program objects matching the search term.
     *
     * @see Client::search()
     */
    public function searchPrograms($term, $include_call_sign = true, $kids_only = false)
    {
        $results = $this->search($term, $include_call_sign, $kids_only);
        return $results->program_results;
    }

    /**
     * @param $term
     *   Text to search for (no partial matching).
     * @param bool $include_call_sign
     *   Whether to include the provided call sign with the request.
     * @param bool $kids_only
     *   Whether to limit the result to kids content.
     *
     * @return array
     *   All Show objects matching the search term.
     *
     * @see Client::search()
     */
    public function searchShows($term, $include_call_sign = true, $kids_only = false)
    {
        $results = $this->search($term, $include_call_sign, $kids_only);
        return $results->show_results;
    }

    /**
     * @param null|mixed $zip
     *   Zip code to restrict channel search to. Note: this endpoint returns a
     *   404 for "no results" responses.
     *
     * @return array
     *   Channel data from the query response.
     *
     */
    public function getChannels($zip = null)
    {
        $uri = 'channels';
        if (!empty($zip)) {
            $uri = $uri . '/zip/' . $zip;
        }
        $response = $this->get($uri);
        return $response->headends;
    }

    /**
     * There is no actual endpoint for feeds. This method uses the "What's on
     * Today" endpoint to get feed data.
     *
     * @return array
     *   Feeds keyed by the Feed short name.
     */
    public function getFeeds()
    {
        $response = $this->get('today');
        $feeds = [];
        foreach ($response->feeds as $feed) {
            unset($feed->listings);
            $feeds[$feed->short_name] = $feed;
        }
        return $response->feeds;
    }

    /**
     * Verifies that a call sign property is set.
     */
    private function callSignRequired()
    {
        if (empty($this->callSign)) {
            throw new CallSignRequiredException();
        }
    }
}
