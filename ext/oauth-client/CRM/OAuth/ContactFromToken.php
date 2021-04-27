<?php

class CRM_OAuth_ContactFromToken {

  /**
   * Given a token, we add a new record to civicrm_contact based on the
   * provider's template
   */
  public static function createContact(array $token): array {
    $client = \Civi\Api4\OAuthClient::get(FALSE)
      ->addWhere('id', '=', $token['client_id'])
      ->execute()
      ->single();
    $provider = \Civi\Api4\OAuthProvider::get(FALSE)
      ->addWhere('name', '=', $client['provider'])
      ->execute()
      ->single();

    $vars = ['token' => $token, 'client' => $client, 'provider' => $provider];
    $template = ['checkPermissions' => FALSE] + $provider['contactTemplate'];
    $contact = civicrm_api4(
      'Contact',
      'create',
      self::evalArrayTemplate($template, $vars)
    )->single();
    return $contact;
  }

  /**
   * @param array $template
   *   Array of key-value expressions. Arrays can be nested.
   *   Ex: ['name' => '{{person.first}} {{person.last}}']
   *
   *   Expressions begin with a variable name; a string followed by a dot
   *   denotes an array key. Ex: {{person.first}} means
   *   $vars['person']['first'].
   *
   *   Optionally, the value may be piped through other 'filter' functions.
   *   Ex: {{person.first|lowercase}}
   *
   * @param array $vars
   *   Array tree of data to interpolate.
   *
   * @return array
   *   The template array, with '{{...}}' expressions evaluated.
   */
  public static function evalArrayTemplate(array $template, array $vars): array {
    $filterFunctions = [
      'lowercase' => function ($s) {
        return strtolower($s);
      },
    ];

    $evaluateLeafNode = function (&$node) use ($filterFunctions, $vars) {
      if (!(preg_match(';{{([a-zA-Z0-9_\.\|]+)}};', $node, $matches))) {
        return $node;
      }

      $parts = explode('|', $matches[1]);
      $value = (string) CRM_Utils_Array::pathGet($vars, explode('.', $parts[0]));
      $filterSteps = array_slice($parts, 1);

      foreach ($filterSteps as $f) {
        if (isset($filterFunctions[$f])) {
          $value = $filterFunctions[$f]($value);
        }
        else {
          $value = NULL;
        }
      }

      $node = $value;
    };

    $result = $template;
    array_walk_recursive($result, $evaluateLeafNode);
    return $result;
  }

}
