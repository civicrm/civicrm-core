<?php

namespace Civi\Api4\Action\OAuthSessionToken;

use Civi\Api4\Generic\BasicBatchAction;
use Civi\Api4\Generic\Result;

/**
 * Delete one or more $ENTITIES.
 *
 * If a `where` parameter is given, it is used to restrict which $ENTITIES
 * are deleted. Otherwise all are deleted.
 */
class Delete extends BasicBatchAction {
  /**
   * Criteria for selecting $ENTITIES to delete. This can be left empty, in
   * which case all $ENTITIES will be deleted.
   *
   * @var array
   */
  protected $where = [];

  protected function processBatch(Result $result, array $items) {
    $session = \CRM_Core_Session::singleton();
    $allTokens = $session->get('OAuthSessionTokens') ?? [];
    foreach ($items as $item) {
      unset($allTokens[$item['cardinal']]);
      $result[] = $item;
    }
    $session->set('OAuthSessionTokens', $allTokens);
  }

}
