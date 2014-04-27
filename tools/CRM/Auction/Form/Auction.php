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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

require_once 'CRM/Core/Form.php';

/**
 * This class manages the auction form
 *
 */
class CRM_Auction_Form_Auction extends CRM_Core_Form {

  /**
   * the id of the auction we are proceessing
   *
   * @var int
   * @protected
   */
  public $_id;

  protected $_dates;

  protected $_checkboxes;

  protected $_numbers;

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */ function preProcess() {
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE);

    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);

    if (!CRM_Core_Permission::checkActionPermission('CiviAuction', $this->_action)) {
      CRM_Core_Error::fatal(ts('You do not have permission to access this page'));
    }

    if (($this->_action & CRM_Core_Action::VIEW ||
        $this->_action & CRM_Core_Action::UPDATE ||
        $this->_action & CRM_Core_Action::DELETE
      ) &&
      !$this->_id
    ) {
      CRM_Core_Error::fatal();
    }
  }

  /**
   * This function sets the default values for the form.
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  function setDefaultValues() {
    require_once 'CRM/Auction/BAO/Auction.php';

    $defaults = array();

    if (isset($this->_id)) {
      $params = array('id' => $this->_id);
      CRM_Auction_BAO_Auction::retrieve($params, $defaults);
    }
    else {
      $defaults['is_active'] = 1;
    }

    return $defaults;
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    $this->applyFilter('__ALL__', 'trim');

    $attributes = CRM_Core_DAO::getAttribute('CRM_Auction_DAO_Auction');
    $this->add('text',
      'title',
      ts('Auction Title'),
      $attributes['auction_title'],
      TRUE
    );

    $this->addWysiwyg('description',
      ts('Complete Description'),
      $attributes['description']
    );

    $this->_dates = array('start_date' => ts('Auction Start Date'),
      'end_date' => ts('Auction End Date'),
      'item_start_date' => ts('Upload Item Start Date'),
      'item_end_date' => ts('Upload Item End Date'),
    );
    foreach ($this->_dates as $name => $title) {
      $this->add('date',
        $name,
        $title,
        CRM_Core_SelectValues::date('datetime')
      );
      $this->addRule($name, ts('Please select a valid date.'), 'qfDate');
    }

    $this->_checkboxes = array('is_active' => ts('Is Active?'),
      'is_approval_needed' => ts('Do items need to be approved?'),
      'is_item_groups' => ts('Can items be grouped?'),
    );

    foreach ($this->_checkboxes as $name => $title) {
      $this->addElement('checkbox',
        $name,
        $title
      );
    }

    $this->_numbers = array('max_items_user' => ts('Maximum number of items per user'),
      'max_items' => ts('Maximum number of items for the auction'),
    );
    foreach ($this->_numbers as $name => $title) {
      $this->addElement('text',
        $name,
        $title,
        $attributes[$name]
      );
      $this->addRule($name,
        ts('%1 should be a postive number',
          array(1 => $title)
        ),
        'positiveInteger'
      );
    }

    if ($this->_action & CRM_Core_Action::VIEW) {
      $buttons = array(array('type' => 'upload',
          'name' => ts('Done'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ));
      $this->freeze();
    }
    elseif ($this->_action & CRM_Core_Action::DELETE) {
      $this->freeze();
      $buttons = array(array('type' => 'upload',
          'name' => ts('Delete'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ),
        array('type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      );
    }
    else {
      $buttons = array(array('type' => 'upload',
          'name' => ts('Save'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ),
        array('type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      );
    }
    $this->addButtons($buttons);

    $this->addFormRule(array('CRM_Auction_Form_Auction', 'formRule'), $this);
  }

  /**
   * global form rule
   *
   * @param array $fields  the input form values
   * @param array $files   the uploaded files if any
   * @param array $options additional user data
   *
   * @return true if no errors, else array of errors
   * @access public
   * @static
   */
  static
  function formRule($fields, $files, $self) {
    $errors = array();

    // add rules to validate dates and overlap
    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::VIEW) {
      return;
    }
    elseif ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Auction_BAO_Auction::del($this->_id);
      return;
    }

    $params = $this->controller->exportValues($this->_name);

    $params['id'] = $this->_id;

    // format date params
    foreach ($this->_dates as $name => $title) {
      $params[$name] = CRM_Utils_Date::format($params[$name]);
    }

    // format checkboxes
    foreach ($this->_checkboxes as $name => $title) {
      $params[$name] = CRM_Utils_Array::value($name, $params, FALSE);
    }

    CRM_Auction_BAO_Auction::add($params);
  }
}

