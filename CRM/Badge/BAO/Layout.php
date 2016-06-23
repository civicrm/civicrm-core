<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 */
class CRM_Badge_BAO_Layout extends CRM_Core_DAO_PrintLabel {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Retrieve DB object based on input parameters.
   *
   * It also stores all the retrieved values in the default array.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the flattened values.
   *
   * @return CRM_Core_DAO_PrintLabel|null
   *   object on success, null otherwise
   */
  public static function retrieve(&$params, &$defaults) {
    $printLabel = new CRM_Core_DAO_PrintLabel();
    $printLabel->copyValues($params);
    if ($printLabel->find(TRUE)) {
      CRM_Core_DAO::storeValues($printLabel, $defaults);
      return $printLabel;
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
   *
   */
  public static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Core_DAO_PrintLabel', $id, 'is_active', $is_active);
  }

  /**
   * Add a name label.
   *
   * @param array $params
   *   Reference array contains the values submitted by the form.
   *
   *
   * @return object
   */
  public static function create(&$params) {
    $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);
    $params['is_default'] = CRM_Utils_Array::value('is_default', $params, FALSE);
    $params['is_reserved'] = CRM_Utils_Array::value('is_reserved', $params, FALSE);

    $params['label_type_id'] = CRM_Core_OptionGroup::getValue('label_type', 'Event Badge', 'name');

    // check if new layout is create, if so set the created_id (if not set)
    if (empty($params['id'])) {
      if (empty($params['created_id'])) {
        $session = CRM_Core_Session::singleton();
        $params['created_id'] = $session->get('userID');
      }
    }

    if (!isset($params['id']) && !isset($params['name'])) {
      $params['name'] = CRM_Utils_String::munge($params['title'], '_', 64);
    }

    // action is taken depending upon the mode
    $printLabel = new CRM_Core_DAO_PrintLabel();
    $printLabel->copyValues($params);

    if ($params['is_default']) {
      $query = "UPDATE civicrm_print_label SET is_default = 0";
      CRM_Core_DAO::executeQuery($query);
    }

    $printLabel->save();
    return $printLabel;
  }

  /**
   * Delete name labels.
   *
   * @param int $printLabelId
   *   ID of the name label to be deleted.
   *
   */
  public static function del($printLabelId) {
    $printLabel = new CRM_Core_DAO_PrintLabel();
    $printLabel->id = $printLabelId;
    $printLabel->delete();
  }

  /**
   *  get the list of print labels.
   *
   * @return array
   *   list of labels
   */
  public static function getList() {
    $printLabel = new CRM_Core_DAO_PrintLabel();
    $printLabel->find();

    $labels = array();
    while ($printLabel->fetch()) {
      $labels[$printLabel->id] = $printLabel->title;
    }
    return $labels;
  }

  /**
   * Build layout structure.
   *
   * @param array $params
   *   Associated array of submitted values.
   *
   * @return array
   *   array formatted array
   */
  public static function buildLayout(&$params) {
    $layoutParams = array('id' => $params['badge_id']);
    CRM_Badge_BAO_Layout::retrieve($layoutParams, $layoutInfo);

    $formatProperties = CRM_Core_OptionGroup::getValue('name_badge', $layoutInfo['label_format_name'], 'name');
    $layoutInfo['format'] = json_decode($formatProperties, TRUE);
    $layoutInfo['data'] = CRM_Badge_BAO_Layout::getDecodedData($layoutInfo['data']);
    return $layoutInfo;
  }

  /**
   * Decode encoded data and return as an array.
   *
   * @param json $jsonData
   *   Json object.
   *
   * @return array
   *   associated array of decoded elements
   */
  static public function getDecodedData($jsonData) {
    return json_decode($jsonData, TRUE);
  }

}
