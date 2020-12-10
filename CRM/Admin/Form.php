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
 * Base class for admin forms.
 */
class CRM_Admin_Form extends CRM_Core_Form {

  /**
   * The id of the object being edited / created
   *
   * @var int
   */
  public $_id;

  /**
   * The default values for form fields.
   *
   * @var int
   */
  protected $_values;

  /**
   * The name of the BAO object for this form.
   *
   * @var string
   */
  protected $_BAOName;

  /**
   * Explicitly declare the form context.
   */
  public function getDefaultContext() {
    return 'create';
  }

  /**
   * Basic setup.
   */
  public function preProcess() {
    Civi::resources()->addStyleFile('civicrm', 'css/admin.css');
    Civi::resources()->addScriptFile('civicrm', 'js/jquery/jquery.crmIconPicker.js');

    $this->_id = $this->get('id');
    $this->_BAOName = $this->get('BAOName');
    $this->_values = [];
    if (isset($this->_id)) {
      $params = ['id' => $this->_id];
      // this is needed if the form is outside the CRM name space
      $baoName = $this->_BAOName;
      $baoName::retrieve($params, $this->_values);
    }
  }

  /**
   * Set default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   *
   * @return array
   */
  public function setDefaultValues() {
    // Fetch defaults from the db
    if (!empty($this->_id) && empty($this->_values) && CRM_Utils_Rule::positiveInteger($this->_id)) {
      $this->_values = [];
      $params = ['id' => $this->_id];
      $baoName = $this->_BAOName;
      $baoName::retrieve($params, $this->_values);
    }
    $defaults = $this->_values;

    // Allow defaults to be set from the url
    if (empty($this->_id) && $this->_action & CRM_Core_Action::ADD) {
      foreach ($_GET as $key => $val) {
        if ($this->elementExists($key)) {
          $defaults[$key] = $val;
        }
      }
    }

    if ($this->_action == CRM_Core_Action::DELETE &&
      isset($defaults['name'])
    ) {
      $this->assign('delName', $defaults['name']);
    }

    // its ok if there is no element called is_active
    $defaults['is_active'] = ($this->_id) ? CRM_Utils_Array::value('is_active', $defaults) : 1;
    if (!empty($defaults['parent_id'])) {
      $this->assign('is_parent', TRUE);
    }
    return $defaults;
  }

  /**
   * Add standard buttons.
   */
  public function buildQuickForm() {
    if ($this->_action & CRM_Core_Action::VIEW || $this->_action & CRM_Core_Action::PREVIEW) {
      $this->addButtons([
        [
          'type' => 'cancel',
          'name' => ts('Done'),
          'isDefault' => TRUE,
        ],
      ]);
    }
    else {
      $this->addButtons([
        [
          'type' => 'next',
          'name' => $this->_action & CRM_Core_Action::DELETE ? ts('Delete') : ts('Save'),
          'isDefault' => TRUE,
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]);
    }
  }

}
