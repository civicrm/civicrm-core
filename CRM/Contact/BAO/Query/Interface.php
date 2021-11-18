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
 * Abstract class for search BAO query objects
 */
abstract class CRM_Contact_BAO_Query_Interface {

  abstract public function &getFields();

  /**
   * @param string $fieldName
   * @param $mode
   * @param $side
   *
   * @return mixed
   */
  abstract public static function from($fieldName, $mode, $side);

  /**
   * @param $query
   */
  public static function select(&$query) {
  }

  /**
   * @param $query
   */
  public static function where(&$query) {
  }

  /**
   * @param $tables
   */
  public function setTableDependency(&$tables) {
  }

  /**
   * @param $panes
   */
  public function registerAdvancedSearchPane(&$panes) {
  }

  /**
   * @param CRM_Core_Form $form
   * @param $type
   *
   * @return null
   */
  public function buildAdvancedSearchPaneForm(&$form, $type) {
    return NULL;
  }

  /**
   * @param $paneTemplatePathArray
   * @param $type
   *
   * @return null
   */
  public function setAdvancedSearchPaneTemplatePath(&$paneTemplatePathArray, $type) {
    return NULL;
  }

  /**
   * Describe options for available for use in the search-builder.
   *
   * The search builder determines its options by examining the API metadata corresponding to each
   * search field. This approach assumes that each field has a unique-name (ie that the field's
   * unique-name in the API matches the unique-name in the search-builder).
   *
   * @param array $apiEntities
   *   List of entities whose options should be automatically scanned using API metadata.
   * @param array $fieldOptions
   *   Keys are field unique-names; values describe how to lookup the options.
   *   For boolean options, use value "yesno". For pseudoconstants/FKs, use the name of an API entity
   *   from which the metadata of the field may be queried. (Yes - that is a mouthful.)
   * @void
   */
  public function alterSearchBuilderOptions(&$apiEntities, &$fieldOptions) {
  }

  /**
   * @param $mode
   * @param $includeCustomFields
   * @return array|null
   */
  public static function defaultReturnProperties($mode) {
    return NULL;
  }

}
