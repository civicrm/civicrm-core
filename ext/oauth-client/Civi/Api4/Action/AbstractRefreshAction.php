<?php
namespace Civi\Api4\Action;

use Civi\Api4\Generic\BasicBatchAction;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

/**
 * Base action for refreshing OAuth tokens.
 *
 * When preparing to connect to a remote OAuth system, use the "refresh" action
 * to simultaneously refresh and return the auth token.
 *
 * Note that it is possible to refresh a token without having permission to view
 * or edit the specific secrets involved. The result will adjust according to permissions:
 *
 * - If permission-checks are disabled, or if you have permission to manage secrets,
 *   then this will return the full token record.
 * - If permission-checks are active and you only have access to "refresh" (but
 *   not to secrets), it will return a minimalist record to indicate completion.
 *
 * @method $this setThreshold(int $limit)
 * @method int getThreshold()
 */
abstract class AbstractRefreshAction extends BasicBatchAction {

  /**
   * Refresh records if they are within the given threshold for expiration.
   *
   * Ex: If your token is approaching expiration in 5 seconds, and if your
   * threshold is 60 seconds, then the token will refresh. But if your token
   * still has 5 minutes, then there's no need to refresh.
   *
   * A negative threshold will always refresh.
   *
   * @var int
   */
  protected $threshold = 60;

  private $syncFields = ['access_token', 'refresh_token', 'expires', 'token_type'];
  private $writeFields = ['access_token', 'refresh_token', 'expires', 'token_type', 'raw'];
  private $baseSelectFields = ['client_id', 'tag', 'access_token', 'refresh_token', 'expires', 'token_type', 'raw'];
  private $providers = [];

  /**
   * Primary key field for the token entity.
   */
  abstract protected function getKeyField(): string;

  /**
   * Handle an IdentityProviderException encountered during token refresh.
   *
   * @param array $row
   * @param IdentityProviderException $e
   * @return array
   * @throws IdentityProviderException
   */
  abstract protected function handleRefreshError(array $row, IdentityProviderException $e);

  protected function getSelect() {
    return array_merge([$this->getKeyField()], $this->baseSelectFields);
  }

  protected function doTask($row) {
    if ($this->threshold >= 0 && \CRM_Utils_Time::time() < $row['expires'] - $this->threshold) {
      return $this->filterReturn($row);
    }

    $provider = $this->getProvider($row['client_id']);
    try {
      $newToken = $provider->getAccessToken('refresh_token', [
        'refresh_token' => $row['refresh_token'],
      ]);
    }
    catch (IdentityProviderException $e) {
      return $this->handleRefreshError($row, $e);
    }

    $raw = $newToken->jsonSerialize();
    $row['raw'] = $raw;
    foreach ($this->syncFields as $field) {
      if (isset($raw[$field])) {
        $row[$field] = $raw[$field];
      }
    }

    \CRM_OAuth_Hook::oauthToken('refresh', $this->getEntityName(), $row);

    $this->saveRefreshedToken($row);

    return $this->filterReturn($row);
  }

  protected function saveRefreshedToken(array $row): void {
    civicrm_api4($this->getEntityName(), 'update', [
      // You may have permission to refresh even if you can't inspect/update secrets directly.
      'checkPermissions' => FALSE,
      'where' => [[$this->getKeyField(), '=', $row[$this->getKeyField()]]],
      'values' => \CRM_Utils_Array::subset($row, $this->writeFields) + $this->getExtraUpdateValues(),
    ]);
  }

  protected function getExtraUpdateValues(): array {
    return [];
  }

  protected function getProvider($clientId) {
    if (!isset($this->providers[$clientId])) {
      $client = \Civi\Api4\OAuthClient::get(FALSE)->addWhere('id', '=', $clientId)->execute()->single();
      $this->providers[$clientId] = \Civi::service('oauth2.league')->createProvider($client);
    }
    return $this->providers[$clientId];
  }

  protected function filterReturn($tokenRecord) {
    return $this->checkPermissions ? \CRM_OAuth_BAO_OAuthSysToken::redact($tokenRecord) : $tokenRecord;
  }

}
