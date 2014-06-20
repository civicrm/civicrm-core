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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * form helper class for an Website object
 */
class CRM_Contact_Form_Inline_Website extends CRM_Contact_Form_Inline {

  /**
   * websitess of the contact that is been viewed
   */
  private $_websites = array();

  /**
   * No of website blocks for inline edit
   */
  private $_blockCount = 6;

  /**
   * call preprocess
   */
  public function preProcess() {
    parent::preProcess();

    //get all the existing websites
    $params = array('contact_id' => $this->_contactId);
    $values = array();
    $this->_websites = CRM_Core_BAO_Website::getValues($params, $values);
  }

  /**
   * build the form elements for website object
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    $totalBlocks = $this->_blockCount;
    $actualBlockCount = 1;
    if (count($this->_websites) > 1) {
      $actualBlockCount = $totalBlocks = count($this->_websites);
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
      CRM_Contact_Form_Edit_Website::buildQuickForm($this, $blockId, TRUE);
    }

  }

  /**
   * set defaults for the form
   *
   * @return array
   * @access public
   */
  public function setDefaultValues() {
    $defaults = array();
    if (!empty($this->_websites)) {
      foreach ($this->_websites as $id => $value) {
        $defaults['website'][$id] = $value;
      }
    }
    else {
      // set the default website type
      $defaults['website'][1]['website_type_id'] = key(CRM_Core_OptionGroup::values('website_type',
        FALSE, FALSE, FALSE, ' AND is_default = 1'
      ));
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

    // Process / save websites
    CRM_Core_BAO_Website::create($params['website'], $this->_contactId, true);

    $this->log();
    $this->response();
  }
}
