<?php
use CRM_OAuth_ExtensionUtil as E;

class CRM_OAuth_Page_Return extends CRM_Core_Page {

  const TTL = 3600;

  public function run() {
    $state = self::loadState(CRM_Utils_Request::retrieve('state', 'String'));

    if (CRM_Utils_Request::retrieve('error', 'String')) {
      $error = CRM_Utils_Array::subset($_GET, ['error', 'error_description', 'error_uri']);
    }
    elseif ($authCode = CRM_Utils_Request::retrieve('code', 'String')) {
      $client = \Civi\Api4\OAuthClient::get(0)->addWhere('id', '=', $state['clientId'])->execute()->single();
      $tokenRecord = Civi::service('oauth2.token')->init([
        'client' => $client,
        'scope' => $state['scopes'],
        'tag' => $state['tag'],
        'storage' => $state['storage'],
        'grant_type' => 'authorization_code',
        'cred' => ['code' => $authCode],
      ]);
    }
    else {
      throw new \Civi\OAuth\OAuthException("OAuth: Unrecognized return request");
    }

    $json = function ($d) {
      return json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    };
    $this->assign('state', $json($state));
    $this->assign('token', $json($tokenRecord ?? NULL));
    $this->assign('error', $json($error ?? NULL));

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
