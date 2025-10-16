<?php

namespace Civi\PayPalPpcp;

use Civi\Api4\OAuthClient;
use Civi\Api4\OAuthProvider;
use CRM_PayPalPpcp_ExtensionUtil as E;
use Civi\Core\Service\AutoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @service paypal_ppcp.registration
 */
class PayPalRegistration extends AutoService implements EventSubscriberInterface {

  const ONBOARD_FLOW_TTL = 6 * 60 * 60;

  const PROVIDER_NAME = 'ppcp';

  public static function getSubscribedEvents(): array {
    return [
      // Register "PayPal Complete" as an OAuthProvider. Enable auto-registration.
      '&hook_civicrm_oauthProviders' => [
        ['onAddProviders', 0],
        ['onFilterProviders', -1500],
      ],

      // When editing a "PPCP" PaymentProcessor, offer the "PayPal" options.
      '&hook_civicrm_initiators::PaymentProcessor' => ['onCreateInitiators', 0],
    ];
  }

  public function isSupported(): bool {
    return \Civi::container()->has('oauth_client.civi_connect');
  }

  /**
   * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_oauthProviders/
   */
  public function onAddProviders(&$providers) {
    if (!$this->isSupported()) {
      return;
    }

    $providers[self::PROVIDER_NAME] = [
      'name' => self::PROVIDER_NAME,
      'title' => ts('PayPal Complete'),
      'class' => PayPalProvider::class,
      'tags' => ['CiviConnect'],
      'options' => [
        'urlCiviConnect' => '{civi_connect_url}',
        'urlAccessToken' => 'https://api-m.sandbox.paypal.com/v1/oauth2/token',
        // NOTE: onFilterProviders will replace 'sandbox.paypal.com' with 'paypal.com', as needed.
        // 'urlResourceOwnerDetails' => '{civi_connect_url}/stripe-connect/resource',
        'scopes' => ['read_write'],
      ],
      'templates' => [
        'PaymentProcessor' => [
          // If an OAuth token is linked to a PPCP payment processor (`tag=PaymentProcessor:123`),
          // then some fields in the PaymentProcessor will be filled-in with this template.
          'user_name' => '',
          'password' => '{{token.raw.access_token}}',
          'url_site' => 'https://www.sandbox.paypal.com/',
          'url_api' => 'https://api-m.sandbox.paypal.com/',
          // NOTE: onFilterProviders will replace 'sandbox.paypal.com' with 'paypal.com', as needed.
        ],
      ],
    ];
  }

  public function onFilterProviders(&$providers) {
    foreach ($providers as $name => &$provider) {
      if ($name !== self::PROVIDER_NAME && !str_starts_with($name, self::PROVIDER_NAME . '_')) {
        continue;
      }
      // This filter runs after all variants (live/sandbox/local) have been added to $providers.
      // Some parts of the template will depend on which backend we're connecting through (e.g. `connect.civicrm.org` vs `sandbox.connect.civicrm.org`).
      if ($this->isRealPayment($provider)) {
        $filter = fn($s) => preg_replace(';^(https?://([\w\-]+.)?)sandbox.paypal.com(/.*)?$;', '$1paypal.com$3', $s);
        $provider['options'] = array_map($filter, $provider['options']);
        $provider['templates']['PaymentProcessor'] = array_map($filter, $provider['templates']['PaymentProcessor']);
      }
    }
  }

