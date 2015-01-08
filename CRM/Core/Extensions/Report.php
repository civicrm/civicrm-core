<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.2                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2012                                |
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
 * This class stores logic for managing CiviCRM extensions.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2012
 * $Id$
 *
 */
class CRM_Core_Extensions_Report {

  /**
   *
   */
  CONST REPORT_GROUP_NAME = 'report_template';

  public function __construct($ext) {
    $this->ext = $ext;
    $this->groupId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup',
      self::REPORT_GROUP_NAME, 'id', 'name'
    );
    $this->customReports = CRM_Core_OptionGroup::values(self::REPORT_GROUP_NAME, TRUE, FALSE, FALSE, NULL, 'name', FALSE);
  }

  public function install() {
    if (array_key_exists($this->ext->key, $this->customReports)) {
      CRM_Core_Error::fatal('This report is already registered.');
    }

    if ($this->ext->typeInfo['component'] === 'Contact') {
      $compId = 'null';
    }
    else {
      $comp = CRM_Core_Component::get($this->ext->typeInfo['component']);
      $compId = $comp->componentID;
    }
    if (empty($compId)) {
      CRM_Core_Error::fatal("Component for which you're trying to install the extension (" . $this->ext->typeInfo['component'] . ") is currently disabled.");
    }
    $weight = CRM_Utils_Weight::getDefaultWeight('CRM_Core_DAO_OptionValue',
      array('option_group_id' => $this->groupId)
    );
    $ids = array();
    $params = array(
      'label' => $this->ext->label . ' (' . $this->ext->key . ')',
      'value' => $this->ext->typeInfo['reportUrl'],
      'name' => $this->ext->key,
      'weight' => $weight,
      'description' => $this->ext->label . ' (' . $this->ext->key . ')',
      'component_id' => $compId,
      'option_group_id' => $this->groupId,
      'is_active' => 1,
    );

    $optionValue = CRM_Core_BAO_OptionValue::add($params, $ids);
  }

  public function uninstall() {

    //        if( !array_key_exists( $this->ext->key, $this->customReports ) ) {
    //            CRM_Core_Error::fatal( 'This report is not registered.' );
    //        }

    $cr          = CRM_Core_OptionGroup::values(self::REPORT_GROUP_NAME, FALSE, FALSE, FALSE, NULL, 'id', FALSE);
    $id          = $cr[$this->customReports[$this->ext->key]];
    $optionValue = CRM_Core_BAO_OptionValue::del($id);

    return $optionValue ? TRUE : FALSE;
  }

  public function disable() {
    $cr          = CRM_Core_OptionGroup::values(self::REPORT_GROUP_NAME, FALSE, FALSE, FALSE, NULL, 'id', FALSE);
    $id          = $cr[$this->customReports[$this->ext->key]];
    $optionValue = CRM_Core_BAO_OptionValue::setIsActive($id, 0);
  }

  public function enable() {
    $cr          = CRM_Core_OptionGroup::values(self::REPORT_GROUP_NAME, FALSE, FALSE, FALSE, NULL, 'id', FALSE);
    $id          = $cr[$this->customReports[$this->ext->key]];
    $optionValue = CRM_Core_BAO_OptionValue::setIsActive($id, 1);
  }
}

