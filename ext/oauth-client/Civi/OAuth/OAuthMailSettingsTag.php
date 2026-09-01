<?php
namespace Civi\OAuth;

use Civi;
use Civi\Api4\OAuthClient;
use Civi\Api4\OAuthProvider;
use Civi\Api4\OAuthSysToken;
use Civi\Core\Service\AutoService;
use CRM_OAuth_ExtensionUtil as E;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Describe and manage the OAuth connection behind a Mail Account.
 *
 * A mail account has no column pointing at its OAuth client. The link is the token's
 * tag, `MailSettings:{ID}`, which `CRM_OAuth_MailSetup::alterMailStore()` resolves at
 * poll-time to swap in a fresh access-token.
 *
 * @service oauth_client.mail_settings_tag
 */
class OAuthMailSettingsTag extends AutoService implements EventSubscriberInterface {

  use PickResponseModeTrait;

  public static function getSubscribedEvents(): array {
    return [
      // When editing a Mail Account, report the connection and offer the "Connect"/"Re-connect" buttons.
      '&hook_civicrm_initiators::MailSettings' => ['onMailInitiators', 0],

      // Allow users who administer mail accounts to make OAuthSysTokens for them.
      '&hook_civicrm_oauthGrant' => ['hook_civicrm_oauthGrant', 0],
    ];
  }

  /**
   * Tag applied while the authorization is in flight, before the account exists.
   *
   * @see \CRM_OAuth_MailSetup::setup()
   */
  const SETUP_TAG = 'MailSettings:setup';

  const TOKEN_TAG_PREFIX = 'MailSettings:';

  /**
   * In the "Mail Account" UI, which OAuth response-modes are expected to work?
   */
  const SUPPORTED_RESPONSE_MODES = ['web_message', 'query'];

  /**
   * @param array $context
   * @param array $available
   * @param string|null $default
   * @see \CRM_Utils_Hook::initiators()
   */
  public function onMailInitiators(array $context, array &$available, &$default): void {
    $mailSettingsId = $context['mail_settings_id'] ?? NULL;
    if (!$mailSettingsId) {
      return;
    }

    $token = $this->getToken($mailSettingsId);
    $clients = $this->getMailClients();

    foreach ($clients as $client) {
      $provider = $this->getProviders()[$client['provider']];
      $isConnected = $token && (int) $token['client_id'] === (int) $client['id'];

      // Once connected, only the owning client is relevant -- connecting a second one would
      // orphan the first token and silently change which mailbox is polled.
      if ($token && !$isConnected) {
        continue;
      }

      $providerObj = Civi::service('oauth2.league')->createProvider($client);
      $responseMode = $this->pickResponseMode($providerObj, static::SUPPORTED_RESPONSE_MODES);
      if (!$responseMode) {
        Civi::log()->warning('Provider ' . $client['provider'] . ' is declared as an OAuth service for MailSettings, but it does not declare any compatible response-modes.');
        continue;
      }

      $initiator = [
        'title' => $provider['title'] ?? $provider['name'],
        'render' => $this->createRenderer($client, $provider, $mailSettingsId, $responseMode, (bool) $isConnected),
      ];

      if ($isConnected) {
        $initiator += [
          'is_connected' => TRUE,
          'managed_fields' => ['password'],
          'manage_url' => $this->getManageUrl($client),
        ] + $this->describeToken($token, $provider, $client);
      }

      $available['oauth_' . $client['id']] = $initiator;
    }
  }

  /**
   * Describe the state of the stored token.
   *
   * Deliberately a plain read: refreshing here would make a live HTTP call to the
   * provider every time somebody opens the form.
   *
   * @return array
   *   With keys 'status_message' and 'status_severity'.
   */
  private function describeToken(array $token, array $provider, array $client): array {
    $service = $provider['title'] ?? $provider['name'];
    $account = $token['resource_owner_name'] ?? NULL;

    if (empty($token['expires']) || $token['expires'] >= \CRM_Utils_Time::time()) {
      $message = $account
        ? E::ts('Connected to %1 as %2 (client #%3).', [1 => $service, 2 => $account, 3 => $client['id']])
        : E::ts('Connected to %1 (client #%2).', [1 => $service, 2 => $client['id']]);
      return ['status_severity' => 'success', 'status_message' => $message];
    }

    return [
      'status_severity' => 'warning',
      'status_message' => E::ts('The connection to %1 expired on %2. Mail will not be collected until you re-connect.', [
        1 => $service,
        2 => \CRM_Utils_Date::customFormat(date('Y-m-d H:i:s', $token['expires'])),
      ]),
    ];
  }

