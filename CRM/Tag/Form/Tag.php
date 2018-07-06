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
 * $Id$
 *
 */

/**
 * This class generates form components for tags
 *
 */
class CRM_Tag_Form_Tag extends CRM_Core_Form {

  /**
   * The contact id, used when add/edit tag
   *
   * @var int
   */
  protected $_entityID;
  protected $_entityTable;

  public function preProcess() {
    if ($this->get('entityID')) {
      $this->_entityID = $this->get('entityID');
    }
    else {
      $this->_entityID = $this->get('contactId');
    }

    $this->_entityTable = $this->get('entityTable');

    if (empty($this->_entityTable)) {
      $this->_entityTable = 'civicrm_contact';
    }

    $this->assign('entityID', $this->_entityID);
    $this->assign('entityTable', $this->_entityTable);
  }

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {
    // get categories for the contact id
    $entityTag = CRM_Core_BAO_EntityTag::getTag($this->_entityID, $this->_entityTable);
    $this->assign('tagged', $entityTag);

    // get the list of all the categories
    $allTags = CRM_Core_BAO_Tag::getTagsUsedFor($this->_entityTable, FALSE);

    // need to append the array with the " checked " if contact is tagged with the tag
    foreach ($allTags as $tagID => $varValue) {
      if (in_array($tagID, $entityTag)) {
        $tagAttribute = array(
          'checked' => 'checked',
          'id' => "tag_{$tagID}",
        );
      }
      else {
        $tagAttribute = array(
          'id' => "tag_{$tagID}",
        );
      }

      $tagChk[$tagID] = $this->createElement('checkbox', $tagID, '', '', $tagAttribute);
    }

    $this->addGroup($tagChk, 'tagList', NULL, NULL, TRUE);

    $tags = new CRM_Core_BAO_Tag();
    $tree = $tags->getTree($this->_entityTable, TRUE);
    $this->assign('tree', $tree);

    $this->assign('allTags', $allTags);

    //build tag widget
    $parentNames = CRM_Core_BAO_Tag::getTagSet('civicrm_contact');
    CRM_Core_Form_Tag::buildQuickForm($this, $parentNames, $this->_entityTable, $this->_entityID);
  }

  /**
   *
   * @return void
   */
  public function postProcess() {
    CRM_Utils_System::flushCache();

    // array contains the posted values
    // exportvalues is not used because its give value 1 of the checkbox which were checked by default,
    // even after unchecking them before submitting them
    $entityTag = $_POST['tagList'];

    CRM_Core_BAO_EntityTag::create($entityTag, $this->_entityTable, $this->_entityID);

    CRM_Core_Session::setStatus(ts('Your update(s) have been saved.'), ts('Saved'), 'success');
  }

}
