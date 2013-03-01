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
 * form helper class for an Email object
 */
class CRM_Contact_Form_Inline_Email extends CRM_Contact_Form_Inline {

  /**
   * email addresses of the contact that is been viewed
   */
  private $_emails = array();

  /**
   * No of email blocks for inline edit
   */
  private $_blockCount = 6;

  /**
   * call preprocess
   */
  public function preProcess() {
    parent::preProcess();

    //get all the existing email addresses
    $email = new CRM_Core_BAO_Email();
    $email->contact_id = $this->_contactId;

    $this->_emails = CRM_Core_BAO_Block::retrieveBlock($email, NULL);
  }

  /**
   * build the form elements for an email object
   *
   * @return void
   * @access public
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

    $this->addFormRule(array('CRM_Contact_Form_Inline_Email', 'formRule'));
  }

  /**
   * global validation rules for the form
   *
   * @param array $fields     posted values of the form
   * @param array $errors     list of errors to be posted back to the form
   *
   * @return $errors
   * @static
   * @access public
   */
  static function formRule($fields, $errors) {
    $hasData = $hasPrimary = $errors = array();
    if (CRM_Utils_Array::value('email', $fields) && is_array($fields['email'])) {
      foreach ($fields['email'] as $instance => $blockValues) {
        $dataExists = CRM_Contact_Form_Contact::blockDataExists($blockValues);

        if ($dataExists) {
          $hasData[] = $instance;
          if (CRM_Utils_Array::value('is_primary', $blockValues)) {
            $hasPrimary[] = $instance;
            }
          }
        }


      if (empty($hasPrimary) && !empty($hasData)) {
        $errors["email[1][is_primary]"] = ts('One email should be marked as primary.');
      }

      if (count($hasPrimary) > 1) {
        $errors["email[".array_pop($hasPrimary)."][is_primary]"] = ts('Only one email can be marked as primary.');
      }
    }
    return $errors;
  }

  /**
   * set defaults for the form
   *
   * @return array
   * @access public
   */
  public function setDefaultValues() {
    $defaults = array();
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
   * process the form
   *
   * @return void
   * @access public
   */
  public function postProcess() {
    $params = $this->exportValues();

    // Process / save emails
    $params['contact_id'] = $this->_contactId;
    $params['updateBlankLocInfo'] = TRUE;
    CRM_Core_BAO_Block::create('email', $params);

    $this->log();
    $this->response();
  }
}
