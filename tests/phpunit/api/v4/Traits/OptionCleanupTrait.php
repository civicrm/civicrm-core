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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

namespace api\v4\Traits;

trait OptionCleanupTrait {

  /**
   * @var int
   */
  protected $optionGroupMaxId;

  /**
   * @var int
   */
  protected $optionValueMaxId;

  public function setUp(): void {
    $this->optionGroupMaxId = \CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM civicrm_option_group');
    $this->optionValueMaxId = \CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM civicrm_option_value');
  }

  public function tearDown(): void {
    if ($this->optionValueMaxId) {
      \CRM_Core_DAO::executeQuery('DELETE FROM civicrm_option_value WHERE id > ' . $this->optionValueMaxId);
    }
    if ($this->optionGroupMaxId) {
      \CRM_Core_DAO::executeQuery('DELETE FROM civicrm_option_group WHERE id > ' . $this->optionGroupMaxId);
    }
  }

}
