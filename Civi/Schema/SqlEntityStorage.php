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

namespace Civi\Schema;

use Civi\Api4\Utils\CoreUtil;

class SqlEntityStorage implements EntityStorageInterface {

  /**
   * @var string
   */
  protected string $entityName;

  public function __construct(string $entityName) {
    $this->entityName = $entityName;
  }

  public function writeRecords(array $records): array {
    $entityDAO = CoreUtil::getBAOFromApiName($this->entityName);
    return $entityDAO::writeRecords($records);
  }

  public function deleteRecords(array $records): array {
    // TODO: Implement deleteRecords() method.
  }

}
