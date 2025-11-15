<?php
namespace Civi\OAuth;

use Civi;
use Civi\Api4\PaymentProcessor;
use CRM_OAuth_ExtensionUtil as E;
use Civi\Core\Service\AutoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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
 * @service oauth_client.payment_processor_tag
 */
class OAuthPaymentProcessorTag extends AutoService implements EventSubscriberInterface {

  public static function getSubscribedEvents(): array {
    return [
      // After completing the interactive authorization-grant, go back to PayProc page.
      '&hook_civicrm_oauthReturn' => ['hook_civicrm_oauthReturn', 0],

      // Whenever the OAuth access-token is initialized or refreshed, we should update the PaymentProcessor.
      '&hook_civicrm_oauthToken' => ['hook_civicrm_oauthToken', 0],
    ];
  }

  const TAG_PREFIX = 'PaymentProcessor:';

  public function hook_civicrm_oauthToken(string $flow, string $type, array &$token): void {
    $payProcId = $this->checkTag($token['tag'] ?? '');
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
        'prefix' => static::TAG_PREFIX,
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

    if ($payProcId = $this->checkTag($token['tag'] ?? '')) {
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

  public function checkTag(string $tag): ?int {
    if (str_starts_with($tag, static::TAG_PREFIX)) {
      $tagParts = explode(':', $tag);
      return \CRM_Utils_Type::validate($tagParts[1], 'Positive');
    }
    else {
      return NULL;
    }
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
