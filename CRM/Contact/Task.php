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
 * Class to represent the actions that can be performed on a group of contacts used by the search forms.
 */
class CRM_Contact_Task extends CRM_Core_Task {

  const
    // Contact tasks
    HOUSEHOLD_CONTACTS = 101,
    ORGANIZATION_CONTACTS = 102,
    RECORD_CONTACTS = 103,
    MAP_CONTACTS = 104,
    ADD_EVENT = 105,
    MERGE_CONTACTS = 106,
    EMAIL_UNHOLD = 107,
    RESTORE = 108,
    COMMUNICATION_PREFS = 109,
    INDIVIDUAL_CONTACTS = 110,
    ADD_TO_CASE = 111;

  static $objectType = 'contact';

  public static function tasks() {
    if (!self::$_tasks) {
      self::$_tasks = array(
        self::GROUP_ADD => array(
          'title' => ts('Group - add contacts'),
          'class' => 'CRM_Contact_Form_Task_AddToGroup',
          'url' => 'civicrm/task/add-to-group',
        ),
        self::GROUP_REMOVE => array(
          'title' => ts('Group - remove contacts'),
          'class' => 'CRM_Contact_Form_Task_RemoveFromGroup',
          'url' => 'civicrm/task/remove-from-group',
        ),
        self::TAG_ADD => array(
          'title' => ts('Tag - add to contacts'),
          'class' => 'CRM_Contact_Form_Task_AddToTag',
          'url' => 'civicrm/task/add-to-tag',
        ),
        self::TAG_REMOVE => array(
          'title' => ts('Tag - remove from contacts'),
          'class' => 'CRM_Contact_Form_Task_RemoveFromTag',
          'url' => 'civicrm/task/remove-from-tag',
        ),
        self::TASK_EXPORT => array(
          'title' => ts('Export contacts'),
          'class' => array(
            'CRM_Export_Form_Select',
            'CRM_Export_Form_Map',
          ),
          'result' => FALSE,
        ),
        self::TASK_EMAIL => array(
          'title' => ts('Email - send now (to %1 or less)', array(
            1 => Civi::settings()
              ->get('simple_mail_limit'),
          )),
          'class' => 'CRM_Contact_Form_Task_Email',
          'result' => TRUE,
          'url' => 'civicrm/task/send-email',
        ),
        self::TASK_DELETE => array(
          'title' => ts('Delete contacts'),
          'class' => 'CRM_Contact_Form_Task_Delete',
          'result' => FALSE,
          'url' => 'civicrm/task/delete-contact',
        ),
        self::RECORD_CONTACTS => array(
          'title' => ts('Add activity'),
          'class' => 'CRM_Activity_Form_Activity',
        ),
        self::SAVE_SEARCH => array(
          'title' => ts('Group - create smart group'),
          'class' => 'CRM_Contact_Form_Task_SaveSearch',
          'result' => TRUE,
        ),
        self::SAVE_SEARCH_UPDATE => array(
          'title' => ts('Group - update smart group'),
          'class' => 'CRM_Contact_Form_Task_SaveSearch_Update',
          'result' => TRUE,
        ),
        self::TASK_PRINT => array(
          'title' => ts('Print selected rows'),
          'class' => 'CRM_Contact_Form_Task_Print',
          'result' => FALSE,
        ),
        self::LABEL_CONTACTS => array(
          'title' => ts('Mailing labels - print'),
          'class' => 'CRM_Contact_Form_Task_Label',
          'result' => TRUE,
          'url' => 'civicrm/task/make-mailing-label',
        ),
        self::BATCH_UPDATE => array(
          'title' => ts('Update multiple contacts'),
          'class' => array(
            'CRM_Contact_Form_Task_PickProfile',
            'CRM_Contact_Form_Task_Batch',
          ),
          'result' => TRUE,
          'url' => 'civicrm/task/pick-profile',
        ),
        self::PDF_LETTER => array(
          'title' => ts('Print/merge document'),
          'class' => 'CRM_Contact_Form_Task_PDF',
          'result' => TRUE,
          'url' => 'civicrm/task/print-document',
        ),
        self::EMAIL_UNHOLD => array(
          'title' => ts('Email - unhold addresses'),
          'class' => 'CRM_Contact_Form_Task_Unhold',
          'url' => 'civicrm/task/unhold-email',
        ),
        self::COMMUNICATION_PREFS => array(
          'title' => ts('Communication preferences - alter'),
          'class' => 'CRM_Contact_Form_Task_AlterPreferences',
          'url' => 'civicrm/task/alter-contact-preference',
        ),
        self::RESTORE => array(
          'title' => ts('Restore contacts from trash'),
          'class' => 'CRM_Contact_Form_Task_Delete',
          'result' => FALSE,
        ),
        self::DELETE_PERMANENTLY => array(
          'title' => ts('Delete permanently'),
          'class' => 'CRM_Contact_Form_Task_Delete',
          'result' => FALSE,
        ),
      );

      //CRM-16329, if SMS provider is configured show sms action.
      $providersCount = CRM_SMS_BAO_Provider::activeProviderCount();
      if ($providersCount && CRM_Core_Permission::check('send SMS')) {
        self::$_tasks[self::TASK_SMS] = array(
          'title' => ts('SMS - schedule/send'),
          'class' => 'CRM_Contact_Form_Task_SMS',
          'result' => TRUE,
        );
      }

      if (CRM_Contact_BAO_ContactType::isActive('Individual')) {
        $label = CRM_Contact_BAO_ContactType::getLabel('individual');
        self::$_tasks[self::INDIVIDUAL_CONTACTS] = array(
          'title' => ts('Add relationship - to %1',
            array(1 => $label)
          ),
          'class' => 'CRM_Contact_Form_Task_AddToIndividual',
        );
      }

      if (CRM_Contact_BAO_ContactType::isActive('Household')) {
        $label = CRM_Contact_BAO_ContactType::getLabel('household');
        self::$_tasks[self::HOUSEHOLD_CONTACTS] = array(
          'title' => ts('Add relationship - to %1',
            array(1 => $label)
          ),
          'class' => 'CRM_Contact_Form_Task_AddToHousehold',
        );
      }

      if (CRM_Contact_BAO_ContactType::isActive('Organization')) {
        $label = CRM_Contact_BAO_ContactType::getLabel('organization');
        self::$_tasks[self::ORGANIZATION_CONTACTS] = array(
          'title' => ts('Add relationship - to %1',
            array(1 => $label)
          ),
          'class' => 'CRM_Contact_Form_Task_AddToOrganization',
        );
      }

      if (CRM_Core_Permission::check('merge duplicate contacts')) {
        self::$_tasks[self::MERGE_CONTACTS] = array(
          'title' => ts('Merge contacts'),
          'class' => 'CRM_Contact_Form_Task_Merge',
          'result' => TRUE,
        );
      }

      //CRM-4418, check for delete
      if (!CRM_Core_Permission::check('delete contacts')) {
        unset(self::$_tasks[self::TASK_DELETE]);
      }

      //show map action only if map provider and geoprovider are set (Google doesn't need geoprovider)
      // should fix this to be more flexible as providers are added ??
      $config = CRM_Core_Config::singleton();

      if ($config->mapProvider &&
        ($config->mapProvider == 'Google' ||
          ($config->mapProvider == 'OpenStreetMaps' ||
            $config->geoProvider == 'Google'
          )
        )
      ) {
        self::$_tasks[self::MAP_CONTACTS] = array(
          'title' => ts('Map contacts'),
          'class' => 'CRM_Contact_Form_Task_Map',
          'result' => FALSE,
        );
      }

      if (CRM_Core_Permission::access('CiviEvent')) {
        self::$_tasks[self::ADD_EVENT] = array(
          'title' => ts('Register participants for event'),
          'class' => 'CRM_Event_Form_Participant',
        );
      }

      if (CRM_Core_Permission::access('CiviMail')
        || (CRM_Mailing_Info::workflowEnabled() && CRM_Core_Permission::check('create mailings'))
      ) {
        self::$_tasks[self::CREATE_MAILING] = array(
          'title' => ts('Email - schedule/send via CiviMail'),
          'class' => 'CRM_Mailing_Form_Task_AdhocMailing',
          'result' => FALSE,
        );
      }

      if (CRM_Core_Permission::access('CiviCase')) {
        self::$_tasks[self::ADD_TO_CASE] = array(
          'title' => 'Add to case as role',
          'class' => 'CRM_Case_Form_AddToCaseAsRole',
          'result' => FALSE,
        );
      }

      parent::tasks();
    }

    return self::$_tasks;
  }

