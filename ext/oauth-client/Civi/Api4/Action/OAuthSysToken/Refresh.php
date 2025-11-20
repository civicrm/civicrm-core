<?php
namespace Civi\Api4\Action\OAuthSysToken;

use Civi\Api4\Generic\BasicBatchAction;

/**
 * Class Refresh
 * @package Civi\Api4\Action\OAuthSysToken
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
class Refresh extends BasicBatchAction {

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
  private $selectFields = ['id', 'client_id', 'tag', 'access_token', 'refresh_token', 'expires', 'token_type', 'raw'];
  private $providers = [];

  protected function getSelect() {
    return $this->selectFields;
  }

  protected function doTask($row) {
    if ($this->threshold >= 0 && \CRM_Utils_Time::time() < $row['expires'] - $this->threshold) {
      return $this->filterReturn($row);
    }

    $provider = $this->getProvider($row['client_id']);
    $newToken = $provider->getAccessToken('refresh_token', [
      'refresh_token' => $row['refresh_token'],
    ]);

    $raw = $newToken->jsonSerialize();
    $row['raw'] = $raw;
    foreach ($this->syncFields as $field) {
      if (isset($raw[$field])) {
        $row[$field] = $raw[$field];
      }
    }

    \CRM_OAuth_Hook::oauthToken('refresh', $this->getEntityName(), $row);

    civicrm_api4($this->getEntityName(), 'update', [
      // You may have permission to refresh even if you can't inspect/update secrets directly.
      'checkPermissions' => FALSE,
      'where' => [['id', '=', $row['id']]],
      'values' => \CRM_Utils_Array::subset($row, $this->writeFields),
    ])->single();

    return $this->filterReturn($row);
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
