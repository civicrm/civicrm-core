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

namespace Civi\Api4\Service\Schema\Joinable;

class CustomGroupJoinable extends Joinable {

  /**
   * @var string
   */
  protected $joinSide = self::JOIN_SIDE_LEFT;

  /**
   * @var string
   *
   * Name of the custom field column.
   */
  protected $columns;

  /**
   * @param $targetTable
   * @param $alias
   * @param bool $isMultiRecord
   * @param string $entityName
   */
  public function __construct($targetTable, $alias, $isMultiRecord, $entityName) {
    $this->entity = $entityName;
    parent::__construct($targetTable, 'entity_id', $alias);
    $this->joinType = $isMultiRecord ?
      self::JOIN_TYPE_ONE_TO_MANY : self::JOIN_TYPE_ONE_TO_ONE;
  }

  /**
   * @return string|null
   */
  public function getEntity() {
    // Only multi-record groups are considered an api "entity"
    $isMultiRecord = $this->joinType === self::JOIN_TYPE_ONE_TO_MANY;
    return $isMultiRecord ? $this->entity : NULL;
  }

}
