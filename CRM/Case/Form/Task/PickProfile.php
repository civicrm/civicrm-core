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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This class provides the functionality for batch profile update for case
 */
class CRM_Case_Form_Task_PickProfile extends CRM_Core_Form_Task_PickProfile {

  /**
   * Must be set to entity table name (eg. civicrm_participant) by child class
   *
   * @var string
   */
  public static $tableName = 'civicrm_case';

  /**
   * Must be set to entity shortname (eg. event)
   *
   * @var string
   */
  public static $entityShortname = 'case';

  /**
   * Get the name of the table for the relevant entity.
   *
   * @return string
   */
  public function getTableName() {
    return $this::$tableName;
  }

  /**
   * Get the query mode (eg. CRM_Core_BAO_Query::MODE_CASE)
   *
   * @return int
   */
  public function getQueryMode() {
    return CRM_Contact_BAO_Query::MODE_CASE;
  }

  /**
   * Get the group by clause for the component.
   *
   * @return string
   */
  public function getEntityAliasField() {
    return $this::$entityShortname . '_id';
  }

}
