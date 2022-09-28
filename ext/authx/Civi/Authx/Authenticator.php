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

/**
 * The Authenticator does the main work of authx -- ie it analyzes a credential,
 * checks if current policy accepts this credential, and logs in as the target person.
 *
 * @package Civi\Authx
 */
class Authenticator {

  /**
   * @var \Civi\Authx\AuthxInterface
   */
  protected $authxUf;

  /**
   * @var string
   *   Ex: 'send' or 'exception
   */
  protected $rejectMode = 'send';

  /**
   * Authenticator constructor.
   */
  public function __construct() {
    $this->authxUf = _authx_uf();
  }

  /**
   * Run the entire authentication routine, checking credentials, checking policy,
   * and ultimately logging in.
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   *   Details for the 'civi.invoke.auth' event.
   * @param array{flow: string, useSession: ?bool, cred: ?string, principal: ?array} $details
   *   Describe the authentication process with these properties:
   *
   *   - string $flow (required);
   *     The type of authentication flow being used
   *     Ex: 'param', 'header', 'auto'
   *   - bool $useSession (default FALSE)
   *     If TRUE, then the authentication should be persistent (in a session variable).
   *     If FALSE, then the authentication should be ephemeral (single page-request).
   *
   *   And then ONE of these properties to describe the user/principal:
   *
   *   - string $cred
   *     The credential, as formatted in the 'Authorization' header.
   *     Ex: 'Bearer 12345', 'Basic ASDFFDSA=='
   *   - array $principal
   *     Description of a validated principal.
   *     Must include 'contactId', 'userId', xor 'user'
   * @return bool
   *   Returns TRUE on success.
   *   Exits with failure
   * @throws \Exception
   */
  public function auth($e, $details) {
    if (!(isset($details['cred']) xor isset($details['principal']))) {
      $this->reject('Authentication logic error: Must specify "cred" xor "principal".');
    }
    if (!isset($details['flow'])) {
      $this->reject('Authentication logic error: Must specify "flow".');
    }

    $tgt = AuthenticatorTarget::create([
      'flow' => $details['flow'],
      'cred' => $details['cred'] ?? NULL,
      'siteKey' => $details['siteKey'] ?? NULL,
      'useSession' => $details['useSession'] ?? FALSE,
    ]);

    if (isset($tgt->cred)) {
      if ($principal = $this->checkCredential($tgt)) {
        $tgt->setPrincipal($principal);
      }
    }
    elseif (isset($details['principal'])) {
      $details['principal']['credType'] = 'assigned';
      $tgt->setPrincipal($details['principal']);
    }

    $this->checkPolicy($tgt);
    $this->login($tgt);
    return TRUE;
  }

  /**
   * Assess the credential ($tgt->cred) and determine the matching principal.
   *
   * @param \Civi\Authx\AuthenticatorTarget $tgt
   * @return array|NULL
   *   Array describing the authenticated principal represented by this credential.
   *   Ex: ['userId' => 123]
   *   Format should match setPrincipal().
   * @see \Civi\Authx\AuthenticatorTarget::setPrincipal()
   */
  protected function checkCredential($tgt) {
    [$credFmt, $credValue] = explode(' ', $tgt->cred, 2);

    switch ($credFmt) {
      case 'Basic':
        [$user, $pass] = explode(':', base64_decode($credValue), 2);
        if ($userId = $this->authxUf->checkPassword($user, $pass)) {
          return ['userId' => $userId, 'credType' => 'pass'];
        }
        break;

      case 'Bearer':
        $c = \CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_contact WHERE api_key = %1', [
          1 => [$credValue, 'String'],
        ]);
        if ($c) {
          return ['contactId' => $c, 'credType' => 'api_key'];
        }

        try {
          $claims = \Civi::service('crypto.jwt')->decode($credValue);
          $scopes = isset($claims['scope']) ? explode(' ', $claims['scope']) : [];
          if (!in_array('authx', $scopes)) {
            $this->reject('JWT does not permit general authentication');
          }
          if (empty($claims['sub']) || substr($claims['sub'], 0, 4) !== 'cid:') {
            $this->reject('JWT does not specify the contact ID (sub)');
          }
          $contactId = substr($claims['sub'], 4);
          return ['contactId' => $contactId, 'credType' => 'jwt', 'jwt' => $claims];
        }
        catch (CryptoException $e) {
          // Invalid JWT. Proceed to check any other token sources.
        }

