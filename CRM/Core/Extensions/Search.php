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
class CRM_Core_Extensions_Search {

  /**
   *
   */
  CONST CUSTOM_SEARCH_GROUP_NAME = 'custom_search';

  public function __construct($ext) {
    $this->ext = $ext;
    $this->groupId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup',
      self::CUSTOM_SEARCH_GROUP_NAME, 'id', 'name'
    );
    $this->customSearches = CRM_Core_OptionGroup::values(self::CUSTOM_SEARCH_GROUP_NAME, TRUE, FALSE, FALSE, NULL, 'name', FALSE);
  }


  public function install() {
    if (array_key_exists($this->ext->key, $this->customSearches)) {
      CRM_Core_Error::fatal('This custom search is already registered.');
    }

    $weight = CRM_Utils_Weight::getDefaultWeight('CRM_Core_DAO_OptionValue',
      array('option_group_id' => $this->groupId)
    );

    $params = array(
      'option_group_id' => $this->groupId,
      'weight' => $weight,
      'description' => $this->ext->label . ' (' . $this->ext->key . ')',
      'name' => $this->ext->key,
      'value' => max($this->customSearches) + 1,
      'label' => $this->ext->key,
      'is_active' => 1,
    );

    $ids = array();
    $optionValue = CRM_Core_BAO_OptionValue::add($params, $ids);

    return $optionValue ? TRUE : FALSE;
  }

  public function uninstall() {
    if (!array_key_exists($this->ext->key, $this->customSearches)) {
      CRM_Core_Error::fatal('This custom search is not registered.');
    }

    $cs          = CRM_Core_OptionGroup::values(self::CUSTOM_SEARCH_GROUP_NAME, FALSE, FALSE, FALSE, NULL, 'id', FALSE);
    $id          = $cs[$this->customSearches[$this->ext->key]];
    $optionValue = CRM_Core_BAO_OptionValue::del($id);

    return TRUE;
  }

  public function disable() {
    $cs          = CRM_Core_OptionGroup::values(self::CUSTOM_SEARCH_GROUP_NAME, FALSE, FALSE, FALSE, NULL, 'id', FALSE);
    $id          = $cs[$this->customSearches[$this->ext->key]];
    $optionValue = CRM_Core_BAO_OptionValue::setIsActive($id, 0);
  }

  public function enable() {
    $cs          = CRM_Core_OptionGroup::values(self::CUSTOM_SEARCH_GROUP_NAME, FALSE, FALSE, FALSE, NULL, 'id', FALSE);
    $id          = $cs[$this->customSearches[$this->ext->key]];
    $optionValue = CRM_Core_BAO_OptionValue::setIsActive($id, 1);
  }
}

