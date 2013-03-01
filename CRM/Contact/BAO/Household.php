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
class CRM_Contact_BAO_Household extends CRM_Contact_DAO_Contact {

  /**
   * This is a contructor of the class.
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * function to update the household with primary contact id
   *
   * @param integer $primaryContactId     null if deleting primary contact
   * @param integer $contactId            contact id
   *
   * @return Object     DAO object on success
   * @access public
   * @static
   */
  static function updatePrimaryContact($primaryContactId, $contactId) {
    $queryString = "UPDATE civicrm_contact
                           SET primary_contact_id = ";

    $params = array();
    if ($primaryContactId) {
      $queryString .= '%1';
      $params[1] = array($primaryContactId, 'Integer');
    }
    else {
      $queryString .= "null";
    }

    $queryString .= " WHERE id = %2";
    $params[2] = array($contactId, 'Integer');

    return CRM_Core_DAO::executeQuery($queryString, $params);
  }
}