        break;
    }

    return NULL;
  }

  /**
   * Does our policy permit this login?
   *
   * @param \Civi\Authx\AuthenticatorTarget $tgt
   */
  protected function checkPolicy(AuthenticatorTarget $tgt) {
    if (!$tgt->hasPrincipal()) {
      $this->reject('Invalid credential');
    }

    if ($tgt->contactId) {
      $findContact = \Civi\Api4\Contact::get(0)->addWhere('id', '=', $tgt->contactId);
      if ($findContact->execute()->count() === 0) {
        $this->reject(sprintf('Contact ID %d is invalid', $tgt->contactId));
      }
    }

    $allowCreds = \Civi::settings()->get('authx_' . $tgt->flow . '_cred') ?: [];
    if ($tgt->credType !== 'assigned' && !in_array($tgt->credType, $allowCreds)) {
      $this->reject(sprintf('Authentication type "%s" with flow "%s" is not allowed for this principal.', $tgt->credType, $tgt->flow));
    }

    $userMode = \Civi::settings()->get('authx_' . $tgt->flow . '_user') ?: 'optional';
    switch ($userMode) {
      case 'ignore':
        $tgt->userId = NULL;
        break;

      case 'require':
        if (empty($tgt->userId)) {
          $this->reject('Cannot login. No matching user is available.');
        }
        break;
    }

    $useGuards = \Civi::settings()->get('authx_guards');
    if (!empty($useGuards)) {
      // array(string $credType => string $requiredPermissionToUseThisCred)
      $perms['pass'] = 'authenticate with password';
      $perms['api_key'] = 'authenticate with api key';

      // If any one of these passes, then we allow the authentication.
      $passGuard = [];
      $passGuard[] = in_array('site_key', $useGuards) && defined('CIVICRM_SITE_KEY') && hash_equals(CIVICRM_SITE_KEY, (string) $tgt->siteKey);
      $passGuard[] = in_array('perm', $useGuards) && isset($perms[$tgt->credType]) && \CRM_Core_Permission::check($perms[$tgt->credType], $tgt->contactId);
      // JWTs are signed by us. We don't need user to prove that they're allowed to use them.
      $passGuard[] = ($tgt->credType === 'jwt');
      $passGuard[] = ($tgt->credType === 'assigned');
      if (!max($passGuard)) {
        $this->reject(sprintf('Login not permitted. Must satisfy guard (%s).', implode(', ', $useGuards)));
      }
    }
  }

  /**
   * Update Civi and UF to recognize the authenticated user.
   *
   * @param AuthenticatorTarget $tgt
   *   Summary of the authentication request
   * @throws \Exception
   */
  protected function login(AuthenticatorTarget $tgt) {
    $isSameValue = function($a, $b) {
      return !empty($a) && (string) $a === (string) $b;
    };

    if (\CRM_Core_Session::getLoggedInContactID() || $this->authxUf->getCurrentUserId()) {
      if ($isSameValue(\CRM_Core_Session::getLoggedInContactID(), $tgt->contactId)  && $isSameValue($this->authxUf->getCurrentUserId(), $tgt->userId)) {
        // Already logged in. Post-condition met - but by unusual means.
        \CRM_Core_Session::singleton()->set('authx', $tgt->createAlreadyLoggedIn());
        return;
      }
      else {
        // This is plausible if you have a dev or admin experimenting.
        // We should probably show a more useful page - e.g. ask if they want
        // logout and/or suggest using private browser window.
        $this->reject('Cannot login. Session already active.');
        // @see \Civi\Authx\AllFlowsTest::testStatefulStatelessOverlap()
      }
    }

    if (empty($tgt->contactId)) {
      // It shouldn't be possible to get here due policy checks. But just in case.
      $this->reject("Cannot login. Failed to determine contact ID.");
    }

    if (!($tgt->useSession)) {
      \CRM_Core_Session::useFakeSession();
    }

    if ($tgt->userId && $tgt->useSession) {
      $this->authxUf->loginSession($tgt->userId);
    }
    if ($tgt->userId && !($tgt->useSession)) {
      $this->authxUf->loginStateless($tgt->userId);
    }

    // Post-login Civi stuff...

    $session = \CRM_Core_Session::singleton();
    $session->set('authx', $tgt->createRedacted());
    $session->set('ufID', $tgt->userId);
    $session->set('userID', $tgt->contactId);

    \CRM_Core_DAO::executeQuery('SET @civicrm_user_id = %1',
      [1 => [$tgt->contactId, 'Integer']]
    );
  }

  /**
   * Specify the rejection mode.
   *
   * @param string $mode
   * @return $this
   */
  public function setRejectMode(string $mode) {
    $this->rejectMode = $mode;
    return $this;
  }

  /**
   * Reject a bad authentication attempt.
   *
   * @param string $message
   */
  protected function reject($message = 'Authentication failed') {
    if ($this->rejectMode === 'exception') {
      throw new AuthxException($message);
    }

    \CRM_Core_Session::useFakeSession();
    $r = new Response(401, ['Content-Type' => 'text/plain'], "HTTP 401 $message");
    \CRM_Utils_System::sendResponse($r);
  }

}

class AuthenticatorTarget {

