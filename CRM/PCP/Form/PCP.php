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
 * $Id$
 *
 */

/**
 * Administer Personal Campaign Pages - Search form
 */
class CRM_PCP_Form_PCP extends CRM_Core_Form {

  public $_context;

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    $this->_id      = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $this->_action  = CRM_Utils_Request::retrieve('action', 'Positive', $this);
    if ($this->_action & CRM_Core_Action::DELETE) {
      //check permission for action.
      if (!CRM_Core_Permission::checkActionPermission('CiviEvent', $this->_action)) {
        CRM_Core_Error::fatal(ts('You do not have permission to access this page.'));
      }
    }
    $session = CRM_Core_Session::singleton();
    $userID = $session->get('userID');

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
    $this->assign('action', $this->_action);
    if ($permission && $this->_id) {
      $this->_title = CRM_Core_DAO::getFieldValue('CRM_PCP_DAO_PCP', $this->_id, 'title');
      $this->assign('title', $this->_title);
      switch ($this->_action) {
        case CRM_Core_Action::DELETE:
        case 'delete':
          CRM_Utils_System::setTitle(ts('Confirm Personal Campaign Page Delete'));
          break;
      }
    }
  }

  /**
   * Set default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @return array
   *   array of default values
   */
  public function setDefaultValues() {
  }

  /**
   * Build the form object.
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
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $fields
   *   Posted values of the form.
   * @param array $files
   * @param CRM_Core_Form $form
   *
   * @return array|NULL
   *   list of errors to be posted back to the form
   */
  public static function formRule($fields, $files, $form) {
    return NULL;
  }

  /**
   * Process the form.
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_PCP_BAO_PCP::deleteById($this->_id);
      CRM_Core_Session::setStatus(ts("The Campaign Page '%1' has been deleted.", array(1 => $this->_title)), ts('Page Deleted'), 'success');
    }
  }

}
