<?php

namespace Civi\OAuth;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;

class CallbackProvider extends CiviOpenIdProvider {

  public static array $responses = [];

  public static array $requests = [];

  public function __construct(array $options = [], array $collaborators = []) {
    $handler = HandlerStack::create(new MockHandler(self::$responses));
    $handler->push(Middleware::history(self::$requests));
    $collaborators['httpClient'] = new Client(['handler' => $handler]);
    parent::__construct($options, $collaborators);
  }

}
