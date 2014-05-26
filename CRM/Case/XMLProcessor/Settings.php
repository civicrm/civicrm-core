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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class CRM_Case_XMLProcessor_Settings extends CRM_Case_XMLProcessor {

  private $_settings = array();

  // Input: The base filename without the .xml extension
  // Output: An array of settings.
  /**
   * @param string $filename
   *
   * @return array
   */
  function run($filename = 'settings') {
    $xml = $this->retrieve($filename);

    // For now it's not an error. In the future it might be a required file.
    if ($xml !== FALSE) {
      // There's only one setting right now, and only one value.
      if ($xml->group[0]) {
        if ($xml->group[0]->attributes()) {
          $groupName = (string) $xml->group[0]->attributes()->name;
          if ($groupName) {
            $this->_settings['groupname'] = $groupName;
          }
        }
      }
    }
    return $this->_settings;
  }
}

