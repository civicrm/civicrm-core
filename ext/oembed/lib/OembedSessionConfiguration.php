<?php

namespace Civi\Oembed;

use Drupal\Core\Session\SessionConfiguration;
use Symfony\Component\HttpFoundation\Request;

class OembedSessionConfiguration extends SessionConfiguration {

  protected function getUnprefixedName(Request $request) {
    return 'OE' . parent::getUnprefixedName($request);
  }

}
