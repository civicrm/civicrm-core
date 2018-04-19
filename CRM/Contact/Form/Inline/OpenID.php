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
 * Form helper class for an OpenID object.
 */
class CRM_Contact_Form_Inline_OpenID extends CRM_Contact_Form_Inline {

  /**
   * Ims of the contact that is been viewed.
   */
  private $_openids = array();

  /**
   * No of openid blocks for inline edit.
   */
  private $_blockCount = 6;

  /**
   * Call preprocess.
   */
  public function preProcess() {
    parent::preProcess();

    //get all the existing openids
    $openid = new CRM_Core_BAO_OpenID();
    $openid->contact_id = $this->_contactId;

    $this->_openids = CRM_Core_BAO_Block::retrieveBlock($openid, NULL);
  }

  /**
   * Build the form object elements for openID object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    $totalBlocks = $this->_blockCount;
    $actualBlockCount = 1;
    if (count($this->_openids) > 1) {
      $actualBlockCount = $totalBlocks = count($this->_openids);
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
      CRM_Contact_Form_Edit_OpenID::buildQuickForm($this, $blockId, TRUE);
    }

    $this->addFormRule(array('CRM_Contact_Form_Inline_OpenID', 'formRule'));
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $fields
   *   Posted values of the form.
   * @param array $errors
   *   List of errors to be posted back to the form.
   *
   * @return array
   */
  public static function formRule($fields, $errors) {
    $hasData = $hasPrimary = $errors = array();
    if (!empty($fields['openid']) && is_array($fields['openid'])) {
      foreach ($fields['openid'] as $instance => $blockValues) {
        $dataExists = CRM_Contact_Form_Contact::blockDataExists($blockValues);

        if ($dataExists) {
          $hasData[] = $instance;
          if (!empty($blockValues['is_primary'])) {
            $hasPrimary[] = $instance;
            if (!$primaryID && !empty($blockValues['openid'])) {
              $primaryID = $blockValues['openid'];
            }
          }
        }
      }

      if (empty($hasPrimary) && !empty($hasData)) {
        $errors["openid[1][is_primary]"] = ts('One OpenID should be marked as primary.');
      }

      if (count($hasPrimary) > 1) {
        $errors["openid[" . array_pop($hasPrimary) . "][is_primary]"] = ts('Only one OpenID can be marked as primary.');
      }
    }
    return $errors;
  }

  /**
   * Set defaults for the form.
   *
   * @return array
   */
  public function setDefaultValues() {
    $defaults = array();
    if (!empty($this->_openids)) {
      foreach ($this->_openids as $id => $value) {
        $defaults['openid'][$id] = $value;
      }
    }
    else {
      // get the default location type
      $locationType = CRM_Core_BAO_LocationType::getDefault();
      $defaults['openid'][1]['location_type_id'] = $locationType->id;
    }
    return $defaults;
  }

  /**
   * Process the form.
   */
  public function postProcess() {
    $params = $this->exportValues();

    // Process / save openID
    $params['contact_id'] = $this->_contactId;
    $params['updateBlankLocInfo'] = TRUE;
    $params['openid']['isIdSet'] = TRUE;
    foreach ($this->_openids as $count => $value) {
      if (!empty($value['id']) && isset($params['openid'][$count])) {
        $params['openid'][$count]['id'] = $value['id'];
      }
    }
    CRM_Core_BAO_Block::create('openid', $params);

    $this->log();
    $this->response();
  }

}
