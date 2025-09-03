<?php

namespace Civi\OAuth;

use Civi\Api4\OAuthClient;
use Civi\Api4\OAuthProvider;
use CRM_OAuth_ExtensionUtil as E;
use Civi\Core\Service\AutoService;

/**
 * Automatically link an OAuth token with a PaymentProcessor.
 *
 * Usage:
 *
 * 1. In the OAuthProvider JSON, specify the `templates['PaymentProcessor']`.
 *    This is a list of `PaymentProcessor` properties to initialize.
 *    Ex: ['user_name' => '{{token.raw.stripe_publishable_key}}', 'password' => '{{token.access_token}}']
 * 2. Initiate OAuth process and set the relevant payment-processor.
 *    Ex: OAuthClient::authorizationCode()->setTag('PaymentProcessor:123')->...execute()
 * 3. Whenever this token is initialized or refreshed, this helper will
 *    update the `user_name` and `password` for `PaymentProcessor:123`.
 *
 * @service oauth_client.templates
 */
class OAuthTemplates extends AutoService {

  /**
   * Determine which (if any) template is available for a given client.
   *
   * TODO: Support contact and mail-settings. Migrate 'contactTemplate' and 'mailSettingsTemplate' to generic 'templates'.
   *
   * @param int $clientId
   *   Local ID of the OAuthClient
   * @param string $template
   *   Symbolic name of the template.
   * @return array|null
   */
  public function getByClientId(int $clientId, string $template): ?array {
    $client = OAuthClient::get(FALSE)
      ->addWhere('id', '=', $clientId)
      ->addSelect('provider')
      ->execute()->first();
    if (!$client) {
      return NULL;
    }

    $provider = OAuthProvider::get(FALSE)
      ->addWhere('name', '=', $client['provider'])
      ->addSelect('templates')
      ->execute()->first();
    if (!$provider) {
      return NULL;
    }

    return $provider['templates'][$template] ?? NULL;
  }

  /**
   * Evaluate an array and interpolating bits of data.
   *
   * @param array $template
   *   List of key-value expressions.
   *   Ex: ['name' => '{{person.first}} {{person.last}}']
   *   Expressions begin with the dotted-name of a variable.
   *   Optionally, the value may be piped through other functions
   * @param array $vars
   *   Array tree of data to interpolate.
   * @return array
   *   The template array, with '{{...}}' expressions evaluated.
   */
  public function evaluate(array $template, array $vars): array {
    $filters = [
      'getMailDomain' => function($v) {
        $parts = explode('@', $v);
        return $parts[1] ?? NULL;
      },
      'getMailUser' => function($v) {
        $parts = explode('@', $v);
        return $parts[0] ?? NULL;
      },
    ];

    $lookupVars = function($m) use ($vars, $filters) {
      $parts = explode('|', $m[1]);
      $value = (string) \CRM_Utils_Array::pathGet($vars, explode('.', array_shift($parts)));
      foreach ($parts as $part) {
        if (isset($filters[$part])) {
          $value = $filters[$part]($value);
        }
        else {
          $value = NULL;
        }
      }
      return $value;
    };

    $values = [];
    foreach ($template as $key => $value) {
      $values[$key] = is_string($value)
        ? preg_replace_callback(';{{([a-zA-Z0-9_\.\|]+)}};', $lookupVars, $value)
        : $value;
    }
    return $values;
  }

}
