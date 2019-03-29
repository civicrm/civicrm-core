<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

namespace Civi\Core\SqlTrigger;

use Civi\Core\Event\GenericHookEvent;

/**
 * Build a set of SQL triggers for tracking timestamps on an entity.
 *
 * This class is a generalization of CRM-10554 with the aim of enabling CRM-20958.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */
class TimestampTriggers {

  /**
   * @var string
   *   SQL table name.
   *   Ex: 'civicrm_contact', 'civicrm_activity'.
   */
  private $tableName;

  /**
   * @var string
   *   An entity name (from civicrm_custom_group.extends).
   *   Ex: 'Contact', 'Activity'.
   */
  private $customDataEntity;

  /**
   * @var string
   *   SQL column name.
   *   Ex: 'created_date'.
   */
  private $createdDate;

  /**
   * @var string
   *   SQL column name.
   *   Ex: 'modified_date'.
   */
  private $modifiedDate;

  /**
   * @var array
   *   Ex: $relations[0] == array('table' => 'civicrm_bar', 'column' => 'foo_id');
   */
  private $relations;

  /**
   * @param string $tableName
   *   SQL table name.
   *   Ex: 'civicrm_contact', 'civicrm_activity'.
   * @param string $customDataEntity
   *   An entity name (from civicrm_custom_group.extends).
   *   Ex: 'Contact', 'Activity'.
   * @return TimestampTriggers
   */
  public static function create($tableName, $customDataEntity) {
    return new static($tableName, $customDataEntity);
  }

  /**
   * TimestampTriggers constructor.
   *
   * @param string $tableName
   *   SQL table name.
   *   Ex: 'civicrm_contact', 'civicrm_activity'.
   * @param string $customDataEntity
   *   An entity name (from civicrm_custom_group.extends).
   *   Ex: 'Contact', 'Activity'.
   * @param string $createdDate
   *   SQL column name.
   *   Ex: 'created_date'.
   * @param string $modifiedDate
   *   SQL column name.
   *   Ex: 'modified_date'.
   * @param array $relations
   *   Ex: $relations[0] == array('table' => 'civicrm_bar', 'column' => 'foo_id');
   */
  public function __construct(
    $tableName,
    $customDataEntity,
    $createdDate = 'created_date',
    $modifiedDate = 'modified_date',
    $relations = []
  ) {
    $this->tableName = $tableName;
    $this->customDataEntity = $customDataEntity;
    $this->createdDate = $createdDate;
    $this->modifiedDate = $modifiedDate;
    $this->relations = $relations;
  }

  /**
   * Add our list of triggers to the global list.
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see \CRM_Utils_Hook::triggerInfo
   */
  public function onTriggerInfo($e) {
    $this->alterTriggerInfo($e->info, $e->tableName);
  }

  /**
   * Add our list of triggers to the global list.
   *
   * @see \CRM_Utils_Hook::triggerInfo
   * @see \CRM_Core_DAO::triggerRebuild
   *
   * @param array $info
   *   See hook_civicrm_triggerInfo.
   * @param string|NULL $tableFilter
   *   See hook_civicrm_triggerInfo.
   */
  public function alterTriggerInfo(&$info, $tableFilter = NULL) {
    // If we haven't upgraded yet, then the created_date/modified_date may not exist.
    // In the past, this was a version-based check, but checkFieldExists()
    // seems more robust.
    if (\CRM_Core_Config::isUpgradeMode()) {
      if (!\CRM_Core_BAO_SchemaHandler::checkIfFieldExists($this->getTableName(),
        $this->getCreatedDate())
      ) {
        return;
      }
    }

    if ($tableFilter == NULL || $tableFilter == $this->getTableName()) {
      $info[] = [
        'table' => [$this->getTableName()],
        'when' => 'BEFORE',
        'event' => ['INSERT'],
        'sql' => "\nSET NEW.{$this->getCreatedDate()} = CURRENT_TIMESTAMP;\n",
      ];
    }

    // Update timestamp when modifying closely related tables
    $relIdx = \CRM_Utils_Array::index(
      ['column', 'table'],
      $this->getAllRelations()
    );
    foreach ($relIdx as $column => $someRelations) {
      $this->generateTimestampTriggers($info, $tableFilter,
        array_keys($someRelations), $column);
    }
  }

