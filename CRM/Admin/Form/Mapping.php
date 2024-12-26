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

/**
 * This class generates form components for Mapping.
 */
class CRM_Admin_Form_Mapping extends CRM_Admin_Form {

  /**
   * @return string
   */
  public function getDefaultEntity(): string {
    return 'Mapping';
  }

  /**
   * @var bool
   */
  public $submitOnce = TRUE;

  /**
   * Build the form object.
   */
  public function preProcess() {
    parent::preProcess();
    $mapping = new CRM_Core_DAO_Mapping();
    $mapping->id = $this->_id;
    $mapping->find(TRUE);
    $this->assign('mappingName', $mapping->name);
  }

  public function buildQuickForm() {
    parent::buildQuickForm();

    if ($this->_action == CRM_Core_Action::DELETE) {
      return;
    }
    else {
      $this->applyFilter('__ALL__', 'trim');

      $this->add('text', 'name', ts('Name'),
        CRM_Core_DAO::getAttribute('CRM_Core_DAO_Mapping', 'name'), TRUE
      );
      $this->addRule('name', ts('Name already exists in Database.'), 'objectExists', [
        'CRM_Core_DAO_Mapping',
        $this->_id,
      ]);

      $this->addElement('text', 'description', ts('Description'),
        CRM_Core_DAO::getAttribute('CRM_Core_DAO_Mapping', 'description')
      );

      $mappingType = $this->addElement('select', 'mapping_type_id', ts('Mapping Type'), CRM_Core_DAO_Mapping::buildOptions('mapping_type_id'));

      if ($this->_action == CRM_Core_Action::UPDATE) {
        $mappingType->freeze();
      }
    }
  }

  /**
   * @return array
   */
  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();
    return $defaults;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    // store the submitted values in an array
    $params = $this->exportValues();

    if ($this->_action == CRM_Core_Action::DELETE) {
      if ($this->_id) {
        CRM_Core_BAO_Mapping::deleteRecord(['id' => $this->_id]);
        CRM_Core_Session::setStatus(ts('Selected mapping has been deleted successfully.'), ts('Deleted'), 'success');
      }
    }
    else {
      if ($this->_id) {
        $params['id'] = $this->_id;
      }

      CRM_Core_BAO_Mapping::writeRecord($params);
    }
  }

}
