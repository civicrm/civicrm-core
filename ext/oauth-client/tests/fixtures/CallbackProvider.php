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

  public static function register(): \Closure {
    $listener = static function ($event) {
      $event->providers['callback-race'] = [
        'name' => 'callback-race',
        'title' => 'Callback race',
        'class' => self::class,
        'pkceMethods' => ['S256'],
        'options' => [
          'discovery' => [
            'issuer' => 'https://id.example.org',
            'authorization_endpoint' => 'https://id.example.org/authorize',
            'token_endpoint' => 'https://id.example.org/token',
            'jwks_uri' => 'https://id.example.org/keys',
          ],
        ],
      ];
    };
    \Civi::dispatcher()->addListener('hook_civicrm_oauthProviders', $listener);
    \Civi::cache('long')->delete('OAuthProvider_list');
    return $listener;
  }

}
