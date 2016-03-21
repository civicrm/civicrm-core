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
 * This class provides the functionality to delete a group of contacts.
 *
 * This class provides functionality for the actual addition of contacts to groups.
 *
 * Wow is that copy & paste gone wrong or what? What does this class do? Anyone, anyone.
 */
class CRM_Contact_Form_Task_AddToTag extends CRM_Contact_Form_Task {

  /**
   * Name of the tag.
   *
   * @var string
   */
  protected $_name;

  /**
   * All the tags in the system.
   *
   * @var array
   */
  protected $_tags;

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    // add select for tag
    $this->_tags = CRM_Core_BAO_Tag::getTags();

    foreach ($this->_tags as $tagID => $tagName) {
      $this->_tagElement = &$this->addElement('checkbox', "tag[$tagID]", NULL, $tagName);
    }

    $parentNames = CRM_Core_BAO_Tag::getTagSet('civicrm_contact');
    CRM_Core_Form_Tag::buildQuickForm($this, $parentNames, 'civicrm_contact');

    $this->addDefaultButtons(ts('Tag Contacts'));
  }

  public function addRules() {
    $this->addFormRule(array('CRM_Contact_Form_Task_AddToTag', 'formRule'));
  }

  /**
   * @param CRM_Core_Form $form
   * @param $rule
   *
   * @return array
   */
  public static function formRule($form, $rule) {
    $errors = array();
    if (empty($form['tag']) && empty($form['contact_taglist'])) {
      $errors['_qf_default'] = ts("Please select at least one tag.");
    }
    return $errors;
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    //get the submitted values in an array
    $params = $this->controller->exportValues($this->_name);
    $contactTags = $tagList = array();

    // check if contact tags exists
    if (!empty($params['tag'])) {
      $contactTags = $params['tag'];
    }

    // check if tags are selected from taglists
    if (!empty($params['contact_taglist'])) {
      foreach ($params['contact_taglist'] as $val) {
        if ($val) {
          if (is_numeric($val)) {
            $tagList[$val] = 1;
          }
          else {
            $tagIDs = explode(',', $val);
            if (!empty($tagIDs)) {
              foreach ($tagIDs as $tagID) {
                if (is_numeric($tagID)) {
                  $tagList[$tagID] = 1;
                }
              }
            }
          }
        }
      }
    }

    $tagSets = CRM_Core_BAO_Tag::getTagsUsedFor('civicrm_contact', FALSE, TRUE);

    foreach ($tagSets as $key => $value) {
      $this->_tags[$key] = $value['name'];
    }

    // merge contact and taglist tags
    $allTags = CRM_Utils_Array::crmArrayMerge($contactTags, $tagList);

    $this->_name = array();
    foreach ($allTags as $key => $dnc) {
      $this->_name[] = $this->_tags[$key];

      list($total, $added, $notAdded) = CRM_Core_BAO_EntityTag::addEntitiesToTag($this->_contactIds, $key,
        'civicrm_contact', FALSE);

      $status = array(ts('%count contact tagged', array('count' => $added, 'plural' => '%count contacts tagged')));
      if ($notAdded) {
        $status[] = ts('%count contact already had this tag', array(
            'count' => $notAdded,
            'plural' => '%count contacts already had this tag',
          ));
      }
      $status = '<ul><li>' . implode('</li><li>', $status) . '</li></ul>';
      CRM_Core_Session::setStatus($status, ts("Added Tag <em>%1</em>", array(1 => $this->_tags[$key])), 'success', array('expires' => 0));
    }

  }

}
