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
class CRM_Admin_Form_ParticipantStatusType extends CRM_Admin_Form {

  /**
   * @var bool
   */
  public $submitOnce = TRUE;

  /**
   * Used to make sure a malicious POST does not change is_reserved
   *
   * @var bool
   */
  protected $_isReserved = FALSE;

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'ParticipantStatusType';
  }

  /**
   * Build form.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    if ($this->_action & CRM_Core_Action::DELETE) {

      return;

    }

    $this->applyFilter('__ALL__', 'trim');

    $attributes = CRM_Core_DAO::getAttribute('CRM_Event_DAO_ParticipantStatusType');

    $this->add('text', 'name', ts('Name'), NULL, TRUE);

    $this->add('text', 'label', ts('Label'), $attributes['label'], TRUE);

    $this->addSelect('class', ['required' => TRUE]);

    $this->add('checkbox', 'is_active', ts('Active?'));
    $this->add('checkbox', 'is_counted', ts('Counted?'));

    $this->add('number', 'weight', ts('Order'), $attributes['weight'], TRUE);

    $this->addSelect('visibility_id', ['label' => ts('Visibility'), 'required' => TRUE]);

    $this->assign('id', $this->_id);
  }

  /**
   * Set default values.
   *
   * @return array
   */
  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();
    if (empty($defaults['weight'])) {
      $defaults['weight'] = CRM_Utils_Weight::getDefaultWeight('CRM_Event_DAO_ParticipantStatusType');
    }
    $this->_isReserved = $defaults['is_reserved'] ?? FALSE;
    if ($this->_isReserved) {
      $this->freeze(['name', 'class', 'is_active']);
    }
    return $defaults;
  }

  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      if (CRM_Event_BAO_ParticipantStatusType::deleteParticipantStatusType($this->_id)) {
        CRM_Core_Session::setStatus(ts('Selected participant status has been deleted.'), ts('Record Deleted'), 'success');
      }
      else {
        CRM_Core_Session::setStatus(ts('Selected participant status has <strong>NOT</strong> been deleted; there are still participants with this status.'), ts('Sorry'), 'error');
      }
      return;
    }

    $formValues = $this->controller->exportValues($this->_name);

    $params = [
      'name' => $formValues['name'] ?? NULL,
      'label' => $formValues['label'] ?? NULL,
      'class' => $formValues['class'] ?? NULL,
      'is_active' => $formValues['is_active'] ?? FALSE,
      'is_counted' => $formValues['is_counted'] ?? FALSE,
      'weight' => $formValues['weight'] ?? NULL,
      'visibility_id' => $formValues['visibility_id'] ?? NULL,
    ];

    // make sure a malicious POST does not change these on reserved statuses
    if ($this->_isReserved) {
      unset($params['name'], $params['class'], $params['is_active']);
    }

    if ($this->_action & CRM_Core_Action::UPDATE) {

      $params['id'] = $this->_id;

    }

    if ($this->_id) {
      $oldWeight = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_ParticipantStatusType', $this->_id, 'weight', 'id');
    }
    else {
      $oldWeight = NULL;
    }
    $params['weight'] = CRM_Utils_Weight::updateOtherWeights('CRM_Event_DAO_ParticipantStatusType', $oldWeight, $params['weight']);

    $participantStatus = CRM_Event_BAO_ParticipantStatusType::writeRecord($params);

    if ($participantStatus->id) {
      if ($this->_action & CRM_Core_Action::UPDATE) {
        CRM_Core_Session::setStatus(ts('The Participant Status has been updated.'), ts('Saved'), 'success');
      }
      else {
        CRM_Core_Session::setStatus(ts('The new Participant Status has been saved.'), ts('Saved'), 'success');
      }
    }
    else {
      CRM_Core_Session::setStatus(ts('The changes have not been saved.'), ts('Saved'), 'success');
    }
  }

}
