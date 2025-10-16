<?php

namespace Civi\PayPalPpcp;

use Civi\OAuth\CiviGenericProvider;
use CRM_StripeConnect_ExtensionUtil as E;

/**
 */
class PayPalProvider extends CiviGenericProvider {

  /**
   * PayPal's "minibrowser" mode shows the OAuth flow in a popup, and returns the results via JS.
   * Conceptually, this is a response-mode, but it doesn't use the standard `?response_mode=xxx` parameter.
   * Instead, it uses `?displayMode=minibrowser`. For purposes of our OAuth integration, we'll
   * treat "?response_mode=paypal_minibrowser" as intending "?displayMode=minibrowser".
   */
  const MINIBROWSER = 'paypal_minibrowser';

  protected function getRequiredOptions() {
    return [
      // 'urlAuthorize',
      'urlAccessToken',
      // 'urlResourceOwnerDetails',
    ];
  }

  public function getAuthorizationUrl(array $options = []) {
    // PayPal's "?displayMode=minibrowser" serves a similar purpose as "response_mode".
    if (isset($options['response_mode']) && $options['response_mode'] = static::MINIBROWSER) {
      $options['displayMode'] = 'minibrowser';
      unset($options['response_mode']);
    }
    return parent::getAuthorizationUrl($options);
  }


}
