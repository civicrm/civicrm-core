<?php
namespace Civi\Api4\Action\OAuthSysToken;

use Civi\Api4\Action\AbstractRefreshAction;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

class Refresh extends AbstractRefreshAction {

  protected function getKeyField(): string {
    return 'id';
  }

  protected function handleRefreshError(array $row, IdentityProviderException $e) {
    // The provider rejected the token (e.g. it was revoked). The batch logs the exception and leaves
    // this record out of the result, so record why here for callers and screens to report.
    $this->recordError($row[$this->getKeyField()], $e);
    throw $e;
  }

  protected function getExtraUpdateValues(): array {
    return ['error' => NULL];
  }

  private function recordError(int $id, IdentityProviderException $e): void {
    $body = $e->getResponseBody();
    civicrm_api4($this->getEntityName(), 'update', [
      'checkPermissions' => FALSE,
      'where' => [['id', '=', $id]],
      'values' => [
        'error' => [
          'error' => $e->getMessage(),
          'error_description' => is_array($body) ? ($body['error_description'] ?? NULL) : NULL,
          'time' => \CRM_Utils_Time::time(),
        ],
      ],
    ]);
  }

}
