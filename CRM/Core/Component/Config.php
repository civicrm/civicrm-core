<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * This abstract class provides the framework for component configuration
 * and provides aggregation methods for injecting it into system config.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

abstract class CRM_Core_Component_Config {

  /**
   * Gets the list of class variables from specific component's
   * configuration file and injects them into system wide
   * configuration object.
   *
   * @param $config
   * @param $oldMode
   *
   * @return array collection of component settings
   * @access public
   */
  public function add($config, $oldMode) {
    foreach (get_class_vars(get_class($this)) as $key => $value) {
      $config->$key = $value;
    }
  }

  /**
   * TODO
   */
  public function setDefaults(&$defaults) {
    foreach (get_class_vars(get_class($this)) as $key => $value) {
      if (!isset($defaults[$key])) {
        $defaults[$key] = $value;
      }
    }
  }
}

