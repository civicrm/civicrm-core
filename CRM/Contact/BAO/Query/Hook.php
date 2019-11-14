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

/**
 * Delegate query functions based on hook system.
 */
class CRM_Contact_BAO_Query_Hook {

  /**
   * Query objects.
   *
   * @var CRM_Contact_BAO_Query_Interface[]
   */
  protected $_queryObjects = NULL;

  /**
   * Singleton function used to manage this object.
   *
   * @return object
   */
  public static function singleton() {
    static $singleton = NULL;
    if (!$singleton) {
      $singleton = new CRM_Contact_BAO_Query_Hook();
    }
    return $singleton;
  }

  /**
   * Get or build the list of search objects (via hook).
   *
   * @return array
   *   Array of CRM_Contact_BAO_Query_Interface objects
   */
  public function getSearchQueryObjects() {
    if ($this->_queryObjects === NULL) {
      $this->_queryObjects = [];
      CRM_Utils_Hook::queryObjects($this->_queryObjects, 'Contact');
    }
    return $this->_queryObjects;
  }

  /**
   * @return array
   */
  public function &getFields() {
    $extFields = [];
    foreach (self::getSearchQueryObjects() as $obj) {
      $flds = $obj->getFields();
      $extFields = array_merge($extFields, $flds);
    }
    return $extFields;
  }

  /**
   * @param $apiEntities
   * @param $fieldOptions
   */
  public function alterSearchBuilderOptions(&$apiEntities, &$fieldOptions) {
    foreach (self::getSearchQueryObjects() as $obj) {
      $obj->alterSearchBuilderOptions($apiEntities, $fieldOptions);
    }
  }

  /**
   * Alter search query.
   *
   * @param string $query
   * @param string $fnName
   */
  public function alterSearchQuery(&$query, $fnName) {
    foreach (self::getSearchQueryObjects() as $obj) {
      $obj->$fnName($query);
    }
  }

  /**
   * @param string $fieldName
   * @param $mode
   * @param $side
   *
   * @return string
   */
  public function buildSearchfrom($fieldName, $mode, $side) {
    $from = '';
    foreach (self::getSearchQueryObjects() as $obj) {
      $from .= $obj->from($fieldName, $mode, $side);
    }
    return $from;
  }

  /**
   * @param $tables
   */
  public function setTableDependency(&$tables) {
    foreach (self::getSearchQueryObjects() as $obj) {
      $obj->setTableDependency($tables);
    }
  }

  /**
   * @param $panes
   */
  public function registerAdvancedSearchPane(&$panes) {
    foreach (self::getSearchQueryObjects() as $obj) {
      $obj->registerAdvancedSearchPane($panes);
    }
  }

  /**
   * @param $panes
   */
  public function getPanesMapper(&$panes) {
    foreach (self::getSearchQueryObjects() as $obj) {
      $obj->getPanesMapper($panes);
    }
  }

  /**
   * @param CRM_Core_Form $form
   * @param $type
   */
  public function buildAdvancedSearchPaneForm(&$form, $type) {
    foreach (self::getSearchQueryObjects() as $obj) {
      $obj->buildAdvancedSearchPaneForm($form, $type);
    }
  }

  /**
   * @param $paneTemplatePathArray
   * @param $type
   */
  public function setAdvancedSearchPaneTemplatePath(&$paneTemplatePathArray, $type) {
    foreach (self::getSearchQueryObjects() as $obj) {
      $obj->setAdvancedSearchPaneTemplatePath($paneTemplatePathArray, $type);
    }
  }

}
