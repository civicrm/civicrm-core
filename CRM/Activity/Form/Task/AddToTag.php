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
 * This class provides the functionality to tag a group of
 * activities (or a single activity)
 */
class CRM_Activity_Form_Task_AddToTag extends CRM_Activity_Form_Task {

  /**
   * @var bool
   */
  public $submitOnce = TRUE;

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    // add select for tag
    $this->add('select2', 'tag', ts('Select Tag'), CRM_Core_BAO_Tag::getColorTags('civicrm_activity'), FALSE, ['multiple' => TRUE]);

    $parentNames = CRM_Core_BAO_Tag::getTagSet('civicrm_activity');

    CRM_Core_Form_Tag::buildQuickForm($this, $parentNames, 'civicrm_activity', NULL, TRUE);

    $this->addDefaultButtons(ts('Tag Activities'));
  }

  public function addRules() {
    $this->addFormRule(['CRM_Activity_Form_Task_AddToTag', 'formRule']);
  }

  /**
   * @param CRM_Core_Form $form
   * @param $rule
   *
   * @return array
   */
  public static function formRule($form, $rule) {
    $errors = [];
    if (empty($form['tag']) && empty($form['activity_taglist'])) {
      $errors['_qf_default'] = ts("Please select at least one tag.");
    }
    return $errors;
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    // Get the submitted values in an array.
    $params = $this->controller->exportValues();
    $activityTags = $tagList = [];

    // check if contact tags exists
    if (!empty($params['tag'])) {
      $activityTags = array_flip(explode(',', $params['tag']));
    }

    // check if tags are selected from taglists
    if (!empty($params['activity_taglist'])) {
      foreach ($params['activity_taglist'] as $val) {
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

    // merge activity and taglist tags
    $allTags = CRM_Utils_Array::crmArrayMerge($activityTags, $tagList);

    foreach ($allTags as $key => $dnc) {
      [, $added, $notAdded] = CRM_Core_BAO_EntityTag::addEntitiesToTag($this->_activityHolderIds, $key,
        'civicrm_activity', FALSE);

      $status = [ts('Activity tagged', ['count' => $added, 'plural' => '%count activities tagged'])];
      if ($notAdded) {
        $status[] = ts('1 activity already had this tag', [
          'count' => $notAdded,
          'plural' => '%count activities already had this tag',
        ]);
      }
      $tagLabel = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Tag', $key, 'label');
      $status = '<ul><li>' . implode('</li><li>', $status) . '</li></ul>';
      CRM_Core_Session::setStatus($status, ts("Added Tag <em>%1</em>", [1 => $tagLabel]), 'success');
    }

  }

}
