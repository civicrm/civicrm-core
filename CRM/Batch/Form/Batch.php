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

use Civi\Api4\Batch;

/**
 * This class generates form components for batch entry.
 */
class CRM_Batch_Form_Batch extends CRM_Admin_Form {

  protected $submittableMoneyFields = ['total'];

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'Batch';
  }

  /**
   * PreProcess function.
   */
  public function preProcess() {
    parent::preProcess();
    // Set the user context.
    $session = CRM_Core_Session::singleton();
    $session->replaceUserContext(CRM_Utils_System::url('civicrm/batch', "reset=1"));
  }

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $this->applyFilter('__ALL__', 'trim');
    $attributes = CRM_Core_DAO::getAttribute('CRM_Batch_DAO_Batch');
    $this->add('text', 'title', ts('Batch Name'), $attributes['name'], TRUE);

    $batchTypes = CRM_Batch_BAO_Batch::buildOptions('type_id');

    $type = $this->add('select', 'type_id', ts('Type'), $batchTypes);

    if ($this->_action & CRM_Core_Action::UPDATE) {
      $type->freeze();
    }

    $this->add('textarea', 'description', ts('Description'), $attributes['description']);
    $this->add('text', 'item_count', ts('Number of Items'), $attributes['item_count'], TRUE);
    $this->add('text', 'total', ts('Total Amount'), $attributes['total'], TRUE);
  }

  /**
   * Set default values for the form.
   */
  public function setDefaultValues() {
    $defaults = [];

    if ($this->_action & CRM_Core_Action::ADD) {
      // Set batch name default.
      $defaults['title'] = CRM_Batch_BAO_Batch::generateBatchName();
    }
    else {
      $defaults = $this->_values;
    }
    return $defaults;
  }

  /**
   * Process the form submission.
   *
   * @throws \CRM_Core_Exception
   */
  public function postProcess(): void {
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Core_Session::setStatus('', ts('Batch Deleted'), 'success');
      CRM_Batch_BAO_Batch::deleteBatch($this->_id);
      return;
    }

    $batchID = Batch::save(FALSE)->setRecords([
      [
        // Always create with data entry status.
        'status_id:name' => 'Data Entry',
        'id' => $this->_id,
        'title' => $this->getSubmittedValue('title'),
        'description' => $this->getSubmittedValue('description'),
        'type_id' => $this->getSubmittedValue('type_id'),
        'total' => $this->getSubmittedValue('total'),
        'item_count' => $this->getSubmittedValue('item_count'),
      ],
    ])->execute()->first()['id'];

    // Redirect to batch entry page.
    if ($this->_action & CRM_Core_Action::ADD) {
      CRM_Core_Session::singleton()->replaceUserContext(CRM_Utils_System::url('civicrm/batch/entry', "id={$batchID}&reset=1&action=add"));
    }
    else {
      CRM_Core_Session::singleton()->replaceUserContext(CRM_Utils_System::url('civicrm/batch/entry', "id={$batchID}&reset=1"));
    }
  }

}
