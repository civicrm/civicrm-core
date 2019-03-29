<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * Form helper class for an Email object.
 */
class CRM_Contact_Form_Inline_Email extends CRM_Contact_Form_Inline {

  /**
   * Email addresses of the contact that is been viewed.
   */
  private $_emails = [];

  /**
   * No of email blocks for inline edit.
   */
  private $_blockCount = 6;

  /**
   * Whether this contact has a first/last/organization/household name
   *
   * @var bool
   */
  public $contactHasName;

  /**
   * Call preprocess.
   */
  public function preProcess() {
    parent::preProcess();

    //get all the existing email addresses
    $email = new CRM_Core_BAO_Email();
    $email->contact_id = $this->_contactId;

    $this->_emails = CRM_Core_BAO_Block::retrieveBlock($email, NULL);

    // Check if this contact has a first/last/organization/household name
    if ($this->_contactType == 'Individual') {
      $this->contactHasName = (bool) (CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->_contactId, 'last_name')
        || CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->_contactId, 'first_name'));
    }
    else {
      $this->contactHasName = (bool) CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->_contactId, strtolower($this->_contactType) . '_name');
    }
  }

  /**
   * Build the form object elements for an email object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    $totalBlocks = $this->_blockCount;
    $actualBlockCount = 1;
    if (count($this->_emails) > 1) {
      $actualBlockCount = $totalBlocks = count($this->_emails);
      if ($totalBlocks < $this->_blockCount) {
        $additionalBlocks = $this->_blockCount - $totalBlocks;
        $totalBlocks += $additionalBlocks;
      }
      else {
        $actualBlockCount++;
        $totalBlocks++;
      }
    }

    $this->assign('actualBlockCount', $actualBlockCount);
    $this->assign('totalBlocks', $totalBlocks);

    $this->applyFilter('__ALL__', 'trim');

    for ($blockId = 1; $blockId < $totalBlocks; $blockId++) {
      CRM_Contact_Form_Edit_Email::buildQuickForm($this, $blockId, TRUE);
    }

    $this->addFormRule(['CRM_Contact_Form_Inline_Email', 'formRule'], $this);
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $fields
   *   Posted values of the form.
   * @param array $errors
   *   List of errors to be posted back to the form.
   * @param CRM_Contact_Form_Inline_Email $form
   *
   * @return array
   */
  public static function formRule($fields, $errors, $form) {
    $hasData = $hasPrimary = $errors = [];
    if (!empty($fields['email']) && is_array($fields['email'])) {
      foreach ($fields['email'] as $instance => $blockValues) {
        $dataExists = CRM_Contact_Form_Contact::blockDataExists($blockValues);

        if ($dataExists) {
          $hasData[] = $instance;
          if (!empty($blockValues['is_primary'])) {
            $hasPrimary[] = $instance;
          }
        }
      }

      if (empty($hasPrimary) && !empty($hasData)) {
        $errors["email[1][is_primary]"] = ts('One email should be marked as primary.');
      }

      if (count($hasPrimary) > 1) {
        $errors["email[" . array_pop($hasPrimary) . "][is_primary]"] = ts('Only one email can be marked as primary.');
      }
    }
    if (!$hasData && !$form->contactHasName) {
      $errors["email[1][email]"] = ts('Contact with no name must have an email.');
    }
    return $errors;
  }

  /**
   * Set defaults for the form.
   *
   * @return array
   */
  public function setDefaultValues() {
    $defaults = [];
    if (!empty($this->_emails)) {
      foreach ($this->_emails as $id => $value) {
        $defaults['email'][$id] = $value;
      }
    }
    else {
      // get the default location type
      $locationType = CRM_Core_BAO_LocationType::getDefault();
      $defaults['email'][1]['location_type_id'] = $locationType->id;
    }

    return $defaults;
  }

  /**
   * Process the form.
   */
  public function postProcess() {
    $params = $this->exportValues();

    // Process / save emails
    $params['contact_id'] = $this->_contactId;
    $params['updateBlankLocInfo'] = TRUE;
    $params['email']['isIdSet'] = TRUE;
    foreach ($this->_emails as $count => $value) {
      if (!empty($value['id']) && isset($params['email'][$count])) {
        $params['email'][$count]['id'] = $value['id'];
      }
    }
    CRM_Core_BAO_Block::create('email', $params);

    // If contact has no name, set primary email as display name
    // TODO: This should be handled in the BAO for the benefit of the api, etc.
    if (!$this->contactHasName) {
      foreach ($params['email'] as $email) {
        if ($email['is_primary']) {
          CRM_Core_DAO::setFieldValue('CRM_Contact_DAO_Contact', $this->_contactId, 'display_name', $email['email']);
          CRM_Core_DAO::setFieldValue('CRM_Contact_DAO_Contact', $this->_contactId, 'sort_name', $email['email']);
          $this->ajaxResponse['reloadBlocks'] = ['#crm-contactname-content'];
          break;
        }
      }
    }

    $this->log();
    $this->response();
  }

}
