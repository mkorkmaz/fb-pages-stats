<?php
declare(strict_types = 1);

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Psr7\Request;

class GuzzleClient
{
    private $client;

    private static $validHttpMethods = ['get', 'post', 'put', 'patch', 'options', 'head'];

    public function __construct(GuzzleHttpClient $client)
    {
        $this->client = $client;
    }

    public function request(string $method, string $uri, array $headers = [], array $body = null)
    {
        $guzzleHeaders = [
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.12; rv:50.0) Gecko/20100101 Firefox/50.0',
            'allow_redirects' => false
        ];
        $request    = new Request($method, $uri, array_merge($headers, $guzzleHeaders), $body, '1.1');
        $response   = $this->client->send($request);
        $exception  = null;
        $response_code = $response->getStatusCode();
        return array( 'status_code' => $response_code, 'response' => $response->getBody(), 'exception' => $exception );
    }

    public function __call(string $method, array $args)
    {
        if (!in_array($method, self::$validHttpMethods, true)) {
            throw new InvalidArgumentException('%s is not a valid HTTP Methhod', $method);
        }
        $args += [0=>null,1=>[],2=>null];
        list($uri, $headers, $body) = $args;
        return $this->request($method, $uri, $headers, $body);
    }
}
