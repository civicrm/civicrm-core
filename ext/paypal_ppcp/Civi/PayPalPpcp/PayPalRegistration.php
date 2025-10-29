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


  public static function getSubscribedEvents(): array {
    return [
      // Add special initiator JS
      '&hook_civicrm_alterBundle::initiators' => ['onAddInitiatorsJs', 0],

      // Register "PayPal Complete" as an OAuthProvider. Enable auto-registration.
      '&hook_civicrm_oauthProviders' => ['onAddProviders', 0],

      // When editing a "PPCP" PaymentProcessor, offer the "PayPal" options.
      '&hook_civicrm_initiators::PaymentProcessor' => ['onCreateInitiators', 0],
    ];
  }

  public function isSupported(): bool {
    return \Civi::container()->has('oauth_client.civi_connect');
  }

  public function onAddInitiatorsJs(\CRM_Core_Resources_Bundle $bundle): void {
    $bundle->addScriptFile(E::LONG_NAME, 'js/paypal.initiator.js');
  }

  /**
   * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_oauthProviders/
   */
  public function onAddProviders(&$providers) {
    if (!$this->isSupported()) {
      return;
    }

    $providers['ppcp'] = [
      'name' => 'ppcp',
      'title' => ts('PayPal Complete'),
      'class' => PayPalProvider::class,
      'tags' => ['CiviConnect'],
      'options' => [
        'urlCiviConnect' => '{civi_connect_url}',
        'urlAuthorize' => 'javascript:',
        // 'urlAuthorize' => '{civi_connect_url}/paypal-ppcp/authorize',
        'urlAccessToken' => '{civi_connect_url}/paypal-ppcp/token',
        // 'urlResourceOwnerDetails' => '{civi_connect_url}/stripe-connect/resource',
        'scopes' => ['read_write'],
        'responseModes' => [PayPalProvider::MINIBROWSER],
      ],
      'templates' => [
        'PaymentProcessor' => [
          // If an OAuth token is linked to a PPCP payment processor (`tag=PaymentProcessor:123`),
          // then some fields in the PaymentProcessor will be filled-in with this template.
          'user_name' => 'fixme',
          'password' => 'fixme',
        ],
      ],
    ];
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

    $providers = OAuthProvider::get()
      ->addWhere('class', '=', PayPalProvider::class)
      ->execute()
      ->indexBy('name')
      ->getArrayCopy();

    $clients = OAuthClient::get()
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
        'callback' => function($context, $initiator, \CRM_Core_Resources_CollectionAdderInterface $resources) use ($client) {
          $data = OAuthClient::authorizationCode()
            ->addWhere('id', '=', $client['id'])
            ->setStorage('OAuthSysToken')
            ->setTag('PaymentProcessor:' . $context['payment_processor_id'])
            ->setStartPage('never')
            ->setResponseMode(PayPalProvider::MINIBROWSER)
            ->setTtl(3 * 60 * 60)
            ->execute()
            ->single();

          $resources->addScriptFile(E::LONG_NAME, 'js/paypal.initiator.js');
          $resources->addScript(sprintf("CRM.$(function(){ CRM.initiatePayPalMinibrowser(%s);});", \CRM_Utils_JSON::encodeScriptVar($data)), ['weight' => 1000]);
        },
        'oauth_client_id' => $client['id'],
      ];
    }
  }

}
