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
 * Parent class for inline contact forms.
 */
abstract class CRM_Contact_Form_Inline extends CRM_Core_Form {

  /**
   * Id of the contact that is being edited
   * @var int
   */
  public $_contactId;

  /**
   * Type of contact being edited
   * @var string
   */
  public $_contactType;

  /**
   * Sub type of contact being edited
   * @var string
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

    $buttons = [
      [
        'type' => 'upload',
        'name' => ts('Save'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ];
    $this->addButtons($buttons);
  }

  /**
   * Override default cancel action.
   */
  public function cancelAction() {
    $response = ['status' => 'cancel'];
    CRM_Utils_JSON::output($response);
  }

  /**
   * Set defaults for the form.
   *
   * @return array
   */
  public function setDefaultValues() {
    $defaults = $params = [];
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
    $ret = ['markup' => $smarty->fetch('CRM/common/contactFooter.tpl')];
    if ($includeCount) {
      $ret['count'] = CRM_Contact_BAO_Contact::getCountComponent('log', $cid);
    }
    return ['changeLog' => $ret];
  }

}
