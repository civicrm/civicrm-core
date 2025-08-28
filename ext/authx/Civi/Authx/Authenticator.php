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

use Civi\Core\Event\GenericHookEvent;
use Civi\Core\HookInterface;
use Civi\Core\Service\AutoService;
use GuzzleHttp\Psr7\Response;

/**
 * The Authenticator does the main work of authx -- ie it analyzes a credential,
 * checks if current policy accepts this credential, and logs in as the target person.
 *
 * @package Civi\Authx
 * @service authx.authenticator
 */
class Authenticator extends AutoService implements HookInterface {

  /**
   * When 'CRM_Core_Invoke' fires 'civi.invoke.auth', we should check for credentials.
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @return bool|void
   * @throws \Exception
   */
  public function on_civi_invoke_auth(GenericHookEvent $e) {
    $params = ($_SERVER['REQUEST_METHOD'] === 'GET') ? $_GET : $_POST;
    $siteKey = $_SERVER['HTTP_X_CIVI_KEY'] ?? $params['_authxSiteKey'] ?? NULL;

    if (!empty($_SERVER['HTTP_X_CIVI_AUTH'])) {
      return $this->auth($e, ['flow' => 'xheader', 'cred' => $_SERVER['HTTP_X_CIVI_AUTH'], 'siteKey' => $siteKey]);
    }

    if (!empty($_SERVER['HTTP_AUTHORIZATION']) && !empty(\Civi::settings()->get('authx_header_cred'))) {
      return $this->auth($e, ['flow' => 'header', 'cred' => $_SERVER['HTTP_AUTHORIZATION'], 'siteKey' => $siteKey]);
    }

    if (!empty($params['_authx'])) {
      if ((implode('/', $e->args) === 'civicrm/authx/login')) {
        $this->auth($e, ['flow' => 'login', 'cred' => $params['_authx'], 'useSession' => TRUE, 'siteKey' => $siteKey]);
        _authx_redact(['_authx']);
      }
      elseif (!empty($params['_authxSes'])) {
        $this->auth($e, ['flow' => 'auto', 'cred' => $params['_authx'], 'useSession' => TRUE, 'siteKey' => $siteKey]);
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
          _authx_reload(implode('/', $e->args), $_SERVER['QUERY_STRING']);
        }
        else {
          _authx_redact(['_authx', '_authxSes']);
        }
      }
      else {
        $this->auth($e, ['flow' => 'param', 'cred' => $params['_authx'], 'siteKey' => $siteKey]);
        _authx_redact(['_authx']);
      }
    }
  }

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
      'requestPath' => empty($e->args) ? '*' : implode('/', $e->args),
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
   * Determine whether credentials are valid. This is similar to `auth()`
   * but stops short of performing an actual login.
   *
   * @param array $details
   * @return array{flow: string, credType: string, jwt: ?array, useSession: bool, userId: ?int, contactId: ?int}
   *   Description of the validated principal (redacted).
   * @throws \Civi\Authx\AuthxException
   */
  public function validate(array $details): array {
    if (!isset($details['flow'])) {
      $this->reject('Authentication logic error: Must specify "flow".');
    }

    $tgt = AuthenticatorTarget::create([
      'flow' => $details['flow'],
      'cred' => $details['cred'] ?? NULL,
      'siteKey' => $details['siteKey'] ?? NULL,
      'useSession' => $details['useSession'] ?? FALSE,
      'requestPath' => $details['requestPath'] ?? '*',
    ]);

    if ($principal = $this->checkCredential($tgt)) {
      $tgt->setPrincipal($principal);
    }

    $this->checkPolicy($tgt);
    return $tgt->createRedacted();
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
    // In order of priority, each subscriber will either:
    // 1. Accept the cred, which stops event propagation and further checks;
    // 2. Reject the cred, which stops event propagation and further checks;
    // 3. Neither accept nor reject, letting the event continue on to the next.
    $checkEvent = new CheckCredentialEvent($tgt->cred, $tgt->requestPath);
    \Civi::dispatcher()->dispatch('civi.authx.checkCredential', $checkEvent);

    if ($checkEvent->getRejection()) {
      $this->reject($checkEvent->getRejection());
    }

    return $checkEvent->getPrincipal();
  }

  /**
   * Does our policy permit this login?
   *
   * @param \Civi\Authx\AuthenticatorTarget $tgt
   */
  protected function checkPolicy(AuthenticatorTarget $tgt) {
    $policy = [
      'userMode' => \Civi::settings()->get('authx_' . $tgt->flow . '_user') ?: 'optional',
      'allowCreds' => \Civi::settings()->get('authx_' . $tgt->flow . '_cred') ?: [],
      'guards' => \Civi::settings()->get('authx_guards'),
    ];

    $checkEvent = new CheckPolicyEvent($policy, $tgt);
    \Civi::dispatcher()->dispatch('civi.authx.checkPolicy', $checkEvent);
    $policy = $checkEvent->policy;
    if ($checkEvent->getRejection()) {
      $this->reject($checkEvent->getRejection());
    }

    // TODO: Consider splitting these checks into late-priority listeners.
    // What follows are a handful of distinct checks in no particular order.
    // In `checkCredential()`, similar steps were split out into distinct listeners (within `CheckCredential.php`).
    // For `checkPolicy()`, these could be moved to similar methods (within `CheckPolicy.php`).
    // They should probably be around priority -2000 (https://docs.civicrm.org/dev/en/latest/hooks/usage/symfony/#priorities).

    if (!$tgt->hasPrincipal()) {
      $this->reject('Invalid credential');
    }

    if ($tgt->contactId) {
      $findContact = \Civi\Api4\Contact::get(0)->addWhere('id', '=', $tgt->contactId);
      if ($findContact->execute()->count() === 0) {
        $this->reject(sprintf('Contact ID %d is invalid', $tgt->contactId));
      }
    }

    if ($tgt->credType !== 'assigned' && !in_array($tgt->credType, $policy['allowCreds'])) {
      $this->reject(sprintf('Authentication type "%s" with flow "%s" is not allowed for this principal.', $tgt->credType, $tgt->flow));
    }

    switch ($policy['userMode']) {
      case 'ignore':
        $tgt->userId = NULL;
        break;

      case 'require':
        if (empty($tgt->userId)) {
          $this->reject('Cannot login. No matching user is available.');
        }
        break;
    }

    $useGuards = $policy['guards'];
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
   * Check for a pre-existing login
   *
   * @param AuthenticatorTarget $tgt
   * @return bool
   */
  protected function checkAlreadyLoggedIn(AuthenticatorTarget $tgt) {
    $existingContact = \CRM_Core_Session::getLoggedInContactID();
    $existingUser = $this->authxUf->getCurrentUserId();

    if (!$existingContact && !$existingUser) {
      return FALSE;
    }

    if (
        $existingContact
        && $existingUser
        && ((string) $existingContact === (string) $tgt->contactId)
        && ((string) $existingUser === (string) $tgt->userId)
        ) {
      return TRUE;
    }

    // This is plausible if you have a dev or admin experimenting.
    // We should probably show a more useful page - e.g. ask if they want
    // logout and/or suggest using private browser window.
    $this->reject('Cannot login. A mismatched session is already active.');
    // @see \Civi\Authx\AllFlowsTest::testStatefulStatelessOverlap()
  }

  /**
   * Update Civi and UF to recognize the authenticated user.
   *
   * @param AuthenticatorTarget $tgt
   *   Summary of the authentication request
   * @throws \Exception
   */
  protected function login(AuthenticatorTarget $tgt) {
    if ($this->checkAlreadyLoggedIn($tgt)) {
      // Already logged in. Post-condition met - but by unusual means.
      \CRM_Core_Session::singleton()->set('authx', $tgt->createAlreadyLoggedIn());
      return;
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
    // Re-set the timezone incase the User System supports user records overriding the system timezone.
    \CRM_Core_Config::singleton()->userSystem->setTimeZone();
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
   * @var string|null
   *   Ex: 'civicrm/dashboard'
   */
  public $requestPath;

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
    $tgt = (new static())->set($args);
    if ($tgt->useSession || $tgt->requestPath === NULL) {
      // If requesting access to a session (or using anything that isn't specifically tied
      // to an HTTP route), then we are effectively asking for any/all routes.
      $tgt->requestPath = '*';
    }
    return $tgt;
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
      $args['userId'] ??= \CRM_Core_Config::singleton()->userSystem->getUfId($args['user']);
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
      'requestPath' => $this->requestPath,
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
