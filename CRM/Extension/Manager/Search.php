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
class CRM_Extension_Manager_Search extends CRM_Extension_Manager_Base {

  const CUSTOM_SEARCH_GROUP_NAME = 'custom_search';

  /**
   * CRM_Extension_Manager_Search constructor.
   */
  public function __construct() {
    parent::__construct(TRUE);
  }

  public function getGroupId() {
    return CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup',
      self::CUSTOM_SEARCH_GROUP_NAME, 'id', 'name'
    );
  }

  /**
   * @param CRM_Extension_Info $info
   *
   * @return bool
   * @throws CRM_Core_Exception
   */
  public function onPreInstall(CRM_Extension_Info $info) {
    $customSearchesByName = $this->getCustomSearchesByName();
    if (array_key_exists($info->key, $customSearchesByName)) {
      throw new CRM_Core_Exception(ts('This custom search is already registered.'));
    }

    $weight = CRM_Utils_Weight::getDefaultWeight('CRM_Core_DAO_OptionValue',
      ['option_group_id' => $this->getGroupId()]
    );

    $params = [
      'option_group_id' => $this->getGroupId(),
      'weight' => $weight,
      'description' => $info->label . ' (' . $info->key . ')',
      'name' => $info->key,
      'value' => max($customSearchesByName) + 1,
      'label' => $info->key,
      'is_active' => 1,
    ];

    $optionValue = CRM_Core_BAO_OptionValue::add($params);

    return $optionValue ? TRUE : FALSE;
  }

  /**
   * @param CRM_Extension_Info $info
   *
   * @return bool
   * @throws Exception
   */
  public function onPreUninstall(CRM_Extension_Info $info) {
    $customSearchesByName = $this->getCustomSearchesByName();
    if (!array_key_exists($info->key, $customSearchesByName)) {
      throw new CRM_Core_Exception('This custom search is not registered.');
    }

    $cs = $this->getCustomSearchesById();
    $id = $cs[$customSearchesByName[$info->key]];
    CRM_Core_BAO_OptionValue::deleteRecord(['id' => $id]);

    return TRUE;
  }

  /**
   * @param CRM_Extension_Info $info
   */
  public function onPreDisable(CRM_Extension_Info $info) {
    $customSearchesByName = $this->getCustomSearchesByName();
    $cs = $this->getCustomSearchesById();
    $id = $cs[$customSearchesByName[$info->key]];
    $optionValue = CRM_Core_BAO_OptionValue::setIsActive($id, 0);
  }

  /**
   * @param CRM_Extension_Info $info
   */
  public function onPreEnable(CRM_Extension_Info $info) {
    $customSearchesByName = $this->getCustomSearchesByName();
    $cs = $this->getCustomSearchesById();
    $id = $cs[$customSearchesByName[$info->key]];
    $optionValue = CRM_Core_BAO_OptionValue::setIsActive($id, 1);
  }

  /**
   * @return array
   */
  protected function getCustomSearchesByName(): array {
    return CRM_Core_OptionGroup::values(self::CUSTOM_SEARCH_GROUP_NAME, TRUE, FALSE, FALSE, NULL, 'name', FALSE);
  }

  /**
   * @return array
   */
  protected function getCustomSearchesById(): array {
    return CRM_Core_OptionGroup::values(self::CUSTOM_SEARCH_GROUP_NAME, FALSE, FALSE, FALSE, NULL, 'id', FALSE);
  }

}
