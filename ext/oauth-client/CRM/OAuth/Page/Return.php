<?php
use CRM_OAuth_ExtensionUtil as E;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

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
      $this->showError(CRM_Utils_Array::subset($_GET, ['error', 'error_description', 'error_uri']), $state);
    }
    elseif ($authCode = CRM_Utils_Request::retrieve('code', 'String')) {
      $client = \Civi\Api4\OAuthClient::get(FALSE)->addWhere('id', '=', $state['clientId'])->execute()->single();
      try {
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
      }
      catch (IdentityProviderException $e) {
        // The provider accepted the sign-in but refused to issue a token, e.g. because the client secret has expired.
        $body = $e->getResponseBody();
        $this->showError([
          'error' => $e->getMessage(),
          'error_description' => is_array($body) ? ($body['error_description'] ?? NULL) : NULL,
          'error_uri' => is_array($body) ? ($body['error_uri'] ?? NULL) : NULL,
        ], $state);
        parent::run();
        return;
      }

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

  private function showError(array $error, array $state): void {
    $error += ['error' => NULL, 'error_description' => NULL, 'error_uri' => NULL];
    CRM_Utils_System::setTitle(ts('OAuth Error'));
    CRM_OAuth_Hook::oauthReturnError($error['error'], $error['error_description'], $error['error_uri'], $state);

    Civi::log()->info('OAuth returned error', [
      'error' => $error,
      'state' => $state,
    ]);

    $this->assign('error', $error);
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
