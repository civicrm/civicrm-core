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
      $this->assertNotStale($item);
    }
    return parent::write($items);
  }

  /**
   * Refuse to save a mailing from a copy older than the stored one.
   *
   * The CiviMail editor sends the `modified_date` it loaded with every save, so a second tab or
   * user would otherwise overwrite newer changes. This is the check APIv3 Mailing.create makes
   * (CRM-20892), which the editor bypasses since it saves through APIv4.
   *
   * @param array $item
   *
   * @throws \CRM_Core_Exception
   */
  private function assertNotStale(array $item): void {
    if (empty($item['id']) || empty($item['modified_date'])) {
      return;
    }
    $stored = \CRM_Core_DAO::singleValueQuery('SELECT modified_date FROM civicrm_mailing WHERE id = %1',
      [1 => [(int) $item['id'], 'Integer']]);
    if ($stored && strtotime($stored) > strtotime($item['modified_date'])) {
      throw new \CRM_Core_Exception(ts('Mailing has not been saved, Content maybe out of date, please refresh the page and try again'));
    }
  }

}
