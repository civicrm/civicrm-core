<?php
namespace Civi\OAuth;

use Civi;
use Civi\Api4\OAuthClient;
use Civi\Api4\OAuthProvider;
use Civi\Api4\PaymentProcessor;
use CRM_OAuth_ExtensionUtil as E;
use Civi\Core\Service\AutoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Automatically link an OAuth token with a PaymentProcessor.
 *
 * Usage:
 *
 * 1. PREPARATION: Check which `name` is declared in your PaymentProcessorType.
 * 2. REGISTRATION: Create the OAuthProvider definition (JSON/PHP). In additon to regular metadata, be sure to...
 *     - Set `tags` to include `PaymentProcessorType:{TYPE_NAME}`.
 *     - Set `templates` to include `PaymentProcessor' with a list of properties to initialize.
 *       Ex: ['user_name' => '{{token.raw.stripe_publishable_key}}', 'password' => '{{token.access_token}}']
 * 2. ADMINISTRATION: Create a relevant PaymentProcessor. Initiate OAuth process, set `tag=>PaymentProcessor:{ID}').
 *    Ex: OAuthClient::authorizationCode()->setTag('PaymentProcessor:123')->...execute()
 * 3. RUNTIME: Whenever this token is initialized or refreshed, this helper will
 *    update the `user_name` and `password` for `PaymentProcessor:123`.
 *
 * @service oauth_client.payment_processor_tag
 */
class OAuthPaymentProcessorTag extends AutoService implements EventSubscriberInterface {

  public static function getSubscribedEvents(): array {
    return [
      // When editing a PaymentProcessor that supports OAuth, offer the "Connect to" buttons.
      '&hook_civicrm_initiators::PaymentProcessor' => ['onPaymentInitiators', 0],

      // Allow users with `administer payment processors` to make OAuthSysTokens for payment-processors.
      '&hook_civicrm_oauthGrant' => ['hook_civicrm_oauthGrant', 0],

      // After completing the interactive authorization-grant, go back to PayProc page.
      '&hook_civicrm_oauthReturn' => ['hook_civicrm_oauthReturn', 0],

      // Whenever the OAuth access-token is initialized or refreshed, we should update the PaymentProcessor.
      '&hook_civicrm_oauthToken' => ['hook_civicrm_oauthToken', 0],
    ];
  }

  const PROVIDER_TAG_PREFIX = 'PaymentProcessorType:';

  const TOKEN_TAG_PREFIX = 'PaymentProcessor:';

  /**
   * In the "Edit Payment Processor" UI, which OAuth response-modes are expected to work?
   *
   * Note: 'query' (w/HTTP redirect) is the basic/common mode. 'web_message' (w/popup) is the preferred.
   */
  const SUPPORTED_RESPONSE_MODES = ['web_message', 'query'];