  /**
   * The authentication-flow by which we received the credential.
   *
   * @var string
   *   Ex: 'param', 'header', 'xheader', 'auto', 'script'
   */
  public $flow;

  /**
   * @var bool
   */
  public $useSession;

  /**
   * The raw credential as submitted.
   *
   * @var string
   *   Ex: 'Basic AbCd123=' or 'Bearer xYz.321'
   */
  public $cred;

  /**
   * The raw site-key as submitted (if applicable).
   * @var string
   */
  public $siteKey;

  /**
   * (Authenticated) The type of credential.
   *
   * @var string
   *   Ex: 'pass', 'api_key', 'jwt'
   */
  public $credType;

  /**
   * (Authenticated) UF user ID
   *
   * @var int|string|null
   */
  public $userId;

  /**
   * (Authenticated) CiviCRM contact ID
   *
   * @var int|null
   */
  public $contactId;

  /**
   * (Authenticated) JWT claims (if applicable).
   *
   * @var array|null
   */
  public $jwt = NULL;

  /**
   * @param array $args
   * @return $this
   */
  public static function create($args = []) {
    return (new static())->set($args);
  }

  /**
   * @param array $args
   * @return $this
   */
  public function set($args) {
    foreach ($args as $k => $v) {
      $this->{$k} = $v;
    }
    return $this;
  }

  /**
   * Specify the authenticated principal for this request.
   *
   * @param array $args
   *   Mix of: 'user', 'userId', 'contactId', 'credType'
   *   It is valid to give 'userId' or 'contactId' - the missing one will be
   *   filled in via UFMatch (if available).
   * @return $this
   */
  public function setPrincipal($args) {
    if (!empty($args['user'])) {
      $args['userId'] = $args['userId'] ?? \CRM_Core_Config::singleton()->userSystem->getUfId($args['user']);
      if ($args['userId']) {
        unset($args['user']);
      }
      else {
        throw new AuthxException("Must specify principal with valid user, userId, or contactId");
      }
    }
    if (empty($args['userId']) && empty($args['contactId'])) {
      throw new AuthxException("Must specify principal with valid user, userId, or contactId");
    }
    if (empty($args['credType'])) {
      throw new AuthxException("Must specify the type of credential used to identify the principal");
    }
    if ($this->hasPrincipal()) {
      throw new AuthxException("Principal has already been specified");
    }

    if (empty($args['contactId']) && !empty($args['userId'])) {
      $args['contactId'] = \CRM_Core_BAO_UFMatch::getContactId($args['userId']);
    }
    if (empty($args['userId']) && !empty($args['contactId'])) {
      $args['userId'] = \CRM_Core_BAO_UFMatch::getUFId($args['contactId']);
    }

    return $this->set($args);
  }

  /**
   * @return bool
   */
  public function hasPrincipal(): bool {
    return !empty($this->userId) || !empty($this->contactId);
  }

  /**
   * Create a variant of the authentication record which omits any secret values. It may be
   * useful to examining metadata and outcomes.
   *
   * The redacted version may be retained in the (real or fake) session and consulted by more
   * fine-grained access-controls.
   *
   * @return array{flow: string, credType: string, jwt: ?array, useSession: bool, userId: ?int, contactId: ?int}
   */
  public function createRedacted(): array {
    return [
      // omit: cred
      // omit: siteKey
      'flow' => $this->flow,
      'credType' => $this->credType,
      'jwt' => $this->jwt,
      'useSession' => $this->useSession,
      'userId' => $this->userId,
      'contactId' => $this->contactId,
    ];
  }

  /**
   * Describe the (OK-ish) authentication outcome wherein the same user was
   * already authenticated.
   *
   * Ex: cv ev --user=demo "return authx_login(['principal' => ['user' => 'demo']], false);"
   *
   * In this example, `cv ev --user=demo` does an initial login, and then `authx_login()` tries
   * to login a second time. This is sort of an error for `authx_login()` (_since it doesn't
   * really do auth_); but it's sort of OK (because the post-conditions are met). It's sort of
   * a code-smell (because flows with multiple login-calls are ill-advised - and may raise
   * exceptions with different data).
   *
   * @return array{flow: string, credType: string, jwt: ?array, useSession: bool, userId: ?int, contactId: ?int}
   */
  public function createAlreadyLoggedIn(): array {
    \Civi::log()->warning('Principal was already authenticated. Ignoring request to re-authenticate.', [
      'userId' => $this->userId,
      'contactId' => $this->contactId,
      'requestedFlow' => $this->flow,
      'requestedCredType' => $this->credType,
    ]);
    return [
      'flow' => 'already-logged-in',
      'credType' => 'already-logged-in',
      'jwt' => NULL,
      'useSession' => !\CRM_Utils_Constant::value('_CIVICRM_FAKE_SESSION'),
      'userId' => $this->userId,
      'contactId' => $this->contactId,
    ];
  }

}
