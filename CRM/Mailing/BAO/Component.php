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
class CRM_Mailing_BAO_Component extends CRM_Mailing_DAO_Component {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Fetch object based on array of properties.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the flattened values.
   *
   * @return CRM_Core_BAO_LocationType.
   */
  public static function retrieve(&$params, &$defaults) {
    $component = new CRM_Mailing_DAO_Component();
    $component->copyValues($params);
    if ($component->find(TRUE)) {
      CRM_Core_DAO::storeValues($component, $defaults);
      return $component;
    }
    return NULL;
  }

  /**
   * Update the is_active flag in the db.
   *
   * @param int $id
   *   Id of the database record.
   * @param bool $is_active
   *   Value we want to set the is_active field.
   *
   * @return Object
   *   DAO object on success, null otherwise
   */
  public static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Mailing_DAO_Component', $id, 'is_active', $is_active);
  }

  /**
   * Create and Update mailing component.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $ids
   *   (deprecated) the array that holds all the db ids.
   *
   * @return CRM_Mailing_BAO_Component
   */
  public static function add(&$params, $ids = array()) {
    $id = CRM_Utils_Array::value('id', $params, CRM_Utils_Array::value('id', $ids));
    $component = new CRM_Mailing_DAO_Component();
    if ($id) {
      $component->id = $id;
      $component->find(TRUE);
    }

    $component->copyValues($params);
    if (empty($id) && empty($params['body_text'])) {
      $component->body_text = CRM_Utils_String::htmlToText(CRM_Utils_Array::value('body_html', $params));
    }

    if ($component->is_default) {
      if (!empty($id)) {
        $sql = 'UPDATE civicrm_mailing_component SET is_default = 0 WHERE component_type = %1 AND id <> %2';
        $sqlParams = array(
          1 => array($component->component_type, 'String'),
          2 => array($id, 'Positive'),
        );
      }
      else {
        $sql = 'UPDATE civicrm_mailing_component SET is_default = 0 WHERE component_type = %1';
        $sqlParams = array(
          1 => array($component->component_type, 'String'),
        );
      }
      CRM_Core_DAO::executeQuery($sql, $sqlParams);
    }

    $component->save();
    return $component;
  }

}