  /**
   * When editing a PaymentProcessor, generate a list of options for how to initialize the API keys.
   *
   * @param array $context
   * @param array $available
   * @param string|NULL $default
   * @see CRM_Utils_Hook::initiators()
   */
  public function onPaymentInitiators(array $context, array &$available, &$default): void {
    $payProcTag = static::PROVIDER_TAG_PREFIX . $context['payment_processor_type'];

    $providers = OAuthProvider::get()
      ->addWhere('tags', 'CONTAINS', $payProcTag)
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
      $name = 'stripe_' . $client['id'];
      $providerObj = \Civi::service('oauth2.league')->createProvider($client);

      $responseMode = $this->pickResponseMode($providerObj, static::SUPPORTED_RESPONSE_MODES);
      if (!$responseMode) {
        Civi::log()->warning('Provider ' . $client['provider'] . ' is declared as an OAuth service for PaymentProcessors, but it does not declare any compatible response-modes.');
        continue;
      }

      $available[$name] = [
        'title' => $provider['title'],
        'render' => function (\CRM_Core_Region $region, array $context, array $initiator) use ($provider, $client, $responseMode) {
          $label = E::ts('Connect to %1', [$provider['title']]);

          // See \Civi\Api4\OAuthClient::authorizationCode()
          $authCodeOptions = [
            'where' => [['id', '=', $client['id']]],
            'storage' => 'OAuthSysToken',
            'tag' => "PaymentProcessor:" . $context['payment_processor_id'],
            'startPage' => 'auto',
            'responseMode' => $responseMode,
            'ttl' => 9 * 60 * 60,
          ];

          $region->addScriptFile(E::LONG_NAME, 'js/oauth.initiator.js');
          $region->addMarkup(sprintf(
            '<div><a class="btn btn-xs btn-primary" href="#" onclick="CRM.oauth.authorizationCode(%s)">%s</a></div>',
            htmlentities(\CRM_Utils_JSON::encodeScriptVar($authCodeOptions)),
            htmlentities($label)
          ));
        },
      ];
    }
  }

  /**
   * @param \League\OAuth2\Client\Provider\AbstractProvider|\Civi\OAuth\CiviGenericProvider $providerObj
   * @param array $preferResponseModes
   *   List of response-modes supported by Payment Processor UI.
   *   Ex: ['web_message', 'query', 'fragment']
   * @return string|null
   */
  protected function pickResponseMode($providerObj, array $preferResponseModes) {
    $allowResponseModes = is_callable([$providerObj, 'getResponseModes']) ? $providerObj->getResponseModes() : ['query'];
    foreach ($preferResponseModes as $preferResponseMode) {
      if (in_array($preferResponseMode, $allowResponseModes)) {
        return $preferResponseMode;
      }
    }
    return NULL;
  }

  /**
   * If the user has `administer payment processors`, and if an OAuthProvider is tagged as `PaymentProcessor`,
   * then the user can create system-tokens for `PaymentProcessor:123`.
   *
   * @param \Civi\Api4\Action\OAuthClient\AbstractGrantAction $action
   * @param array $client
   * @param bool $allowed
   *
   * @see \CRM_OAuth_Hook::oauthGrant()
   */
  public function hook_civicrm_oauthGrant($action, array $client, bool &$allowed): void {
    if ($this->checkTokenTag($action->getTag() ?? '')
      && \CRM_Core_Permission::check('administer payment processors')
      && $action->getStorage() === 'OAuthSysToken'
      && !empty($this->checkProviderTags($this->getProvider($client['provider'])['tags'] ?? []))
    ) {
      $allowed = TRUE;
    }
  }

  public function hook_civicrm_oauthToken(string $flow, string $type, array &$token): void {
    $payProcId = $this->checkTokenTag($token['tag'] ?? '');
    if (empty($payProcId) || $type !== 'OAuthSysToken' || empty($token['client_id'])) {
      return;
    }

    // For refresh(), we might be implicitly refreshing as part of another op (by a diff user),
    // so `$checkPermissions=FALSE`. For 'init', it might be better to check some access rights...
    // but we know user has 'manage OAuth client', so they have proportionate access rights.
    $checkPermissions = FALSE;

    // It might be nice to add these as template variables. But for now, I don't think we need them
    // $client = Civi\Api4\OAuthClient::get($checkPermissions)
    //   ->addWhere('id', '=', $token['client_id'])
    //   ->execute()
    //   ->single();
    //
    // $provider = Civi\Api4\OAuthProvider::get($checkPermissions)
    //   ->addWhere('name', '=', $client['provider'])
    //   ->execute()
    //   ->single();

    /** @var \Civi\OAuth\OAuthTemplates $oauthTemplates */
    $oauthTemplates = Civi::service('oauth_client.templates');
    $template = $oauthTemplates->getByClientId($token['client_id'], 'PaymentProcessor');
    if (empty($template)) {
      Civi::log()->warning("OAuth token is tagged as %prefix but lacks a template for %name", [
        'prefix' => static::TOKEN_TAG_PREFIX,
        'name' => 'PaymentProcessor',
      ]);
      return;
    }
    // $vars = ['token' => $token, 'client' => $client, 'provider' => $provider];
    $vars = ['token' => $token];
    $values = $oauthTemplates->evaluate($template, $vars);

    PaymentProcessor::update($checkPermissions)
      ->addWhere('id', '=', $payProcId)
      ->setValues($values)
      ->execute();
  }

  /**
   * When the user returns with a token, we update the PaymentProcessor.
   *
   * @param $token
   * @param $nextUrl
   * @return void
   */
  public function hook_civicrm_oauthReturn($token, &$nextUrl): void {
    Civi::log()->info("Received token", ['token' => $token, 'nextUrl' => $nextUrl]);

    if ($payProcId = $this->checkTokenTag($token['tag'] ?? '')) {
      \CRM_Core_Session::setStatus(
        '',
        ts('Received API credentials'),
        'info'
      );

      $nextUrl = (string) Civi::url('backend://civicrm/admin/paymentProcessor/edit')->addQuery([
        'action' => 'update',
        'id' => $this->getCanonicalPaymentProcessorId($payProcId),
        'reset' => 1,
      ]);
    }
  }

  /**
   * Check if the list of provider-tags includes "PaymentProcessorType:{TYPE_NAME}".
   *
   * @param array $tags
   *   Ex: ['CiviConnect', 'PaymentProcessorType:StripeCheckout', 'PaymentProcessorType:Stripe']
   * @return array
   *   List of {TYPE_NAME}s. In other words, the list of Payment Processor Types supported by this OAuth provider.
   *   Ex: ['StripeCheckout', 'Stripe']
   */
  public function checkProviderTags(array $tags): array {
    $result = [];
    foreach ($tags as $tag) {
      if (str_starts_with($tag, static::PROVIDER_TAG_PREFIX)) {
        $tagParts = explode(':', $tag);
        if (preg_match(';^[a-zA-Z][a-zA-Z0-9_]*$;', $tagParts[1])) {
          $result[] = $tagParts[1];
        }
      }
    }
    return $result;
  }

  /**
   * Check if the token-tag specifies "PaymentProcessor:{ID}".
   *
   * @param string $tag
   *   Ex: "PaymentProcessor:123"
   * @return int|null
   *   Ex: 123
   * @throws \CRM_Core_Exception
   */
  public function checkTokenTag(string $tag): ?int {
    if (str_starts_with($tag, static::TOKEN_TAG_PREFIX)) {
      $tagParts = explode(':', $tag);
      return \CRM_Utils_Type::validate($tagParts[1], 'Positive');
    }
    else {
      return NULL;
    }
  }

  protected function getProvider(string $name): array {
    return Civi\Api4\OAuthProvider::get()
      ->addWhere('name', '=', $name)
      ->execute()
      ->single();
  }

  /**
   * @param int $id
   *   The civicrm_payment_processor.id of *some* record. It may be for *live* or *test*.
   * @return int
   *   The civicrm_payment_processor.id of a *live* record.
   */
  public function getCanonicalPaymentProcessorId(int $id): int {
    $pp = PaymentProcessor::get(FALSE)
      ->addWhere('id', '=', $id)
      ->addSelect('id', 'name', 'is_test')
      ->execute()
      ->single();
    if (!$pp['is_test']) {
      return $id;
    }

    $live = PaymentProcessor::get(FALSE)
      ->addWhere('name', '=', $pp['name'])
      ->addWhere('is_test', '=', FALSE)
      ->addSelect('id')
      ->execute()
      ->single();
    return $live['id'];
  }

}
