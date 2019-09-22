<?php

namespace api\v4\Traits;

trait OptionCleanupTrait {

  protected $optionGroupMaxId;
  protected $optionValueMaxId;

  public function setUp() {
    $this->optionGroupMaxId = \CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM civicrm_option_group');
    $this->optionValueMaxId = \CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM civicrm_option_value');
  }

  public function tearDown() {
    if ($this->optionValueMaxId) {
      \CRM_Core_DAO::executeQuery('DELETE FROM civicrm_option_value WHERE id > ' . $this->optionValueMaxId);
    }
    if ($this->optionGroupMaxId) {
      \CRM_Core_DAO::executeQuery('DELETE FROM civicrm_option_group WHERE id > ' . $this->optionGroupMaxId);
    }
  }

}