  /**
   * When editing a PaymentProcessor, generate a list of options for how to initialize the API keys.
   *
   * @param array $context
   * @param array $available
   * @param string|NULL $default
   * @see CRM_Utils_Hook::initiators()
   */
  public function onCreateInitiators(array $context, array &$available, &$default): void {
    if (!in_array($context['payment_processor_type'], ['PPCP']) || !$this->isSupported()) {
      return;
    }
    if (!\CRM_Core_Permission::check('administer payment processors')) {
      return;
    }

    $providers = OAuthProvider::get(FALSE)
      ->addWhere('class', '=', PayPalProvider::class)
      ->execute()
      ->indexBy('name')
      ->getArrayCopy();

    $clients = OAuthClient::get(FALSE)
      ->addWhere('provider', 'IN', array_keys($providers))
      ->addOrderBy('id')
      ->execute()
      ->getArrayCopy();

    foreach ($clients as $client) {
      $provider = $providers[$client['provider']];
      $name = 'ppcp_' . $client['id'];
      $available[$name] = [
        'title' => $provider['title'],
        'tags' => $provider['tags'],
        'render' => function (\CRM_Core_Region $region, array $context, array $initiator) use ($provider, $client) {
          $region->addScriptFile(E::LONG_NAME, 'js/paypal.initiator.js');

          // Ugh, this will be a synchronous part of the pageload. But PayPal requires that we obtain referral
          // URL before we can render a button... i.e. there is no JS API for opening the minibrowser...
          try {
            [$url, $stateId] = $this->createMinibrowserFlow($client, $context['payment_processor_id']);
          }
          catch (\Throwable $e) {
            \Civi::log()->warning("Failed to create referral URL.", ['exception' => $e]);
            $url = NULL;
            $stateId = NULL;
            $region->addMarkup(sprintf('<div class="alert alert-danger">%s</div>',
              ts('Failed to create referral URL.') . " " . ts('See log for details.')
            ));
          }

          // PayPal callback doesn't tell us -which- button was pressed (e.g. live vs test).
          // So we make separate callbacks (stubs) to ensure that each button triggers a different flow (`stateId`).
          $callback = 'paypal_' . preg_replace(';[^a-zA-Z1-9];', '', $provider['name']) . '_' . \CRM_Utils_String::createRandom(8, 'abcdefghijklmnopqrstuvwxyz');
          $region->addScript(sprintf('function %s(authCode, sharedId) { CRM.ppcp.onboard(CRM._.extend(%s, {code: authCode, paypal_shared_id: sharedId})); }',
            $callback,
            \CRM_Utils_JSON::encodeScriptVar(['state' => $stateId])
          ));

          $region->addMarkup(sprintf(
            '<div><a target="_blank" class="btn btn-xs btn-primary %s" data-paypal-onboard-complete="%s" href="%s" data-paypal-button="true">%s</a></div>',
            $url ? '' : 'disabled',
            htmlentities($callback),
            htmlentities($url ?: ''),
            htmlentities(ts('Connect to %1', [$provider['title']]))
          ));
          $scriptDomain = $this->isRealPayment($provider) ? 'https://www.paypal.com' : 'https://www.sandbox.paypal.com';
          $region->add([
            'name' => 'paypal-partner-js',
            'markup' => '<script id="paypal-js" src="' . $scriptDomain . '/webapps/merchantboarding/js/lib/lightbox/partner.js"></script>',
            'weight' => 1000,
          ]);
        },
        'oauth_client_id' => $client['id'],
      ];
    }
  }

  protected function isRealPayment(array $provider): bool {
    return preg_match(';^https://connect.civicrm.org(/|$);', $provider['options']['urlCiviConnect']);
  }

  /**
   * PayPal Minibrowser flow is similar-but-different to the OAuth AuthorizationCode flow:
   *
   * - Both present the user with a screen to login and approve permission.
   * - Both give an auth `code` - which can be exchanged for a bearer-token.
   * - For initiation, minibrowser doesn't use HTTP redirects or query-params -- it uses a custom JS API (with callbacks).
   * - The requests for exchanging tokens are shaped a bit differently.
   *
   * This function is analogous to OAuthClient::authorizationCode() -- it takes some inputs, creates the state, and
   * gives you back a URL.
   *
   * @param array $client
   * @param int $paymentProcessorId
   * @return array
   *   Tuple [0 => string $url, 1 => string $stateId]
   */
  protected function createMinibrowserFlow(array $client, int $paymentProcessorId): array {
    $providerObj = \Civi::service('oauth2.league')->createProvider($client);

    // The purpose of a nonce is to ensure that the party initiating the request is the same one that does the final token-retrieval.
    // To enforce this property, we won't directly reveal the nonce to browsers.
    $sellerNonce = \CRM_Utils_String::createRandom(64, \CRM_Utils_String::ALPHANUMERIC);

    // This is analogous to calling `OAuthClient::authorizationCode()` -- both setup a new `state` for a new pageflow.
    $stateId = \Civi::service('oauth2.state')->store([
      'ttl' => static::ONBOARD_FLOW_TTL,
      'clientId' => $client['id'],
      'grant_type' => PayPalProvider::MINIBROWSER,
      'storage' => 'OAuthSysToken',
      'scopes' => [],
      'tag' => 'PaymentProcessor:' . $paymentProcessorId,
      'code_verifier' => $sellerNonce,
    ]);

    $url = $providerObj->createReferralUrl($sellerNonce) . '&displayMode=minibrowser';

    return [$url, $stateId];
  }

}
