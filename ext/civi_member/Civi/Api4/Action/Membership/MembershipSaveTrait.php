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

namespace Civi\Api4\Action\Membership;

/**
 * Code shared by Membership create/update/save actions
 */
trait MembershipSaveTrait {

  /**
   * @inheritDoc
   */
  protected function write(array $items) {
    foreach ($items as &$item) {
      // Required by Membership BAO so we can deprecate lineItem processing.
      // ie. API4 does less than direct BAO::create or API3.
      // See https://github.com/civicrm/civicrm-core/pull/35628
      $item['version'] = 4;
    }
    return parent::write($items);
  }

}
