<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */
class CRM_Extension_Manager_Report extends CRM_Extension_Manager_Base {

  const REPORT_GROUP_NAME = 'report_template';

  /**
   * CRM_Extension_Manager_Report constructor.
   */
  public function __construct() {
    parent::__construct(TRUE);
    $this->groupId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup',
      self::REPORT_GROUP_NAME, 'id', 'name'
    );
  }

  /**
   * @param CRM_Extension_Info $info
   *
   * @throws Exception
   */
  public function onPreInstall(CRM_Extension_Info $info) {
    $customReports = $this->getCustomReportsByName();
    if (array_key_exists($info->key, $customReports)) {
      CRM_Core_Error::fatal('This report is already registered.');
    }

    if ($info->typeInfo['component'] === 'Contact') {
      $compId = 'null';
    }
    else {
      $comp = CRM_Core_Component::get($info->typeInfo['component']);
      $compId = $comp->componentID;
    }
    if (empty($compId)) {
      CRM_Core_Error::fatal("Component for which you're trying to install the extension (" . $info->typeInfo['component'] . ") is currently disabled.");
    }
    $weight = CRM_Utils_Weight::getDefaultWeight('CRM_Core_DAO_OptionValue',
      array('option_group_id' => $this->groupId)
    );
    $ids = array();
    $params = array(
      'label' => $info->label . ' (' . $info->key . ')',
      'value' => $info->typeInfo['reportUrl'],
      'name' => $info->key,
      'weight' => $weight,
      'description' => $info->label . ' (' . $info->key . ')',
      'component_id' => $compId,
      'option_group_id' => $this->groupId,
      'is_active' => 1,
    );

    $optionValue = CRM_Core_BAO_OptionValue::add($params, $ids);
  }

  /**
   * @param CRM_Extension_Info $info
   *
   * @return bool
   */
  public function onPreUninstall(CRM_Extension_Info $info) {

    //        if( !array_key_exists( $info->key, $this->customReports ) ) {
    //            CRM_Core_Error::fatal( 'This report is not registered.' );
    //        }

    $customReports = $this->getCustomReportsByName();
    $cr = $this->getCustomReportsById();
    $id = $cr[$customReports[$info->key]];
    $optionValue = CRM_Core_BAO_OptionValue::del($id);

    return $optionValue ? TRUE : FALSE;
  }

  /**
   * @param CRM_Extension_Info $info
   */
  public function onPreDisable(CRM_Extension_Info $info) {
    $customReports = $this->getCustomReportsByName();
    $cr = $this->getCustomReportsById();
    $id = $cr[$customReports[$info->key]];
    $optionValue = CRM_Core_BAO_OptionValue::setIsActive($id, 0);
  }

  /**
   * @param CRM_Extension_Info $info
   */
  public function onPreEnable(CRM_Extension_Info $info) {
    $customReports = $this->getCustomReportsByName();
    $cr = $this->getCustomReportsById();
    $id = $cr[$customReports[$info->key]];
    $optionValue = CRM_Core_BAO_OptionValue::setIsActive($id, 1);
  }

  /**
   * @return array
   */
  public function getCustomReportsByName() {
    return CRM_Core_OptionGroup::values(self::REPORT_GROUP_NAME, TRUE, FALSE, FALSE, NULL, 'name', FALSE, TRUE);
  }

  /**
   * @return array
   */
  public function getCustomReportsById() {
    return CRM_Core_OptionGroup::values(self::REPORT_GROUP_NAME, FALSE, FALSE, FALSE, NULL, 'id', FALSE, TRUE);
  }

}
