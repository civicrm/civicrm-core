<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * This class stores logic for managing CiviCRM extensions.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Extension_Manager_Report extends CRM_Extension_Manager_Base {

  const REPORT_GROUP_NAME = 'report_template';

  /**
   * CRM_Extension_Manager_Report constructor.
   */
  public function __construct() {
    parent::__construct(TRUE);
  }

  public function getGroupId() {
    return CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup',
      self::REPORT_GROUP_NAME, 'id', 'name'
    );
  }

  /**
   * @param CRM_Extension_Info $info
   *
   * @throws CRM_Core_Exception
   */
  public function onPreInstall(CRM_Extension_Info $info): void {
    $customReports = $this->getCustomReportsByName();
    if (array_key_exists($info->key, $customReports)) {
      throw new CRM_Core_Exception(ts('This report is already registered.'));
    }

    if ($info->typeInfo['component'] === 'Contact') {
      $compId = 'null';
    }
    else {
      $comp = CRM_Core_Component::get($info->typeInfo['component']);
      $compId = $comp->componentID;
    }
    if (empty($compId)) {
      throw new CRM_Core_Exception(ts('Component for which you are trying to install the extension (%1) is currently disabled.', [1 => $info->typeInfo['component']]));
    }
    $weight = CRM_Utils_Weight::getDefaultWeight('CRM_Core_DAO_OptionValue',
      ['option_group_id' => $this->getGroupId()]
    );
    $params = [
      'label' => $info->label . ' (' . $info->key . ')',
      'value' => $info->typeInfo['reportUrl'],
      'name' => $info->key,
      'weight' => $weight,
      'description' => $info->label . ' (' . $info->key . ')',
      'component_id' => $compId,
      'option_group_id' => $this->getGroupId(),
      'is_active' => 1,
    ];

    CRM_Core_BAO_OptionValue::add($params);
  }

  /**
   * @param CRM_Extension_Info $info
   *
   * @return bool
   */
  public function onPreUninstall(CRM_Extension_Info $info) {
    $customReports = $this->getCustomReportsByName();
    $cr = $this->getCustomReportsById();
    $id = $cr[$customReports[$info->key]];
    $optionValue = CRM_Core_BAO_OptionValue::deleteRecord(['id' => $id]);

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
    return CRM_Core_OptionGroup::values(self::REPORT_GROUP_NAME, TRUE, FALSE, FALSE, NULL, 'name', FALSE);
  }

  /**
   * @return array
   */
  public function getCustomReportsById() {
    return CRM_Core_OptionGroup::values(self::REPORT_GROUP_NAME, FALSE, FALSE, FALSE, NULL, 'id', FALSE);
  }

}
