<?php
use CRM_OAuth_ExtensionUtil as E;

class CRM_OAuth_Page_Return extends CRM_Core_Page {

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
      CRM_OAuth_Hook::oauthReturnError(
        $error['error'] ?? NULL,
        $error['description'] ?? NULL,
        $error['uri'] ?? NULL,
        $state,
      );

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
        'grant_type' => $state['grant_type'] ?? 'authorization_code',
        'cred' => array_merge(
          ['code' => $authCode],
          empty($state['code_verifier']) ? [] : ['code_verifier' => $state['code_verifier']],
        ),
      ]);

      $nextUrl = $state['landingUrl'] ?? NULL;
      CRM_OAuth_Hook::oauthReturn($tokenRecord, $nextUrl);
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
   * @deprecated
   */
  public static function storeState($stateData):string {
    return Civi::service('oauth2.state')->store($stateData);
  }

  /**
   * Restore from the $stateId.
   *
   * @param string $stateId
   * @return mixed
   * @throws \Civi\OAuth\OAuthException
   * @deprecated
   */
  public static function loadState($stateId) {
    return Civi::service('oauth2.state')->load($stateId);
  }

}
