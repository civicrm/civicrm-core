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
 * This class stores logic for managing CiviCRM extensions.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Extension_Manager_Search extends CRM_Extension_Manager_Base {

  /**
   *
   */
  CONST CUSTOM_SEARCH_GROUP_NAME = 'custom_search';

  public function __construct() {
    parent::__construct(TRUE);
    $this->groupId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup',
      self::CUSTOM_SEARCH_GROUP_NAME, 'id', 'name'
    );
  }

  public function onPreInstall(CRM_Extension_Info $info) {
    $customSearchesByName = $this->getCustomSearchesByName();
    if (array_key_exists($info->key, $customSearchesByName)) {
      CRM_Core_Error::fatal('This custom search is already registered.');
    }

    $weight = CRM_Utils_Weight::getDefaultWeight('CRM_Core_DAO_OptionValue',
      array('option_group_id' => $this->groupId)
    );

    $params = array(
      'option_group_id' => $this->groupId,
      'weight' => $weight,
      'description' => $info->label . ' (' . $info->key . ')',
      'name' => $info->key,
      'value' => max($customSearchesByName) + 1,
      'label' => $info->key,
      'is_active' => 1,
    );

    $ids = array();
    $optionValue = CRM_Core_BAO_OptionValue::add($params, $ids);

    return $optionValue ? TRUE : FALSE;
  }

  public function onPreUninstall(CRM_Extension_Info $info) {
    $customSearchesByName = $this->getCustomSearchesByName();
    if (!array_key_exists($info->key, $customSearchesByName)) {
      CRM_Core_Error::fatal('This custom search is not registered.');
    }

    $cs          = $this->getCustomSearchesById();
    $id          = $cs[$customSearchesByName[$info->key]];
    $optionValue = CRM_Core_BAO_OptionValue::del($id);

    return TRUE;
  }

  public function onPreDisable(CRM_Extension_Info $info) {
    $customSearchesByName = $this->getCustomSearchesByName();
    $cs          = $this->getCustomSearchesById();
    $id          = $cs[$customSearchesByName[$info->key]];
    $optionValue = CRM_Core_BAO_OptionValue::setIsActive($id, 0);
  }

  public function onPreEnable(CRM_Extension_Info $info) {
    $customSearchesByName = $this->getCustomSearchesByName();
    $cs          = $this->getCustomSearchesById();
    $id          = $cs[$customSearchesByName[$info->key]];
    $optionValue = CRM_Core_BAO_OptionValue::setIsActive($id, 1);
  }

  protected function getCustomSearchesByName() {
    return CRM_Core_OptionGroup::values(self::CUSTOM_SEARCH_GROUP_NAME, TRUE, FALSE, FALSE, NULL, 'name', FALSE, TRUE);
  }

  protected function getCustomSearchesById() {
    return CRM_Core_OptionGroup::values(self::CUSTOM_SEARCH_GROUP_NAME, FALSE, FALSE, FALSE, NULL, 'id', FALSE, TRUE);
  }
}
