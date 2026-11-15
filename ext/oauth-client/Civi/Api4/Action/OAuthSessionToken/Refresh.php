<?php
namespace Civi\Api4\Action\OAuthSessionToken;

use Civi\Api4\Action\AbstractRefreshAction;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

class Refresh extends AbstractRefreshAction {

  protected function getKeyField(): string {
    return 'cardinal';
  }

  protected function handleRefreshError(array $row, IdentityProviderException $e): array {
    // Token could not be refreshed and is expired.
    // Remove it from the session
    civicrm_api4($this->getEntityName(), 'delete', [
      // You may have permission to refresh even if you can't inspect/update secrets directly.
      'checkPermissions' => FALSE,
      'where' => [[$this->getKeyField(), '=', $row[$this->getKeyField()]]],
    ]);
    return $this->filterReturn($row);
  }

}
