<?php

namespace Civi\OAuth;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class DummyProvider extends CiviGenericProvider {

  public function __construct(array $options = [], array $collaborators = []) {
    parent::__construct($options, $collaborators);
    if ($paramsForCannedResponses = $options['cannedResponses'] ?? NULL) {
      $this->setHttpClient($this->createHttpClient($paramsForCannedResponses));
    }
  }

  private function createHttpClient($paramsForResponses): \GuzzleHttp\Client {
    $handler = new MockHandler();

    foreach ($paramsForResponses as $ps) {
      $handler->append(
        new Response($ps['status'], $ps['headers'], $ps['body'])
      );
    }

    $handlerStack = HandlerStack::create($handler);
    return new \GuzzleHttp\Client(['handler' => $handlerStack]);
  }

}
