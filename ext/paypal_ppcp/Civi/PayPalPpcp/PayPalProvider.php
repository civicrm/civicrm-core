<?php

namespace Civi\PayPalPpcp;

use Civi\OAuth\CiviGenericProvider;
use Civi\OAuth\OAuthException;
use CRM_StripeConnect_ExtensionUtil as E;
use CRM_Utils_Request;
use GuzzleHttp\Client;
use League\OAuth2\Client\Grant\AbstractGrant;

/**
 */
class PayPalProvider extends CiviGenericProvider {

  /**
   * PayPal uses a flow that is -similar- to OAuth2's AuthorizationCode flow:
   *
   * - It differs most during initialization (where it replaces the ordinary HTTP redirect with their own Javascript popup,
   *   called the "minibrowser" - which returns the authCode via JS callback).
   * - The initial token-exchange uses `code` + `code_verifier` (similar), but it differs in using `SHARED-ID` (not `client_id`/`client_secret`).
   * - It looks like it's similar for refresh-tokens.
   *
   * We model this as a distinct flow (called `paypal_minibrowser`).
   *
   * @link https://developer.paypal.com/docs/multiparty/seller-onboarding/build-onboarding/
   */
  const MINIBROWSER = 'paypal_minibrowser';

  public function __construct(array $options = [], array $collaborators = []) {
    parent::__construct($options, $collaborators);

    $this->getGrantFactory()->setGrant(PayPalProvider::MINIBROWSER, new class extends AbstractGrant {

      protected function getName() {
        return PayPalProvider::MINIBROWSER;
      }

      protected function getRequiredRequestParameters() {
        return ['code', 'code_verifier'];
      }

    });

  }

  protected function getRequiredOptions() {
    return [
      // 'urlAuthorize',
      'urlCiviConnect',
      'urlAccessToken',
      // 'urlResourceOwnerDetails',
    ];
  }

  protected function getAccessTokenRequest(array $params) {
    switch ($params['grant_type'] ?? NULL) {
      case self::MINIBROWSER:
        // https://developer.paypal.com/docs/multiparty/seller-onboarding/build-onboarding/#get-seller-access-token
        return parent::getAccessTokenRequest([
          'code' => $params['code'],
          'code_verifier' => $params['code_verifier'],
          'grant_type' => 'authorization_code',
          // Exclude properties like client_id, client_secret, redirect_uri.
        ])->withHeader('Authorization', 'Basic ' . base64_encode($this->getSharedId() . ':'));

      default:
        return parent::getAccessTokenRequest($params);
    }
  }

  public function getSharedId(): ?string {
    // It's hard to find a way to propagate this from the "Return" controller to here...
    $sharedId = CRM_Utils_Request::retrieve('paypal_shared_id', 'String');
    if ($sharedId && !preg_match(';^[-_a-zA-Z0-9\+]+$;', $sharedId)) {
      throw new OAuthException("Malformed paypal_shared_id");
    }
    return $sharedId;
  }

  /**
   * Get referral URL from civicrm.org.
   *
   * @param string $sellerNonce
   * @return string
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @link https://developer.paypal.com/docs/multiparty/seller-onboarding/build-onboarding/#generate-signup-link
   */
  public function createReferralUrl(string $sellerNonce): string {
    $serviceUrl = $this->getCiviConnectUrl();
    $response = (new Client())->post("$serviceUrl/paypal/referral-url", [
      'form_params' => [
        'seller_nonce' => $sellerNonce,
      ],
    ]);
    $data = (string) $response->getBody();
    $parsed = json_decode($data, TRUE);
    foreach ($parsed['links'] as $link) {
      if ($link['rel'] == 'action_url') {
        // $this->registerNonce($sellerNonce); /* OK, this nonce can be used. */
        return $link['href'];
      }
    }
    throw new \CRM_Core_Exception("Filed to identify action_url in createReferralUrl()");
  }

}
