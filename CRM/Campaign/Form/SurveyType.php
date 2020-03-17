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
 * This class generates form components for Option Group.
 */
class CRM_Campaign_Form_SurveyType extends CRM_Admin_Form {
  protected $_gid;

  /**
   * The option group name
   *
   * @var string
   */
  protected $_gName;

  /**
   * Action
   *
   * @var int
   */
  public $_action;

  /**
   * Set variables up before form is built.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this);

    if ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::DELETE)) {
      $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
      $this->assign('id', $this->_id);
    }
    $this->assign('action', $this->_action);
    $this->assign('id', $this->_id);

    $this->_BAOName = 'CRM_Core_BAO_OptionValue';
    $this->_gName = 'activity_type';
    $this->_gid = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', $this->_gName, 'id', 'name');

    $session = CRM_Core_Session::singleton();
    $url = CRM_Utils_System::url('civicrm/admin/campaign/surveyType', 'reset=1');
    $session->pushUserContext($url);

    if ($this->_id && in_array($this->_gName, CRM_Core_OptionGroup::$_domainIDGroups)) {
      $domainID = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $this->_id, 'domain_id', 'id');
      if (CRM_Core_Config::domainID() != $domainID) {
        CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
      }
    }
  }

  /**
   * Set default values for the form.
   * the default values are retrieved from the database.
   *
   * @return array
   *   array of default values
   */
  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();

    if (!isset($defaults['weight']) || !$defaults['weight']) {
      $fieldValues = ['option_group_id' => $this->_gid];
      $defaults['weight'] = CRM_Utils_Weight::getDefaultWeight('CRM_Core_DAO_OptionValue', $fieldValues);
    }

    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $this->applyFilter('__ALL__', 'trim');
    $this->add('text', 'label', ts('Title'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'label'), TRUE);

    $this->add('wysiwyg', 'description',
      ts('Description'),
      CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'description')
    );

    $this->add('checkbox', 'is_active', ts('Enabled?'));

    if ($this->_action == CRM_Core_Action::UPDATE &&
      CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', $this->_id, 'is_reserved')
    ) {
      $this->freeze(['label', 'is_active']);
    }
    $this->add('number', 'weight', ts('Order'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'weight'), TRUE);

    $this->assign('id', $this->_id);
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {

    if ($this->_action & CRM_Core_Action::DELETE) {
      $fieldValues = ['option_group_id' => $this->_gid];
      $wt = CRM_Utils_Weight::delWeight('CRM_Core_DAO_OptionValue', $this->_id, $fieldValues);

      if (CRM_Core_BAO_OptionValue::del($this->_id)) {
        CRM_Core_Session::setStatus(ts('Selected Survey type has been deleted.'), ts('Record Deleted'), 'success');
      }
    }
    else {
      $params = $ids = [];
      $params = $this->exportValues();

      // set db value of filter in params if filter is non editable
      if ($this->_id && !array_key_exists('filter', $params)) {
        $params['filter'] = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $this->_id, 'filter', 'id');
      }

      $params['component_id'] = CRM_Core_Component::getComponentID('CiviCampaign');
      $optionValue = CRM_Core_OptionValue::addOptionValue($params, $this->_gName, $this->_action, $this->_id);

      CRM_Core_Session::setStatus(ts('The Survey type \'%1\' has been saved.', [1 => $optionValue->label]), ts('Saved'), 'success');
    }
  }

}
