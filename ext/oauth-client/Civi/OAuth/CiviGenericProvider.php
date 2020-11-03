<?php
namespace Civi\OAuth;

class CiviGenericProvider extends \League\OAuth2\Client\Provider\GenericProvider {

  protected function getAuthorizationParameters(array $options) {
    $newOptions = parent::getAuthorizationParameters($options);
    if (!isset($options['approval_prompt'])) {
      // GenericProvider insists on filling in "approval_prompt", but this seems
      // to be disfavored nowadays b/c OpenID Connect defines "prompt".
      unset($newOptions['approval_prompt']);
    }
    return $newOptions;
  }

}