  /**
   * Build the callback which renders the "Connect"/"Re-connect" button.
   */
  private function createRenderer(array $client, array $provider, int $mailSettingsId, string $responseMode, bool $isConnected): callable {
    return function (\CRM_Core_Region $region, array $context, array $initiator) use ($client, $provider, $mailSettingsId, $responseMode, $isConnected) {
      $service = $provider['title'] ?? $provider['name'];
      $label = $isConnected ? E::ts('Re-connect to %1', [1 => $service]) : E::ts('Connect to %1', [1 => $service]);

      // Tagging with the existing record id re-connects this account in place,
      // rather than creating a second one the way "Add Mail Account" does.
      $authCodeOptions = [
        'where' => [['id', '=', $client['id']]],
        'storage' => 'OAuthSysToken',
        'tag' => static::TOKEN_TAG_PREFIX . $mailSettingsId,
        'startPage' => 'auto',
        'responseMode' => $responseMode,
        'prompt' => $provider['options']['prompt'] ?? 'select_account',
        'landingUrl' => $this->getMailSettingsUrl($mailSettingsId),
        'ttl' => 9 * 60 * 60,
      ];

      $region->addScriptFile(E::LONG_NAME, 'js/oauth.initiator.js');
      $region->addMarkup(sprintf(
        '<div><a class="btn btn-xs btn-primary" href="#" onclick="CRM.oauth.authorizationCode(%s)">%s</a></div>',
        htmlentities(\CRM_Utils_JSON::encodeScriptVar($authCodeOptions)),
        htmlentities($label)
      ));
    };
  }

  /**
   * If the user may administer mail accounts, they may create system-tokens for `MailSettings:123`.
   *
   * @param \Civi\Api4\Action\OAuthClient\AbstractGrantAction $action
   * @param array $client
   * @param bool $allowed
   *
   * @see \CRM_OAuth_Hook::oauthGrant()
   */
  public function hook_civicrm_oauthGrant($action, array $client, bool &$allowed): void {
    if ($this->checkTokenTag($action->getTag() ?? '')
      && \CRM_Core_Permission::check('access CiviMail')
      && $action->getStorage() === 'OAuthSysToken'
      && !empty($this->getProviders()[$client['provider']]['mailSettingsTemplate'])
    ) {
      $allowed = TRUE;
    }
  }

  /**
   * The most recent token for a mail account, if any.
   *
   * Selects only unprivileged fields, so the status renders for anyone who can reach
   * the form -- `access_token`/`refresh_token` require `manage OAuth client secrets`.
   *
   * @return array|null
   */
  public function getToken(int $mailSettingsId): ?array {
    return OAuthSysToken::get(FALSE)
      ->addSelect('id', 'client_id', 'tag', 'expires', 'resource_owner_name', 'created_date')
      ->addWhere('tag', '=', static::TOKEN_TAG_PREFIX . $mailSettingsId)
      ->addOrderBy('id', 'DESC')
      ->setLimit(1)
      ->execute()
      ->first();
  }

  /**
   * Check if the token-tag specifies "MailSettings:{ID}".
   *
   * Note that "MailSettings:setup" is a legitimate tag for an authorization which has
   * not yet produced a record, and is not a match.
   *
   * @param string $tag
   *   Ex: "MailSettings:123"
   * @return int|null
   *   Ex: 123
   */
  public function checkTokenTag(string $tag): ?int {
    if (!str_starts_with($tag, static::TOKEN_TAG_PREFIX)) {
      return NULL;
    }
    $tagParts = explode(':', $tag);
    return \CRM_Utils_Type::validate($tagParts[1] ?? '', 'Positive', FALSE);
  }

  /**
   * OAuth clients whose provider knows how to set up a mail account.
   */
  private function getMailClients(): array {
    $mailProviders = array_keys(array_filter($this->getProviders(),
      fn($provider) => !empty($provider['mailSettingsTemplate'])));
    if (!$mailProviders) {
      return [];
    }
    return (array) OAuthClient::get(FALSE)
      ->addWhere('provider', 'IN', $mailProviders)
      ->addWhere('is_active', '=', 1)
      ->addOrderBy('id')
      ->execute()
      ->getArrayCopy();
  }

  private function getProviders(): array {
    return OAuthProvider::get(FALSE)->execute()->indexBy('name')->getArrayCopy();
  }

  private function getManageUrl(array $client): string {
    // The client admin screen selects by provider; the client id is named in the status message.
    return \CRM_Utils_System::url('civicrm/admin/oauth', NULL, TRUE, '!/?provider=' . urlencode($client['provider']));
  }

  private function getMailSettingsUrl(int $mailSettingsId): string {
    return \CRM_Utils_System::url('civicrm/admin/mailSettings/edit', [
      'action' => 'update',
      'id' => $mailSettingsId,
      'reset' => 1,
    ], TRUE, NULL, FALSE);
  }

}
