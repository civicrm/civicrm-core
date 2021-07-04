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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */


namespace Civi\Api4\Action\MockBasicEntity;

/**
 * This class demonstrates how the getRecords method of Basic\Get can be overridden.
 */
class BatchFrobnicate extends \Civi\Api4\Generic\BasicBatchAction {

  protected function doTask($item) {
    return [
      'identifier' => $item['identifier'],
      'frobnication' => $item['number'] * $item['number'],
    ];
  }

  protected function getSelect() {
    return ['identifier', 'number'];
  }

}
