<?php

/**
 * Class to re-convert curly braces that have been encoded as %7B and %7D
 * back curly braces when they look like CiviCRM tokens.
 *
 * See also:
 * https://lab.civicrm.org/dev/core/-/issues/5676
 */
class CRM_Utils_HTMLPurifier_URIFilter extends HTMLPurifier_URIFilter {

  public $name = 'CiviToken';

  public function prepare($config): bool {
    return TRUE;
  }

  public function filter(&$uri, $config, $context): bool {
    if ($uri->query) {
      // Replace %7B with { and %7D with } if they look like CiviCRM tokens.
      // Looking for {entity.string}
      $uri->query = preg_replace_callback('/%7B([A-Za-z0-9_]*\.[A-Za-z0-9_.]*?)%7D/', function ($matches) {
        return '{' . $matches[1] . '}';
      }, $uri->query);
    }
    return TRUE;

  }

}
