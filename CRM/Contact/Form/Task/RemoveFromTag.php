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
   * Build the form object.
   */
  public function buildQuickForm() {
    // add select for tag
    $this->add('select2', 'tag', ts('Select Tag'), CRM_Core_BAO_Tag::getColorTags(), FALSE, ['multiple' => TRUE]);

    $parentNames = CRM_Core_BAO_Tag::getTagSet('civicrm_contact');
    CRM_Core_Form_Tag::buildQuickForm($this, $parentNames, 'civicrm_contact', NULL, TRUE);

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
    $params = $this->controller->exportValues();

    $contactTags = $tagList = [];

    // check if contact tags exists
    if (!empty($params['tag'])) {
      $contactTags = array_flip(explode(',', $params['tag']));
    }

    // check if tags are selected from taglists
    if (!empty($params['contact_taglist'])) {
      foreach ($params['contact_taglist'] as $val) {
        if ($val) {
          if (is_numeric($val)) {
            $tagList[$val] = 1;
          }
          else {
            [, $tagID] = explode(',', $val);
            $tagList[$tagID] = 1;
          }
        }
      }
    }

    // merge contact and taglist tags
    $allTags = CRM_Utils_Array::crmArrayMerge($contactTags, $tagList);

    foreach ($allTags as $key => $dnc) {
      [, $removed, $notRemoved] = CRM_Core_BAO_EntityTag::removeEntitiesFromTag($this->_contactIds, $key,
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
      $tagLabel = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Tag', $key, 'label');
      $status = '<ul><li>' . implode('</li><li>', $status) . '</li></ul>';
      CRM_Core_Session::setStatus($status, ts("Removed Tag <em>%1</em>", [1 => $tagLabel]), 'success');
    }
  }

}
