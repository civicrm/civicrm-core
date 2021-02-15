<?php

namespace Civi\Test;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Middleware;
use GuzzleHttp\Client;

/**
 * Class GuzzleTestTrait
 *
 * This trait defines a number of helper functions for testing Guzzle-based logic,
 * such as a payment-processing gateway.
 *
 * Use this in a headless test for which you need to mock outbound HTTP requests from Civi.
 * Alternatively, to write an E2E test with inbound HTTP requests to Civi, see HttpTestTrait.
 */
trait GuzzleTestTrait {
  /**
   * @var \GuzzleHttp\Client
   */
  protected $guzzleClient;

  /**
   * Array containing guzzle history of requests and responses.
   *
   * @var array
   */
  protected $container;

  /**
   * Mockhandler to simulate guzzle requests.
   *
   * @var \GuzzleHttp\Handler\MockHandler
   */
  protected $mockHandler;

  /**
   * The url to mock-interact with.
   *
   * @var string
   */
  protected $baseUri;

  /**
   * @return \GuzzleHttp\Client
   */
  public function getGuzzleClient() {
    return $this->guzzleClient;
  }

  /**
   * @param \GuzzleHttp\Client $guzzleClient
   */
  public function setGuzzleClient($guzzleClient) {
    $this->guzzleClient = $guzzleClient;
  }

  /**
   * @return array
   */
  public function getContainer() {
    return $this->container;
  }

  /**
   * @param array $container
   */
  public function setContainer($container) {
    $this->container = $container;
  }

  /**
   * @return mixed
   */
  public function getBaseUri() {
    return $this->baseUri;
  }

  /**
   * @param mixed $baseUri
   */
  public function setBaseUri($baseUri) {
    $this->baseUri = $baseUri;
  }

  /**
   * @return \GuzzleHttp\Handler\MockHandler
   */
  public function getMockHandler() {
    return $this->mockHandler;
  }

  /**
   * @param \GuzzleHttp\Handler\MockHandler $mockHandler
   */
  public function setMockHandler($mockHandler) {
    $this->mockHandler = $mockHandler;
  }

  /**
   * @param $responses
   */
  protected function createMockHandler($responses) {
    $mocks = [];
    foreach ($responses as $response) {
      $mocks[] = new Response(200, [], $response);
    }
    $this->setMockHandler(new MockHandler($mocks));
  }

  /**
   * @param $files
   */
  protected function createMockHandlerForFiles($files) {
    $body = [];
    foreach ($files as $file) {
      $body[] = trim(file_get_contents(__DIR__ . $file));
    }
    $this->createMockHandler($body);
  }

  /**
   * Set up a guzzle client with a history container.
   *
   * After you have run the requests you can inspect $this->container
   * for the outgoing requests and incoming responses.
   *
   * If $this->mock is defined then no outgoing http calls will be made
   * and the responses configured on the handler will be returned instead
   * of replies from a remote provider.
   */
  protected function setUpClientWithHistoryContainer() {
    $this->container = [];
    $history = Middleware::history($this->container);
    $handler = HandlerStack::create($this->getMockHandler());
    $handler->push($history);
    $this->guzzleClient = new Client(['base_uri' => $this->baseUri, 'handler' => $handler]);
  }

  /**
   * Get the bodies of the requests sent via Guzzle.
   *
   * @return array
   */
  protected function getRequestBodies() {
    $requests = [];
    foreach ($this->getContainer() as $guzzle) {
      $requests[] = (string) $guzzle['request']->getBody();
    }
    return $requests;
  }

  /**
   * Get the bodies of the requests sent via Guzzle.
   *
   * @return array
   */
  protected function getRequestHeaders() {
    $requests = [];
    foreach ($this->getContainer() as $guzzle) {
      $requests[] = $guzzle['request']->getHeaders();
    }
    return $requests;
  }

  /**
   * Get the bodies of the requests sent via Guzzle.
   *
   * @return array
   */
  protected function getRequestUrls() {
    $requests = [];
    foreach ($this->getContainer() as $guzzle) {
      $requests[] = (string) $guzzle['request']->getUri();
    }
    return $requests;
  }

  /**
   * Get the bodies of the responses returned via Guzzle.
   *
   * @return array
   */
  protected function getResponseBodies() {
    $responses = [];
    foreach ($this->getContainer() as $guzzle) {
      $responses[] = (string) $guzzle['response']->getBody();
    }
    return $responses;
  }

}
