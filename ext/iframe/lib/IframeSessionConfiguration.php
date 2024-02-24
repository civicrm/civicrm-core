<?php

namespace Civi\Iframe;

use Drupal\Core\Session\SessionConfiguration;
use Symfony\Component\HttpFoundation\Request;

class IframeSessionConfiguration extends SessionConfiguration {

  protected function getUnprefixedName(Request $request) {
    return 'OE' . parent::getUnprefixedName($request);
  }

}
