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
 * Class to represent the actions that can be performed on a group of contacts used by the search forms.
 */
class CRM_Contact_Task extends CRM_Core_Task {

  /**
   * Contact tasks
   */
  const
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

  /**
   * @var string
   */
  public static $objectType = 'contact';

  public static function tasks() {
    if (!self::$_tasks) {
      self::$_tasks = [
        self::GROUP_ADD => [
          'title' => ts('Group - add contacts'),
          'class' => 'CRM_Contact_Form_Task_AddToGroup',
          'url' => 'civicrm/task/add-to-group',
          'icon' => 'fa-user-plus',
        ],
        self::GROUP_REMOVE => [
          'title' => ts('Group - remove contacts'),
          'class' => 'CRM_Contact_Form_Task_RemoveFromGroup',
          'url' => 'civicrm/task/remove-from-group',
          'icon' => 'fa-user-plus',
        ],
        self::TAG_ADD => [
          'title' => ts('Tag - add to contacts'),
          'class' => 'CRM_Contact_Form_Task_AddToTag',
          'url' => 'civicrm/task/add-to-tag',
          'icon' => 'fa-tags',
        ],
        self::TAG_REMOVE => [
          'title' => ts('Tag - remove from contacts'),
          'class' => 'CRM_Contact_Form_Task_RemoveFromTag',
          'url' => 'civicrm/task/remove-from-tag',
          'icon' => 'fa-tag',
        ],
        self::TASK_EXPORT => [
          'title' => ts('Export contacts'),
          'class' => [
            'CRM_Contact_Export_Form_Select',
            'CRM_Contact_Export_Form_Map',
          ],
          'result' => FALSE,
        ],
        self::TASK_EMAIL => [
          'title' => ts('Email - send now (to %1 or less)', [
            1 => Civi::settings()
              ->get('simple_mail_limit'),
          ]),
          'class' => 'CRM_Contact_Form_Task_Email',
          'result' => TRUE,
          'url' => 'civicrm/task/send-email',
          'icon' => 'fa-paper-plane-o',
        ],
        self::TASK_DELETE => [
          'title' => ts('Delete contacts'),
          'class' => 'CRM_Contact_Form_Task_Delete',
          'result' => FALSE,
          'url' => 'civicrm/task/delete-contact',
          'icon' => 'fa-trash',
        ],
        self::RECORD_CONTACTS => [
          'title' => ts('Add activity'),
          'class' => 'CRM_Activity_Form_Activity',
          'icon' => 'fa-tasks',
          'url' => 'civicrm/task/add-activity?action=add&context=search',
        ],
        self::SAVE_SEARCH => [
          'title' => ts('Group - create smart group'),
          'class' => 'CRM_Contact_Form_Task_SaveSearch',
          'result' => TRUE,
        ],
        self::SAVE_SEARCH_UPDATE => [
          'title' => ts('Group - update smart group'),
          'class' => 'CRM_Contact_Form_Task_SaveSearch_Update',
          'result' => TRUE,
        ],
        self::TASK_PRINT => [
          'title' => ts('Print selected rows'),
          'class' => 'CRM_Contact_Form_Task_Print',
          'result' => FALSE,
        ],
        self::LABEL_CONTACTS => [
          'title' => ts('Mailing labels - print'),
          'class' => 'CRM_Contact_Form_Task_Label',
          'result' => TRUE,
          'url' => 'civicrm/task/make-mailing-label',
          'icon' => 'fa-print',
        ],
        self::BATCH_UPDATE => [
          'title' => ts('Update multiple contacts'),
          'class' => [
            'CRM_Contact_Form_Task_PickProfile',
            'CRM_Contact_Form_Task_Batch',
          ],
          'result' => TRUE,
        ],
        self::PDF_LETTER => [
          'title' => ts('Print/merge document'),
          'class' => 'CRM_Contact_Form_Task_PDF',
          'result' => TRUE,
          'url' => 'civicrm/task/print-document',
          'icon' => 'fa-file-pdf-o',
        ],
        self::EMAIL_UNHOLD => [
          'title' => ts('Email - unhold addresses'),
          'class' => 'CRM_Contact_Form_Task_Unhold',
          'url' => 'civicrm/task/unhold-email',
          'icon' => 'fa-unlock',
        ],
        self::COMMUNICATION_PREFS => [
          'title' => ts('Communication preferences - alter'),
          'class' => 'CRM_Contact_Form_Task_AlterPreferences',
          'url' => 'civicrm/task/alter-contact-preference',
          'icon' => 'fa-check-square-o',
        ],
        self::RESTORE => [
          'title' => ts('Restore contacts from trash'),
          'class' => 'CRM_Contact_Form_Task_Delete',
          'url' => 'civicrm/task/restore-contact',
          'result' => FALSE,
        ],
        self::DELETE_PERMANENTLY => [
          'title' => ts('Delete permanently'),
          'url' => 'civicrm/task/delete-permanently',
          'class' => 'CRM_Contact_Form_Task_Delete',
          'result' => FALSE,
        ],
      ];

      //CRM-16329, if SMS provider is configured show sms action.
      $providersCount = CRM_SMS_BAO_SmsProvider::activeProviderCount();
      if ($providersCount && CRM_Core_Permission::check('send SMS')) {
        self::$_tasks[self::TASK_SMS] = [
          'title' => ts('SMS - schedule/send'),
          'class' => 'CRM_Contact_Form_Task_SMS',
          'result' => TRUE,
        ];
      }

      if (CRM_Contact_BAO_ContactType::isActive('Individual')) {
        $label = CRM_Contact_BAO_ContactType::getLabel('Individual');
        self::$_tasks[self::INDIVIDUAL_CONTACTS] = [
          'title' => ts('Add relationship - to %1',
            [1 => $label]
          ),
          'class' => 'CRM_Contact_Form_Task_AddToIndividual',
        ];
      }

      if (CRM_Contact_BAO_ContactType::isActive('Household')) {
        $label = CRM_Contact_BAO_ContactType::getLabel('Household');
        self::$_tasks[self::HOUSEHOLD_CONTACTS] = [
          'title' => ts('Add relationship - to %1',
            [1 => $label]
          ),
          'class' => 'CRM_Contact_Form_Task_AddToHousehold',
        ];
      }

      if (CRM_Contact_BAO_ContactType::isActive('Organization')) {
        $label = CRM_Contact_BAO_ContactType::getLabel('Organization');
        self::$_tasks[self::ORGANIZATION_CONTACTS] = [
          'title' => ts('Add relationship - to %1',
            [1 => $label]
          ),
          'class' => 'CRM_Contact_Form_Task_AddToOrganization',
        ];
      }

      if (CRM_Core_Permission::check('merge duplicate contacts')) {
        self::$_tasks[self::MERGE_CONTACTS] = [
          'title' => ts('Merge contacts'),
          'class' => 'CRM_Contact_Form_Task_Merge',
          'result' => TRUE,
        ];
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
        self::$_tasks[self::MAP_CONTACTS] = [
          'title' => ts('Map contacts'),
          'class' => 'CRM_Contact_Form_Task_Map',
          'result' => FALSE,
          'url' => 'civicrm/contact/map',
          'icon' => 'fa-map',
        ];
      }

      if (CRM_Core_Permission::access('CiviEvent')) {
        self::$_tasks[self::ADD_EVENT] = [
          'title' => ts('Register participants for event'),
          'class' => 'CRM_Event_Form_Task_Register',
        ];
      }

      if (CRM_Core_Permission::access('CiviMail')
        || (CRM_Mailing_Info::workflowEnabled() && CRM_Core_Permission::check('create mailings'))
      ) {
        self::$_tasks[self::CREATE_MAILING] = [
          'title' => ts('Email - schedule/send via CiviMail'),
          'class' => 'CRM_Mailing_Form_Task_AdhocMailing',
          'result' => FALSE,
        ];
      }

      if (CRM_Core_Permission::access('CiviCase')) {
        self::$_tasks[self::ADD_TO_CASE] = [
          'title' => ts('Add to case as role'),
          'class' => 'CRM_Case_Form_AddToCaseAsRole',
          'result' => FALSE,
        ];
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
  public static function permissionedTaskTitles($permission, $params = []) {
    if (!isset($params['deletedContacts'])) {
      $params['deletedContacts'] = FALSE;
    }
    self::tasks();
    $tasks = [];
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
      $tasks = [
        self::TASK_EXPORT => self::$_tasks[self::TASK_EXPORT]['title'],
        self::TASK_EMAIL => self::$_tasks[self::TASK_EMAIL]['title'],
        self::LABEL_CONTACTS => self::$_tasks[self::LABEL_CONTACTS]['title'],
      ];

      foreach ([self::MAP_CONTACTS, self::CREATE_MAILING, self::TASK_SMS] as $task) {
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

    if (empty(self::$_tasks[$value])) {
      // make it the print task by default
      $value = self::TASK_PRINT;
    }
    return parent::getTask($value);
  }

}
