<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
 * @copyright CiviCRM LLC (c) 2004-2017
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
  protected $_id;

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
    Civi::resources()->addScriptFile('civicrm', 'js/crm.admin.js');

    $this->_id = $this->get('id');
    $this->_BAOName = $this->get('BAOName');
    $this->_values = array();
    if (isset($this->_id)) {
      $params = array('id' => $this->_id);
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
      $this->_values = array();
      $params = array('id' => $this->_id);
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
      $this->addButtons(array(
          array(
            'type' => 'cancel',
            'name' => ts('Done'),
            'isDefault' => TRUE,
          ),
        )
      );
    }
    else {
      $this->addButtons(array(
          array(
            'type' => 'next',
            'name' => $this->_action & CRM_Core_Action::DELETE ? ts('Delete') : ts('Save'),
            'isDefault' => TRUE,
          ),
          array(
            'type' => 'cancel',
            'name' => ts('Cancel'),
          ),
        )
      );
    }
  }

}
