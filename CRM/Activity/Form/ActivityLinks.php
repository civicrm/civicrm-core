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
 * This class generates form components for Activity Links.
 */
class CRM_Activity_Form_ActivityLinks extends CRM_Core_Form {

  public function buildQuickForm() {
    self::commonBuildQuickForm($this);
  }

  /**
   * @param self $self
   */
  public static function commonBuildQuickForm($self) {
    $contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $self) ?: CRM_Utils_Request::retrieve('cid', 'Positive');

    $activityTypes = [];

    $activityLinks = \Civi\Api4\Activity::getLinks()
      ->addValue('target_contact_id', $contactId)
      ->addWhere('ui_action', '=', 'add')
      ->setExpandMultiple(TRUE)
      ->execute();
    foreach ($activityLinks as $activityLink) {
      $activityTypes[] = [
        'label' => $activityLink['text'],
        'icon' => $activityLink['icon'],
        'url' => CRM_Utils_System::url($activityLink['path'], NULL, FALSE, NULL, FALSE),
      ];
    }

    $self->assign('activityTypes', $activityTypes);

    $self->assign('suppressForm', TRUE);
  }

}
