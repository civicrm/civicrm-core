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

namespace Civi\Api4\Action\EntityTag;

/**
 * @inheritDoc
 */
trait EntityTagSaveTrait {

  /**
   * Override method which defaults to 'create' for oddball DAO which uses 'add'
   *
   * @param array $items
   * @return array
   */
  protected function write(array $items) {
    $saved = [];
    foreach ($items as $item) {
      $saved[] = \CRM_Core_BAO_EntityTag::add($item);
    }
    return $saved;
  }

}
