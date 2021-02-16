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

use Civi\Crypto\Exception\CryptoException;
use GuzzleHttp\Psr7\Response;

class Authenticator {

  /**
   * @var string
   *   Ex: 'param', 'xheader', 'header'
   */
  protected $flow;

  /**
   * @var string
   *  Ex: 'optional', 'require', 'ignore'
   */
  protected $userMode;

  /**
   * @var array
   *  Ex: ['jwt', 'pass', 'api_key']
   */
  protected $allowCreds;

  /**
   * Authenticator constructor.
   *
   * @param string $flow
   */
  public function __construct(string $flow) {
    $this->flow = $flow;
    $this->allowCreds = \Civi::settings()->get('authx_' . $flow . '_cred');
    $this->userMode = \Civi::settings()->get('authx_' . $flow . '_user');
  }

  /**
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @param string $cred
   *   The credential, as formatted in the 'Authorization' header.
   *   Ex: 'Bearer 12345'
   *   Ex: 'Basic ASDFFDSA=='
   * @param bool $useSession
   *   If TRUE, then the authentication should be persistent (in a session variable).
   *   If FALSE, then the authentication should be ephemeral (single page-request).
   * @return bool
   *   Returns TRUE on success.
   *   Exits with failure
   */
  public function auth($e, $cred, $useSession = FALSE) {
    $authxUf = _authx_uf();
    [$credType, $credValue] = explode(' ', $cred, 2);
    switch ($credType) {
      case 'Basic':
        if (!in_array('pass', $this->allowCreds)) {
          $this->reject('Password authentication is not supported');
        }
        [$user, $pass] = explode(':', base64_decode($credValue), 2);
        if ($userId = $authxUf->checkPassword($user, $pass)) {
          $contactId = \CRM_Core_BAO_UFMatch::getContactId($userId);
          $this->login($contactId, $userId, $useSession);
          return TRUE;
        }
        break;

      case 'Bearer':
        if ($contactId = $this->lookupContactToken($credValue)) {
          $userId = \CRM_Core_BAO_UFMatch::getUFId($contactId);
          $this->login($contactId, $userId, $useSession);
          return TRUE;
        }
        break;

      default:
        $this->reject();
    }

    $this->reject();
  }

  /**
   * Update Civi and UF to recognize the authenticated user.
   *
   * @param int $contactId
   *   The CiviCRM contact which is logging in.
   * @param int|string|null $userId
   *   The UF user which is logging in. May be NULL if there is no corresponding user.
   * @param bool $useSession
   *   Whether the login should be part of a persistent session.
   * @throws \Exception
   */
  protected function login($contactId, $userId, bool $useSession) {
    $authxUf = _authx_uf();

    if (\CRM_Core_Session::getLoggedInContactID() || $authxUf->getCurrentUserId()) {
      if (\CRM_Core_Session::getLoggedInContactID() === $contactId && $authxUf->getCurrentUserId() === $userId) {
        return;
      }
      else {
        // This is plausible if you have a dev or admin experimenting.
        // We should probably show a more useful page - e.g. ask if they want
        // logout and/or suggest using private browser window.
        $this->reject('Cannot login. Session already active.');
      }
    }

    if (empty($contactId)) {
      $this->reject("Cannot login. Failed to determine contact ID.");
    }

    switch ($this->userMode) {
      case 'ignore':
        $userId = NULL;
        break;

      case 'require':
        if (empty($userId)) {
          $this->reject('Cannot login. No matching user is available.');
        }
        break;
    }

    if (!$useSession) {
      \CRM_Core_Session::useFakeSession();
    }

    if ($userId && $useSession) {
      $authxUf->loginSession($userId);
    }
    if ($userId && !$useSession) {
      $authxUf->loginStateless($userId);
    }

    // Post-login Civi stuff...

    $session = \CRM_Core_Session::singleton();
    $session->set('ufID', $userId);
    $session->set('userID', $contactId);

    \CRM_Core_DAO::executeQuery('SET @civicrm_user_id = %1',
      [1 => [$contactId, 'Integer']]
    );
  }

  /**
   * Reject a bad authentication attempt.
   *
   * @param string $message
   */
  protected function reject($message = 'Authentication failed') {
    $r = new Response(401, ['Content-Type' => 'text/plain'], "HTTP 401 $message");
    \CRM_Utils_System::sendResponse($r);
  }

  /**
   * If given a bearer token, then lookup (and validate) the corresponding identity.
   *
   * @param string $credValue
   *   Bearer token
   *
   * @return int|null
   *   The authenticated contact ID.
   */
  protected function lookupContactToken($credValue) {
    if (in_array('api_key', $this->allowCreds)) {
      $c = \CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_contact WHERE api_key = %1', [
        1 => [$credValue, 'String'],
      ]);
      if ($c) {
        return $c;
      }
    }
    if (in_array('jwt', $this->allowCreds)) {
      try {
        $claims = \Civi::service('crypto.jwt')->decode($credValue);
        $scopes = isset($claims['scope']) ? explode(' ', $claims['scope']) : [];
        if (!in_array('authx', $scopes)) {
          $this->reject('JWT does not permit general authentication');
        }
        if (empty($claims['sub']) || substr($claims['sub'], 0, 4) !== 'cid:') {
          $this->reject('JWT does not specify the contact ID (sub)');
        }
        return substr($claims['sub'], 4);
      }
      catch (CryptoException $e) {
      }
    }
    return NULL;
  }

}
