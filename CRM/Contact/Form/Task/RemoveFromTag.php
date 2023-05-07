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
 * This class provides the functionality to remove tags of contact(s).
 */
class CRM_Contact_Form_Task_RemoveFromTag extends CRM_Contact_Form_Task {

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
      $this->addElement('checkbox', "tag[$tagID]", NULL, $tagName);
    }

    $parentNames = CRM_Core_BAO_Tag::getTagSet('civicrm_contact');
    CRM_Core_Form_Tag::buildQuickForm($this, $parentNames, 'civicrm_contact', NULL, TRUE, FALSE);

    $this->addDefaultButtons(ts('Remove Tags from Contacts'));
  }

  public function addRules() {
    $this->addFormRule(['CRM_Contact_Form_Task_RemoveFromTag', 'formRule']);
  }

  /**
   * @param CRM_Core_Form $form
   * @param $rule
   *
   * @return array
   */
  public static function formRule($form, $rule) {
    $errors = [];
    if (empty($form['tag']) && empty($form['contact_taglist'])) {
      $errors['_qf_default'] = ts('Please select at least one tag.');
    }
    return $errors;
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    //get the submitted values in an array
    $params = $this->controller->exportValues($this->_name);

    $contactTags = $tagList = [];

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
            list($label, $tagID) = explode(',', $val);
            $tagList[$tagID] = 1;
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

    $this->_name = [];
    foreach ($allTags as $key => $dnc) {
      $this->_name[] = $this->_tags[$key];

      list($total, $removed, $notRemoved) = CRM_Core_BAO_EntityTag::removeEntitiesFromTag($this->_contactIds, $key,
        'civicrm_contact', FALSE);

      $status = [
        ts('%count contact un-tagged', [
          'count' => $removed,
          'plural' => '%count contacts un-tagged',
        ]),
      ];
      if ($notRemoved) {
        $status[] = ts('1 contact already did not have this tag', [
          'count' => $notRemoved,
          'plural' => '%count contacts already did not have this tag',
        ]);
      }
      $status = '<ul><li>' . implode('</li><li>', $status) . '</li></ul>';
      CRM_Core_Session::setStatus($status, ts("Removed Tag <em>%1</em>", [1 => $this->_tags[$key]]), 'success');
    }
  }

}
