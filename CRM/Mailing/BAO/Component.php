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
class CRM_Mailing_BAO_Component extends CRM_Mailing_DAO_Component {

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
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
   * @return object CRM_Core_BAO_LocaationType object
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    $component = new CRM_Mailing_DAO_Component();
    $component->copyValues($params);
    if ($component->find(TRUE)) {
      CRM_Core_DAO::storeValues($component, $defaults);
      return $component;
    }
    return NULL;
  }

  /**
   * update the is_active flag in the db
   *
   * @param int      $id        id of the database record
   * @param boolean  $is_active value we want to set the is_active field
   *
   * @return Object             DAO object on sucess, null otherwise
   * @static
   */
  static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Mailing_DAO_Component', $id, 'is_active', $is_active);
  }

  /**
   * Create and Update mailing component
   *
   * @param array $params (reference ) an assoc array of name/value pairs
   * @param array $ids (deprecated) the array that holds all the db ids
   *
   * @return object CRM_Mailing_BAO_Component object
   *
   * @access public
   * @static
   */
  static function add(&$params, $ids = array()) {
    $id = CRM_Utils_Array::value('id', $params, CRM_Utils_Array::value('id', $ids));
    $component = new CRM_Mailing_DAO_Component();
    $component->id = $id;
    $component->copyValues($params);
    if (empty($id) && empty($params['body_text'])) {
      $component->body_text = CRM_Utils_String::htmlToText(CRM_Utils_Array::value('body_html', $params));
    }

    if ($component->is_default && !empty($id)) {
      CRM_Core_DAO::executeQuery("UPDATE civicrm_mailing_component SET is_default = 0 WHERE component_type ='{$component->component_type}' AND id <> $id");
    }

    $component->save();
    return $component;
  }
}
