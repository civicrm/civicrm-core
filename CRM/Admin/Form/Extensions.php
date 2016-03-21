<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * This class generates form components for Extensions.
 */
class CRM_Admin_Form_Extensions extends CRM_Admin_Form {

  /**
   * Form pre-processing.
   */
  public function preProcess() {
    parent::preProcess();

    $this->_key = CRM_Utils_Request::retrieve('key', 'String',
      $this, FALSE, 0
    );

    $session = CRM_Core_Session::singleton();
    $url = CRM_Utils_System::url('civicrm/admin/extensions', 'reset=1&action=browse');
    $session->pushUserContext($url);
    $this->assign('id', $this->_id);
    $this->assign('key', $this->_key);

    switch ($this->_action) {
      case CRM_Core_Action::ADD:
      case CRM_Core_Action::DELETE:
      case CRM_Core_Action::ENABLE:
      case CRM_Core_Action::DISABLE:
        $info = CRM_Extension_System::singleton()->getMapper()->keyToInfo($this->_key);
        $extInfo = CRM_Admin_Page_Extensions::createExtendedInfo($info);
        $this->assign('extension', $extInfo);
        break;

      case CRM_Core_Action::UPDATE:
        if (!CRM_Extension_System::singleton()->getBrowser()->isEnabled()) {
          CRM_Core_Error::fatal(ts('The system administrator has disabled this feature.'));
        }
        $info = CRM_Extension_System::singleton()->getBrowser()->getExtension($this->_key);
        $extInfo = CRM_Admin_Page_Extensions::createExtendedInfo($info);
        $this->assign('extension', $extInfo);
        break;

      default:
        CRM_Core_Error::fatal(ts('Unsupported action'));
    }

  }

  /**
   * Set default values for the form.
   */
  public function setDefaultValues() {
    $defaults = array();
    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    switch ($this->_action) {
      case CRM_Core_Action::ADD:
        $buttonName = ts('Install');
        $title = ts('Install "%1"?', array(
          1 => $this->_key,
        ));
        break;

      case CRM_Core_Action::UPDATE:
        $buttonName = ts('Download and Install');
        $title = ts('Download and Install "%1"?', array(
          1 => $this->_key,
        ));
        break;

      case CRM_Core_Action::DELETE:
        $buttonName = ts('Uninstall');
        $title = ts('Uninstall "%1"?', array(
          1 => $this->_key,
        ));
        break;

      case CRM_Core_Action::ENABLE:
        $buttonName = ts('Enable');
        $title = ts('Enable "%1"?', array(
          1 => $this->_key,
        ));
        break;

      case CRM_Core_Action::DISABLE:
        $buttonName = ts('Disable');
        $title = ts('Disable "%1"?', array(
          1 => $this->_key,
        ));
        break;
    }

    $this->assign('title', $title);
    $this->addButtons(array(
        array(
          'type' => 'next',
          'name' => $buttonName,
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );
  }

  /**
   * Global form rule.
   *
   * @param array $fields
   *   The input form values.
   * @param array $files
   *   The uploaded files if any.
   * @param array $self
   *   This object.
   *
   * @return bool|array
   *   true if no errors, else an array of errors
   */
  public static function formRule($fields, $files, $self) {
    $errors = array();

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    CRM_Utils_System::flushCache();

    if ($this->_action & CRM_Core_Action::DELETE) {
      try {
        CRM_Extension_System::singleton()->getManager()->uninstall(array($this->_key));
        CRM_Core_Session::setStatus("", ts('Extension Uninstalled'), "success");
      }
      catch (CRM_Extension_Exception_DependencyException $e) {
        // currently only thrown for payment-processor dependencies
        CRM_Core_Session::setStatus(ts('Cannot uninstall this extension - there is at least one payment processor using the payment processor type provided by it.'), ts('Uninstall Error'), 'error');
      }
    }

    if ($this->_action & CRM_Core_Action::ADD) {
      CRM_Extension_System::singleton()->getManager()->install(array($this->_key));
      CRM_Core_Session::setStatus("", ts('Extension Installed'), "success");
    }

    if ($this->_action & CRM_Core_Action::ENABLE) {
      CRM_Extension_System::singleton()->getManager()->enable(array($this->_key));
      CRM_Core_Session::setStatus("", ts('Extension Enabled'), "success");
    }

    if ($this->_action & CRM_Core_Action::DISABLE) {
      CRM_Extension_System::singleton()->getManager()->disable(array($this->_key));
      CRM_Core_Session::setStatus("", ts('Extension Disabled'), "success");
    }

    if ($this->_action & CRM_Core_Action::UPDATE) {
      $result = civicrm_api('Extension', 'download', array(
        'version' => 3,
        'key' => $this->_key,
      ));
      if (!CRM_Utils_Array::value('is_error', $result, FALSE)) {
        CRM_Core_Session::setStatus("", ts('Extension Upgraded'), "success");
      }
      else {
        CRM_Core_Session::setStatus($result['error_message'], ts('Extension Upgrade Failed'), "error");
      }
    }

    CRM_Utils_System::redirect(
      CRM_Utils_System::url(
        'civicrm/admin/extensions',
        'reset=1&action=browse'
      )
    );
  }

}
