<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * Parent class for inline contact forms.
 */
abstract class CRM_Contact_Form_Inline extends CRM_Core_Form {

  /**
   * Id of the contact that is being edited
   */
  public $_contactId;

  /**
   * Type of contact being edited
   */
  public $_contactType;

  /**
   * Sub type of contact being edited
   */
  public $_contactSubType;

  /**
   * Explicitly declare the form context.
   */
  public function getDefaultContext() {
    return 'create';
  }

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'Contact';
  }

  /**
   * Common preprocess: fetch contact ID and contact type
   */
  public function preProcess() {
    $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE, NULL, $_REQUEST);
    $this->assign('contactId', $this->_contactId);

    // get contact type and subtype
    if (empty($this->_contactType)) {
      $contactTypeInfo = CRM_Contact_BAO_Contact::getContactTypes($this->_contactId);
      $this->_contactType = $contactTypeInfo[0];

      // check if subtype is set
      if (isset($contactTypeInfo[1])) {
        // unset contact type which is 0th element
        unset($contactTypeInfo[0]);
        $this->_contactSubType = $contactTypeInfo;
      }
    }

    $this->assign('contactType', $this->_contactType);

    $this->setAction(CRM_Core_Action::UPDATE);
  }

  /**
   * Common form elements.
   */
  public function buildQuickForm() {
    CRM_Contact_Form_Inline_Lock::buildQuickForm($this, $this->_contactId);

    $buttons = array(
      array(
        'type' => 'upload',
        'name' => ts('Save'),
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ),
    );
    $this->addButtons($buttons);
  }

  /**
   * Override default cancel action.
   */
  public function cancelAction() {
    $response = array('status' => 'cancel');
    CRM_Utils_JSON::output($response);
  }

  /**
   * Set defaults for the form.
   *
   * @return array
   */
  public function setDefaultValues() {
    $defaults = $params = array();
    $params['id'] = $this->_contactId;

    CRM_Contact_BAO_Contact::getValues($params, $defaults);

    return $defaults;
  }

  /**
   * Add entry to log table.
   */
  protected function log() {
    CRM_Core_BAO_Log::register($this->_contactId,
      'civicrm_contact',
      $this->_contactId
    );
  }

  /**
   * Common function for all inline contact edit forms.
   *
   * Prepares ajaxResponse
   */
  protected function response() {
    $this->ajaxResponse = array_merge(
      self::renderFooter($this->_contactId),
      $this->ajaxResponse,
      CRM_Contact_Form_Inline_Lock::getResponse($this->_contactId)
    );
    // Note: Post hooks will be called by CRM_Core_Form::mainProcess
  }

  /**
   * Render change log footer markup for a contact and supply count.
   *
   * Needed for refreshing the contact summary screen
   *
   * @param int $cid
   * @param bool $includeCount
   * @return array
   */
  public static function renderFooter($cid, $includeCount = TRUE) {
    // Load change log footer from template.
    $smarty = CRM_Core_Smarty::singleton();
    $smarty->assign('contactId', $cid);
    $smarty->assign('external_identifier', CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $cid, 'external_identifier'));
    $smarty->assign('lastModified', CRM_Core_BAO_Log::lastModified($cid, 'civicrm_contact'));
    $viewOptions = CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'contact_view_options', TRUE
    );
    $smarty->assign('changeLog', $viewOptions['log']);
    $ret = array('markup' => $smarty->fetch('CRM/common/contactFooter.tpl'));
    if ($includeCount) {
      $ret['count'] = CRM_Contact_BAO_Contact::getCountComponent('log', $cid);
    }
    return array('changeLog' => $ret);
  }

}
