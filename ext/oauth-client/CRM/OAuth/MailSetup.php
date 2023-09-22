<?php

class CRM_OAuth_MailSetup {

  /**
   * Return a list of setup-options based on OAuth2 services.
   *
   * @see CRM_Utils_Hook::mailSetupActions()
   */
  public static function buildSetupLinks() {
    $clients = Civi\Api4\OAuthClient::get(FALSE)->addWhere('is_active', '=', 1)->execute();
    $providers = Civi\Api4\OAuthProvider::get(FALSE)->execute()->indexBy('name');

    $setupActions = [];
    foreach ($clients as $client) {
      $provider = $providers[$client['provider']] ?? NULL;
      if ($provider === NULL) {
        continue;
      }
      // v api OptionValue.get option_group_id=mail_protocol
      if (!empty($provider['mailSettingsTemplate'])) {
        $setupActions['oauth_' . $client['id']] = [
          'title' => sprintf('%s (ID #%s)', $provider['title'] ?? $provider['name'] ?? ts('OAuth2'), $client['id']),
          'callback' => ['CRM_OAuth_MailSetup', 'setup'],
          'oauth_client_id' => $client['id'],
          'prompt' => $provider['options']['prompt'] ?? NULL,
        ];
      }
    }

    return $setupActions;
  }

  /**
   * When a user chooses to add one of our mail options, we kick off
   * the authorization-code workflow.
   *
   * @param array $setupAction
   *   The chosen descriptor from mailSetupActions.
   * @return array
   *   With keys:
   *   - url: string, the final URL to go to.
   * @see CRM_Utils_Hook::mailSetupActions()
   */
  public static function setup($setupAction) {
    $authCode = Civi\Api4\OAuthClient::authorizationCode(0)
      ->addWhere('id', '=', $setupAction['oauth_client_id'])
      ->setStorage('OAuthSysToken')
      ->setTag('MailSettings:setup')
      ->setPrompt($setupAction['prompt'] ?? 'select_account')
      ->execute()
      ->single();

    return [
      'url' => $authCode['url'],
    ];
  }

  /**
   * When the user returns with a token, we add a new record to
   * civicrm_mail_settings with defaults and redirect to the edit screen.
   *
   * @param array $token
   *   OAuthSysToken
   * @param string $nextUrl
   */
  public static function onReturn($token, &$nextUrl) {
    if ($token['tag'] !== 'MailSettings:setup') {
      return;
    }

    $client = \Civi\Api4\OAuthClient::get(FALSE)->addWhere('id', '=', $token['client_id'])->execute()->single();
    $provider = \Civi\Api4\OAuthProvider::get(FALSE)->addWhere('name', '=', $client['provider'])->execute()->single();

    $vars = ['token' => $token, 'client' => $client, 'provider' => $provider];
    $mailSettings = civicrm_api4('MailSettings', 'create', [
      'values' => self::evalArrayTemplate($provider['mailSettingsTemplate'], $vars),
    ])->single();

    \Civi\Api4\OAuthSysToken::update(FALSE)
      ->addWhere('id', '=', $token['id'])
      ->setValues(['tag' => 'MailSettings:' . $mailSettings['id']])
      ->execute();

    CRM_Core_Session::setStatus(
      ts('Here are the account defaults we detected for %1. Please check them carefully.', [
        1 => $mailSettings['name'],
      ]),
      ts('Account created!'),
      'info'
    );

    $nextUrl = CRM_Utils_System::url('civicrm/admin/mailSettings/edit', [
      'action' => 'update',
      'id' => $mailSettings['id'],
      'reset' => 1,
    ], TRUE, NULL, FALSE);
  }

  /**
   * @param array $template
   *   List of key-value expressions.
   *   Ex: ['name' => '{{person.first}} {{person.last}}']
   *   Expressions begin with the dotted-name of a variable.
   *   Optionally, the value may be piped through other functions
   * @param array $vars
   *   Array tree of data to interpolate.
   * @return array
   *   The template array, with '{{...}}' expressions evaluated.
   */
  public static function evalArrayTemplate($template, $vars) {
    $filters = [
      'getMailDomain' => function($v) {
        $parts = explode('@', $v);
        return $parts[1] ?? NULL;
      },
      'getMailUser' => function($v) {
        $parts = explode('@', $v);
        return $parts[0] ?? NULL;
      },
    ];

    $lookupVars = function($m) use ($vars, $filters) {
      $parts = explode('|', $m[1]);
      $value = (string) CRM_Utils_Array::pathGet($vars, explode('.', array_shift($parts)));
      foreach ($parts as $part) {
        if (isset($filters[$part])) {
          $value = $filters[$part]($value);
        }
        else {
          $value = NULL;
        }
      }
      return $value;
    };

    $values = [];
    foreach ($template as $key => $value) {
      $values[$key] = is_string($value)
        ? preg_replace_callback(';{{([a-zA-Z0-9_\.\|]+)}};', $lookupVars, $value)
        : $value;
    }
    return $values;
  }

  /**
   * If we have a stored token for this for this, then use it.
   *
   * @see CRM_Utils_Hook::alterMailStore()
   */
  public static function alterMailStore(&$mailSettings) {
    $token = civicrm_api4('OAuthSysToken', 'refresh', [
      'checkPermissions' => FALSE,
      'where' => [['tag', '=', 'MailSettings:' . $mailSettings['id']]],
      'orderBy' => ['id' => 'DESC'],
    ])->first();

    if ($token === NULL) {
      return;
    }
    // Not certain if 'refresh' will complain about staleness. Doesn't hurt to double-check.
    if (empty($token['access_token']) || $token['expires'] < CRM_Utils_Time::time()) {
      throw new \Civi\OAuth\OAuthException("Found invalid token for mail store #" . $mailSettings['id']);
    }

    $mailSettings['auth'] = 'XOAuth2';
    $mailSettings['password'] = $token['access_token'];
  }

}
