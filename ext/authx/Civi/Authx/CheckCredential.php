<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\Authx;

use Civi\Core\Service\AutoService;
use Civi\Crypto\Exception\CryptoException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This class is a small collection of common/default credential checkers.
 *
 * @service authx.credentials
 */
class CheckCredential extends AutoService implements EventSubscriberInterface {

  /**
   * Listener priority for handling credential format of 'Basic' with
   * 'username:password'.
   */
  const PRIORITY_BASIC_USER = -200;

  /**
   * Listener priority for handling credential format of 'Bearer' with a
   * traditional Civi API key
   */
  const PRIORITY_BEARER_API_KEY = -300;

  /**
   * Listener priority for handling credential format of 'Bearer' with
   * Authx-style JSON Web Token.
   */
  const PRIORITY_BEARER_JWT = -400;

  /**
   * @inheritdoc
   *
   * Set up three subscribers to handle different credential formats ('Basic',
   * 'Bearer') and different credential types ('pass', 'api_key', 'jwt')
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    $events['civi.authx.checkCredential'][] = ['basicUser', self::PRIORITY_BASIC_USER];
    $events['civi.authx.checkCredential'][] = ['bearerApiKey', self::PRIORITY_BEARER_API_KEY];
    $events['civi.authx.checkCredential'][] = ['bearerJwt', self::PRIORITY_BEARER_JWT];
    return $events;
  }

  /**
   * Interpret the HTTP "Basic" credential as `username:password` (CMS user).
   *
   * @param \Civi\Authx\CheckCredentialEvent $check
   */
  public function basicUser(CheckCredentialEvent $check): void {
    if ($check->credFormat === 'Basic') {
      [$user, $pass] = explode(':', base64_decode($check->credValue), 2);
      if ($userId = _authx_uf()->checkPassword($user, $pass)) {
        $check->accept(['userId' => $userId, 'credType' => 'pass']);
      }
    }
  }

  /**
   * Interpret the HTTP `Bearer` credential as a traditional Civi API key
   * (`civicrm_contact.api_key`).
   *
   * @param \Civi\Authx\CheckCredentialEvent $check
   */
  public function bearerApiKey(CheckCredentialEvent $check): void {
    if ($check->credFormat === 'Bearer') {
      $c = \CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_contact WHERE api_key = %1', [
        1 => [$check->credValue, 'String'],
      ]);
      if ($c) {
        $check->accept(['contactId' => $c, 'credType' => 'api_key']);
      }
    }
  }

  /**
   * Interpret the HTTP `Bearer` credential as an Authx-style JSON Web Token.
   *
   * @param \Civi\Authx\CheckCredentialEvent $check
   */
  public function bearerJwt(CheckCredentialEvent $check): void {
    if ($check->credFormat === 'Bearer') {
      try {
        $claims = \Civi::service('crypto.jwt')->decode($check->credValue);
        $scopes = isset($claims['scope']) ? explode(' ', $claims['scope']) : [];
        if (!in_array('authx', $scopes)) {
          // This is not an authx JWT. Proceed to check any other token sources.
          return;
        }
        if (empty($claims['sub']) || substr($claims['sub'], 0, 4) !== 'cid:') {
          $check->reject('Malformed JWT. Must specify the contact ID.');
        }
        else {
          $contactId = substr($claims['sub'], 4);
          $check->accept(['contactId' => $contactId, 'credType' => 'jwt', 'jwt' => $claims]);
        }
      }
      catch (CryptoException $e) {
        // TODO: Is responding that its expired a security risk?
        if (str_contains($e->getMessage(), 'Expired token')) {
          $check->reject('Expired token');
        }

        // Not a valid AuthX JWT. Proceed to check any other token sources.
      }
    }
  }

}