  /**
   * Generate triggers to update the timestamp.
   *
   * The corresponding civicrm_FOO row is updated on insert/update/delete
   * to a table that extends civicrm_FOO.
   * Don't regenerate triggers for all such tables if only asked for one table.
   *
   * @param array $info
   *   Reference to the array where generated trigger information is being stored
   * @param string|null $tableFilter
   *   Name of the table for which triggers are being generated, or NULL if all tables
   * @param array $relatedTableNames
   *   Array of all core or all custom table names extending civicrm_FOO
   * @param string $contactRefColumn
   *   'contact_id' if processing core tables, 'entity_id' if processing custom tables
   *
   * @link https://issues.civicrm.org/jira/browse/CRM-15602
   * @see triggerInfo
   */
  public function generateTimestampTriggers(
    &$info,
    $tableFilter,
    $relatedTableNames,
    $contactRefColumn
  ) {
    // Safety
    $contactRefColumn = \CRM_Core_DAO::escapeString($contactRefColumn);

    // If specific related table requested, just process that one.
    // (Reply: This feels fishy.)
    if (in_array($tableFilter, $relatedTableNames)) {
      $relatedTableNames = [$tableFilter];
    }

    // If no specific table requested (include all related tables),
    // or a specific related table requested (as matched above)
    if (empty($tableFilter) || isset($relatedTableNames[$tableFilter])) {
      $info[] = [
        'table' => $relatedTableNames,
        'when' => 'AFTER',
        'event' => ['INSERT', 'UPDATE'],
        'sql' => "\nUPDATE {$this->getTableName()} SET {$this->getModifiedDate()} = CURRENT_TIMESTAMP WHERE id = NEW.$contactRefColumn;\n",
      ];
      $info[] = [
        'table' => $relatedTableNames,
        'when' => 'AFTER',
        'event' => ['DELETE'],
        'sql' => "\nUPDATE {$this->getTableName()} SET {$this->getModifiedDate()} = CURRENT_TIMESTAMP WHERE id = OLD.$contactRefColumn;\n",
      ];
    }
  }

  /**
   * @return string
   */
  public function getTableName() {
    return $this->tableName;
  }

  /**
   * @param string $tableName
   * @return TimestampTriggers
   */
  public function setTableName($tableName) {
    $this->tableName = $tableName;
    return $this;
  }

  /**
   * @return string
   */
  public function getCustomDataEntity() {
    return $this->customDataEntity;
  }

  /**
   * @param string $customDataEntity
   * @return TimestampTriggers
   */
  public function setCustomDataEntity($customDataEntity) {
    $this->customDataEntity = $customDataEntity;
    return $this;
  }

  /**
   * @return string
   */
  public function getCreatedDate() {
    return $this->createdDate;
  }

  /**
   * @param string $createdDate
   * @return TimestampTriggers
   */
  public function setCreatedDate($createdDate) {
    $this->createdDate = $createdDate;
    return $this;
  }

  /**
   * @return string
   */
  public function getModifiedDate() {
    return $this->modifiedDate;
  }

  /**
   * @param string $modifiedDate
   * @return TimestampTriggers
   */
  public function setModifiedDate($modifiedDate) {
    $this->modifiedDate = $modifiedDate;
    return $this;
  }

  /**
   * @return array
   *   Each item is an array('table' => string, 'column' => string)
   */
  public function getRelations() {
    return $this->relations;
  }

  /**
   * @param array $relations
   * @return TimestampTriggers
   */
  public function setRelations($relations) {
    $this->relations = $relations;
    return $this;
  }

  /**
   * Get a list of all tracked relations.
   *
   * This is basically the curated list (`$this->relations`) plus any custom data.
   *
   * @return array
   *   Each item is an array('table' => string, 'column' => string)
   */
  public function getAllRelations() {
    $relations = $this->getRelations();

    if ($this->getCustomDataEntity()) {
      $customGroupDAO = \CRM_Core_BAO_CustomGroup::getAllCustomGroupsByBaseEntity($this->getCustomDataEntity());
      $customGroupDAO->is_multiple = 0;
      $customGroupDAO->find();
      while ($customGroupDAO->fetch()) {
        $relations[] = [
          'table' => $customGroupDAO->table_name,
          'column' => 'entity_id',
        ];
      }
    }

    return $relations;
  }

}
