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

namespace Civi\Api4\Action\Mailing;

trait MailingSaveTrait {

  /**
   * @inheritDoc
   */
  protected function write(array $items) {
    foreach ($items as &$item) {
      // Required by Mailing & MailingJob to avoid legacy behaviour.
      $item['skip_legacy_scheduling'] = TRUE;
    }
    return parent::write($items);
  }

}
