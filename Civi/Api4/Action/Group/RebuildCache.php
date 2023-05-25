<?php

/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\Api4\Action\Group;

use Civi\Api4\Generic\Result;

/**
 * Rebuild the group contact cache.
 *
 * @method $this setLimit(int $limit) Set limit - number of groups to rebuild
 * @method int getLimit() Get contact ID param
 */
class RebuildCache extends \Civi\Api4\Generic\AbstractAction {

  /**
   * Limit: number of groups to rebuild
   *
   * @var int
   */
  protected $limit;

  /**
   * @param \Civi\Api4\Generic\Result $result
   */
  public function _run(Result $result) {
    \CRM_Contact_BAO_GroupContactCache::lockAndLoad(NULL, $this->limit ?? 0);
  }

}
