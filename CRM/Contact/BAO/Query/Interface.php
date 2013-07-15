<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * Abstract class for search BAO query objects
 */
abstract class CRM_Contact_BAO_Query_Interface {

  abstract public function &getFields();
  abstract public function from($fieldName, $mode, $side);

  public function select(&$query) {
    return NULL;
  }

  public function where(&$query) {
    return NULL;
  }

  public function setTableDependency(&$tables) {
    return NULL;
  }

  public function registerAdvancedSearchPane(&$panes) {
    return NULL;
  }

  public function buildAdvancedSearchPaneForm(&$form, $type) {
    return NULL;
  }

  public function setAdvancedSearchPaneTemplatePath(&$paneTemplatePathArray, $type) {
    return NULL;
  }
}