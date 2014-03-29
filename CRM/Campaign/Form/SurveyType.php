<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * This class generates form components for Option Group
 *
 */
class CRM_Campaign_Form_SurveyType extends CRM_Admin_Form {
  protected $_gid;

  /**
   * The option group name
   *
   * @var string
   * @static
   */
  protected $_gName;

  /**
   * id
   *
   * @var int
   */
  protected $_id;

  /**
   * action
   *
   * @var int
   */
  protected $_action;

  /**
   * Function to set variables up before form is built
   *
   * @param null
   *
   * @return void
   * @access public
   */ function preProcess() {
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this);

    if ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::DELETE)) {
      $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
      $this->assign('id', $this->_id);
    }
    $this->assign('action', $this->_action);
    $this->assign('id', $this->_id);

    $this->_BAOName = 'CRM_Core_BAO_OptionValue';
    $this->_gName   = 'activity_type';
    $this->_gid     = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', $this->_gName, 'id', 'name');

    $session = CRM_Core_Session::singleton();
    $url = CRM_Utils_System::url('civicrm/admin/campaign/surveyType', 'reset=1');
    $session->pushUserContext($url);

    if ($this->_id && in_array($this->_gName, CRM_Core_OptionGroup::$_domainIDGroups)) {
      $domainID = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $this->_id, 'domain_id', 'id');
      if (CRM_Core_Config::domainID() != $domainID) {
        CRM_Core_Error::fatal(ts('You do not have permission to access this page'));
      }
    }
  }

  /**
   * This function sets the default values for the form.
   * the default values are retrieved from the database.
   *
   * @param null
   *
   * @return array    array of default values
   * @access public
   */
  function setDefaultValues() {
    $defaults = parent::setDefaultValues();

    if (!isset($defaults['weight']) || !$defaults['weight']) {
      $fieldValues = array('option_group_id' => $this->_gid);
      $defaults['weight'] = CRM_Utils_Weight::getDefaultWeight('CRM_Core_DAO_OptionValue', $fieldValues);
    }

    return $defaults;
  }

  /**
   * Function to build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $this->applyFilter('__ALL__', 'trim');
    $this->add('text', 'label', ts('Title'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'label'), TRUE);

    $this->addWysiwyg('description',
      ts('Description'),
      CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'description')
    );


    $this->add('checkbox', 'is_active', ts('Enabled?'));

    if ($this->_action == CRM_Core_Action::UPDATE &&
      CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', $this->_id, 'is_reserved')
    ) {
      $this->freeze(array('label', 'is_active'));
    }
    $this->add('text', 'weight', ts('Weight'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'weight'), TRUE);

    $this->assign('id', $this->_id);
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return void
   */
  public function postProcess() {

    if ($this->_action & CRM_Core_Action::DELETE) {
      $fieldValues = array('option_group_id' => $this->_gid);
      $wt = CRM_Utils_Weight::delWeight('CRM_Core_DAO_OptionValue', $this->_id, $fieldValues);

      if (CRM_Core_BAO_OptionValue::del($this->_id)) {
        CRM_Core_Session::setStatus(ts('Selected Survey type has been deleted.'), ts('Record Deleted'), 'success');
      }
    }
    else {
      $params = $ids = array();
      $params = $this->exportValues();

      // set db value of filter in params if filter is non editable
      if ($this->_id && !array_key_exists('filter', $params)) {
        $params['filter'] = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $this->_id, 'filter', 'id');
      }

      $groupParams = array('name' => ($this->_gName));
      $params['component_id'] = CRM_Core_Component::getComponentID('CiviCampaign');
      $optionValue = CRM_Core_OptionValue::addOptionValue($params, $groupParams, $this->_action, $this->_id);

      CRM_Core_Session::setStatus(ts('The Survey type \'%1\' has been saved.', array(1 => $optionValue->label)), ts('Saved'), 'success');
    }
  }
}

