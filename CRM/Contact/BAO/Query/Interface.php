<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
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
  abstract public function from($fieldName, $mode, $side);

  /**
   * @param $query
   *
   * @return null
   */
  public function select(&$query) {
    return NULL;
  }

  /**
   * @param $query
   *
   * @return null
   */
  public function where(&$query) {
    return NULL;
  }

  /**
   * @param $tables
   *
   * @return null
   */
  public function setTableDependency(&$tables) {
    return NULL;
  }

  /**
   * @param $panes
   *
   * @return null
   */
  public function registerAdvancedSearchPane(&$panes) {
    return NULL;
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

}
