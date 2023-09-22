<?php
use CRM_OAuth_ExtensionUtil as E;

class CRM_OAuth_Page_Return extends CRM_Core_Page {

  const TTL = 3600;

  public function run() {
    $json = function ($d) {
      return json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    };

    $state = self::loadState(CRM_Utils_Request::retrieve('state', 'String'));
    if (CRM_Core_Permission::check('manage OAuth client')) {
      $this->assign('state', $state);
      $this->assign('stateJson', $json($state ?? NULL));
    }

    if (CRM_Utils_Request::retrieve('error', 'String')) {
      CRM_Utils_System::setTitle(ts('OAuth Error'));
      $error = CRM_Utils_Array::subset($_GET, ['error', 'error_description', 'error_uri']);
      $event = \Civi\Core\Event\GenericHookEvent::create([
        'error' => $error['error'] ?? NULL,
        'description' => $error['description'] ?? NULL,
        'uri' => $error['uri'] ?? NULL,
        'state' => $state,
      ]);
      Civi::dispatcher()->dispatch('hook_civicrm_oauthReturnError', $event);

      Civi::log()->info('OAuth returned error', [
        'error' => $error,
        'state' => $state,
      ]);

      $this->assign('error', $error ?? NULL);
    }
    elseif ($authCode = CRM_Utils_Request::retrieve('code', 'String')) {
      $client = \Civi\Api4\OAuthClient::get(FALSE)->addWhere('id', '=', $state['clientId'])->execute()->single();
      $tokenRecord = Civi::service('oauth2.token')->init([
        'client' => $client,
        'scope' => $state['scopes'],
        'tag' => $state['tag'],
        'storage' => $state['storage'],
        'grant_type' => 'authorization_code',
        'cred' => ['code' => $authCode],
      ]);

      $nextUrl = $state['landingUrl'] ?? NULL;
      $event = \Civi\Core\Event\GenericHookEvent::create([
        'token' => $tokenRecord,
        'nextUrl' => &$nextUrl,
      ]);
      Civi::dispatcher()->dispatch('hook_civicrm_oauthReturn', $event);
      if ($nextUrl !== NULL) {
        CRM_Utils_System::redirect($nextUrl);
      }

      CRM_Utils_System::setTitle(ts('OAuth Token Created'));
      if (CRM_Core_Permission::check('manage OAuth client')) {
        $this->assign('token', CRM_OAuth_BAO_OAuthSysToken::redact($tokenRecord));
        $this->assign('tokenJson', $json(CRM_OAuth_BAO_OAuthSysToken::redact($tokenRecord)));
      }
    }
    else {
      throw new \Civi\OAuth\OAuthException("OAuth: Unrecognized return request");
    }

    parent::run();
  }

  /**
   * @param array $stateData
   * @return string
   *   State token / identifier
   */
  public static function storeState($stateData):string {
    $stateId = \CRM_Utils_String::createRandom(20, \CRM_Utils_String::ALPHANUMERIC);

    if (PHP_SAPI === 'cli') {
      // CLI doesn't have a real session, so we can't defend as deeply. However,
      // it's also quite uncommon to run authorizationCode in CLI.
      \Civi::cache('session')->set('OAuthStates_' . $stateId, $stateData, self::TTL);
      return 'c_' . $stateId;
    }
    else {
      // Storing in the bona fide session binds us to the cookie
      $session = \CRM_Core_Session::singleton();
      $session->createScope('OAuthStates');
      $session->set($stateId, $stateData, 'OAuthStates');
      return 'w_' . $stateId;
    }
  }

  /**
   * Restore from the $stateId.
   *
   * @param string $stateId
   * @return mixed
   * @throws \Civi\OAuth\OAuthException
   */
  public static function loadState($stateId) {
    list ($type, $id) = explode('_', $stateId);
    switch ($type) {
      case 'w':
        $state = \CRM_Core_Session::singleton()->get($id, 'OAuthStates');
        break;

      case 'c':
        $state = \Civi::cache('session')->get('OAuthStates_' . $id);
        break;

      default:
        throw new \Civi\OAuth\OAuthException("OAuth: Received invalid or expired state");
    }

    if (!isset($state['time']) || $state['time'] + self::TTL < CRM_Utils_Time::getTimeRaw()) {
      throw new \Civi\OAuth\OAuthException("OAuth: Received invalid or expired state");
    }

    return $state;
  }

}
