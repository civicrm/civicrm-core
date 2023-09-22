<?php

namespace Civi\OAuth;

use League\OAuth2\Client\Provider\GenericResourceOwner;
use League\OAuth2\Client\Token\AccessToken;

class TestOAuthDotComProvider extends CiviGenericProvider {

  protected function createResourceOwner(array $response, AccessToken $token): GenericResourceOwner {
    return new GenericResourceOwner($response, 'name');
  }

}
