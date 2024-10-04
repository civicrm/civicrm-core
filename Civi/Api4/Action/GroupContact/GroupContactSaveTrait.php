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

namespace Civi\Api4\Action\GroupContact;

/**
 * @inheritDoc
 *
 * @method $this setMethod(string $method) Indicate who added/removed the group.
 * @method string getMethod()
 * @method $this setTracking(string $tracking) Specify ip address or other tracking info.
 * @method string getTracking()
 */
trait GroupContactSaveTrait {

  /**
   * String to indicate who added/removed the group.
   *
   * @var string
   * @optionsCallback getSubscriptionHistoryMethods
   */
  protected $method = 'API';

  /**
   * IP address or other tracking info about who performed this group subscription.
   *
   * @var string
   */
  protected $tracking = '';

  /**
   * @inheritDoc
   */
  protected function write(array $items) {
    foreach ($items as &$item) {
      $item['method'] = $this->method;
      $item['tracking'] = $this->tracking;
    }
    return \CRM_Contact_BAO_GroupContact::writeRecords($items);
  }

  public static function getSubscriptionHistoryMethods(): array {
    return array_keys(\CRM_Core_SelectValues::getSubscriptionHistoryMethods());
  }

}
