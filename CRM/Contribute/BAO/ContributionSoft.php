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
class CRM_Contribute_BAO_ContributionSoft extends CRM_Contribute_DAO_ContributionSoft {

  /* 
   * construct method
  */
  function __construct() {
    parent::__construct();
  }

  /**
   * funtion to add contribution soft credit record
   *
   * @param array  $params (reference ) an assoc array of name/value pairs
   *
   * @return object soft contribution of object that is added
   * @access public
   *
   */
  public static function add(&$params) {
    $contributionSoft = new CRM_Contribute_DAO_ContributionSoft();
    $contributionSoft->copyValues($params);
    return $contributionSoft->save();
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * contact_id. We'll tweak this function to be more full featured over a period
   * of time. This is the inverse function of create. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
   * @return object CRM_Contribute_BAO_ContributionSoft object
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    $contributionSoft = new CRM_Contribute_DAO_ContributionSoft();
    $contributionSoft->copyValues($params);
    if ($contributionSoft->find(TRUE)) {
      CRM_Core_DAO::storeValues($contributionSoft, $defaults);
      return $contributionSoft;
    }
    return NULL;
  }

  /**
   * Function to delete soft credits 
   *
   * @param int $contributionTypeId
   * @static
   */
  static function del($contributionID) {
    //delete from contribution soft table
    $contributionSoft = new CRM_Contribute_DAO_ContributionSoft();
    $contributionSoft->contribution_id = $contributionID;
    $contributionSoft->delete();
  }
}

