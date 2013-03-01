<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * Administer Personal Campaign Pages - Search form
 */
class CRM_PCP_Form_PCP extends CRM_Core_Form {

  public $_context;

  /**
   * Function to set variables up before form is built
   *
   * @param null
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      //check permission for action.
      if (!CRM_Core_Permission::checkActionPermission('CiviEvent', $this->_action)) {
        CRM_Core_Error::fatal(ts('You do not have permission to access this page'));
      }

      $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
      $this->_title = CRM_Core_DAO::getFieldValue('CRM_PCP_DAO_PCP', $this->_id, 'title');
      $this->assign('title', $this->_title);
      parent::preProcess();
    }

    if (!$this->_action) {
      $this->_action = CRM_Utils_Array::value('action', $_GET);
      $this->_id = CRM_Utils_Array::value('id', $_GET);
    }
    else {
      $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    }

    //give the context.
    if (!isset($this->_context)) {
      $this->_context = CRM_Utils_Request::retrieve('context', 'String', $this);
    }

    $this->assign('context', $this->_context);


    $session = CRM_Core_Session::singleton();
    $context = $session->popUserContext();
    $userID  = $session->get('userID');

    //do not allow destructive actions without permissions
    $permission = FALSE;
    if (CRM_Core_Permission::check('administer CiviCRM') ||
      ($userID && (CRM_Core_DAO::getFieldValue('CRM_PCP_DAO_PCP',
            $this->_id,
            'contact_id'
          ) == $userID))
    ) {
      $permission = TRUE;
    }
    if ($permission && $this->_id) {

      $this->_title = CRM_Core_DAO::getFieldValue('CRM_PCP_DAO_PCP', $this->_id, 'title');
      switch ($this->_action) {
        case CRM_Core_Action::DELETE:
        case 'delete':
          CRM_PCP_BAO_PCP::deleteById($this->_id);
          CRM_Core_Session::setStatus(ts("The Campaign Page '%1' has been deleted.", array(1 => $this->_title)), ts('Page Deleted'), 'success');
          break;

        case CRM_Core_Action::DISABLE:
        case 'disable':
          CRM_PCP_BAO_PCP::setDisable($this->_id, '0');
          CRM_Core_Session::setStatus(ts("The Campaign Page '%1' has been disabled.", array(1 => $this->_title)), ts('Page Disabled'), 'success');
          break;

        case CRM_Core_Action::ENABLE:
        case 'enable':
          CRM_PCP_BAO_PCP::setDisable($this->_id, '1');
          CRM_Core_Session::setStatus(ts("The Campaign Page '%1' has been enabled.", array(1 => $this->_title)), ts('Page Enabled'), 'success');
          break;
      }

      if ($context) {
        CRM_Utils_System::redirect($context);
      }
    }
  }

  /**
   * This function sets the default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @param null
   *
   * @return array   array of default values
   * @access public
   */
  function setDefaultValues() {
    $defaults = array();

    $pageType = CRM_Utils_Request::retrieve('page_type', 'String', $this);
    $defaults['page_type'] = !empty($pageType) ? $pageType : '';

    return $defaults;
  }

  /**
   * Function to actually build the form
   *
   * @param null
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      $this->addButtons(array(
          array(
            'type' => 'next',
            'name' => ts('Delete Campaign'),
            'isDefault' => TRUE,
          ),
          array(
            'type' => 'cancel',
            'name' => ts('Cancel'),
          ),
        )
      );
    }
    else {

      $status       = array('' => ts('- select -')) + CRM_PCP_PseudoConstant::pcpStatus();
      $types        = array('' => ts('- select -')) + CRM_PCP_PseudoConstant::pcpType();
      $contribPages = array('' => ts('- select -')) + CRM_Contribute_PseudoConstant::contributionPage();
      $eventPages   = array('' => ts('- select -')) + CRM_Event_PseudoConstant::event(NULL, FALSE, "( is_template IS NULL OR is_template != 1 )");

      $this->addElement('select', 'status_id', ts('Status'), $status);
      $this->addElement('select', 'page_type', ts('Source Type'), $types);
      $this->addElement('select', 'page_id', ts('Contribution Page'), $contribPages);
      $this->addElement('select', 'event_id', ts('Event Page'), $eventPages);
      $this->addButtons(array(
          array(
            'type' => 'refresh',
            'name' => ts('Search'),
            'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
            'isDefault' => TRUE,
          ),
        )
      );
      parent::buildQuickForm();
    }
  }

  /**
   * global validation rules for the form
   *
   * @param array $fields posted values of the form
   *
   * @return array list of errors to be posted back to the form
   * @static
   * @access public
   */
  static function formRule($fields, $files, $form) {}

  /**
   * Process the form
   *
   * @param null
   *
   * @return void
   * @access public
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_PCP_BAO_PCP::deleteById($this->_id);
      CRM_Core_Session::setStatus(ts("The Campaign Page '%1' has been deleted.", array(1 => $this->_title)), ts('Page Deleted'), 'success');
    }
    else {
      $params = $this->controller->exportValues($this->_name);
      $parent = $this->controller->getParent();

      if (!empty($params)) {
        $fields = array('status_id', 'page_id');
        foreach ($fields as $field) {
          if (isset($params[$field]) &&
            !CRM_Utils_System::isNull($params[$field])
          ) {
            $parent->set($field, $params[$field]);
          }
          else {
            $parent->set($field, NULL);
          }
        }
      }
    }
  }
}