  /**
   * Show tasks selectively based on the permission level
   * of the user
   *
   * @param int $permission
   * @param array $params
   *             bool deletedContacts: Are these tasks for operating on deleted contacts?.
   *
   * @return array
   *   set of tasks that are valid for the user
   */
  public static function permissionedTaskTitles($permission, $params = array()) {
    if (!isset($params['deletedContacts'])) {
      $params['deletedContacts'] = FALSE;
    }
    self::tasks();
    $tasks = array();
    if ($params['deletedContacts']) {
      if (CRM_Core_Permission::check('access deleted contacts')) {
        $tasks[self::RESTORE] = self::$_tasks[self::RESTORE]['title'];
        if (CRM_Core_Permission::check('delete contacts')) {
          $tasks[self::DELETE_PERMANENTLY] = self::$_tasks[self::DELETE_PERMANENTLY]['title'];
        }
      }
    }
    elseif ($permission == CRM_Core_Permission::EDIT) {
      $tasks = self::taskTitles();
    }
    else {
      $tasks = array(
        self::TASK_EXPORT => self::$_tasks[self::TASK_EXPORT]['title'],
        self::TASK_EMAIL => self::$_tasks[self::TASK_EMAIL]['title'],
        self::LABEL_CONTACTS => self::$_tasks[self::LABEL_CONTACTS]['title'],
      );

      foreach ([
        self::MAP_CONTACTS,
        self::CREATE_MAILING,
        self::TASK_SMS
      ] as $task) {
        if (isset(self::$_tasks[$task]) &&
          !empty(self::$_tasks[$task]['title'])
        ) {
          $tasks[$task] = self::$_tasks[$task]['title'];
        }
      }
    }

    $tasks = parent::corePermissionedTaskTitles($tasks, $permission, $params);
    return $tasks;
  }

  /**
   * @param $value
   *
   * @return array
   */
  public static function getTask($value) {
    self::tasks();

    if (!CRM_Utils_Array::value($value, self::$_tasks)) {
      // make it the print task by default
      $value = self::TASK_PRINT;
    }
    return parent::getTask($value);
  }

}
