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
 * $Id$
 *
 */

/**
 * Helper class to build navigation links
 */
class CRM_Event_Form_ManageEvent_TabHeader {

  /**
   * @param CRM_Event_Form_ManageEvent $form
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public static function build(&$form) {
    $tabs = $form->get('tabHeader');
    if (!$tabs || empty($_GET['reset'])) {
      $tabs = self::process($form);
      $form->set('tabHeader', $tabs);
    }
    $form->assign_by_ref('tabHeader', $tabs);
    CRM_Core_Resources::singleton()
      ->addScriptFile('civicrm', 'templates/CRM/common/TabHeader.js', 1, 'html-header')
      ->addSetting([
        'tabSettings' => [
          'active' => self::getCurrentTab($tabs),
        ],
      ]);
    CRM_Event_Form_ManageEvent::addProfileEditScripts();
    return $tabs;
  }

  /**
   * @param CRM_Event_Form_ManageEvent $form
   *
   * @return array
   * @throws Exception
   */
  public static function process(&$form) {
    if ($form->getVar('_id') <= 0) {
      return NULL;
    }

    $default = [
      'link' => NULL,
      'valid' => TRUE,
      'active' => TRUE,
      'current' => FALSE,
      'class' => 'ajaxForm',
    ];

    $tabs = [];
    $tabs['settings'] = ['title' => ts('Info and Settings'), 'class' => 'ajaxForm livePage'] + $default;
    $tabs['location'] = ['title' => ts('Event Location')] + $default;
    $tabs['fee'] = ['title' => ts('Fees')] + $default;
    $tabs['registration'] = ['title' => ts('Online Registration')] + $default;
    // @fixme I don't understand the event permissions check here - can we just get rid of it?
    $permissions = CRM_Event_BAO_Event::getAllPermissions();
    if (CRM_Core_Permission::check('administer CiviCRM') || !empty($permissions[CRM_Core_Permission::EDIT])) {
      $tabs['reminder'] = ['title' => ts('Schedule Reminders'), 'class' => 'livePage'] + $default;
    }
    $tabs['conference'] = ['title' => ts('Conference Slots')] + $default;
    $tabs['friend'] = ['title' => ts('Tell a Friend')] + $default;
    $tabs['pcp'] = ['title' => ts('Personal Campaigns')] + $default;
    $tabs['repeat'] = ['title' => ts('Repeat')] + $default;

    // Repeat tab must refresh page when switching repeat mode so js & vars will get set-up
    if (!$form->_isRepeatingEvent) {
      unset($tabs['repeat']['class']);
    }

    // check if we're in shopping cart mode for events
    $enableCart = Civi::settings()->get('enable_cart');
    if (!$enableCart) {
      unset($tabs['conference']);
    }

    $eventID = $form->getVar('_id');
    if ($eventID) {
      // disable tabs based on their configuration status
      $eventNameMapping = CRM_Utils_Array::first(CRM_Core_BAO_ActionSchedule::getMappings([
        'id' => CRM_Event_ActionMapping::EVENT_NAME_MAPPING_ID,
      ]));
      $sql = "
SELECT     e.loc_block_id as is_location, e.is_online_registration, e.is_monetary, taf.is_active, pcp.is_active as is_pcp, sch.id as is_reminder, re.id as is_repeating_event
FROM       civicrm_event e
LEFT JOIN  civicrm_tell_friend taf ON ( taf.entity_table = 'civicrm_event' AND taf.entity_id = e.id )
LEFT JOIN  civicrm_pcp_block pcp   ON ( pcp.entity_table = 'civicrm_event' AND pcp.entity_id = e.id )
LEFT JOIN  civicrm_action_schedule sch ON ( sch.mapping_id = %2 AND sch.entity_value = %1 )
LEFT JOIN  civicrm_recurring_entity re ON ( e.id = re.entity_id AND re.entity_table = 'civicrm_event' )
WHERE      e.id = %1
";
      //Check if repeat is configured
      $eventHasParent = CRM_Core_BAO_RecurringEntity::getParentFor($eventID, 'civicrm_event');
      $params = [
        1 => [$eventID, 'Integer'],
        2 => [$eventNameMapping->getId(), 'Integer'],
      ];
      $dao = CRM_Core_DAO::executeQuery($sql, $params);
      if (!$dao->fetch()) {
        CRM_Core_Error::fatal();
      }
      if (!$dao->is_location) {
        $tabs['location']['valid'] = FALSE;
      }
      if (!$dao->is_online_registration) {
        $tabs['registration']['valid'] = FALSE;
      }
      if (!$dao->is_monetary) {
        $tabs['fee']['valid'] = FALSE;
      }
      if (!$dao->is_active) {
        $tabs['friend']['valid'] = FALSE;
      }
      if (!$dao->is_pcp) {
        $tabs['pcp']['valid'] = FALSE;
      }
      if (!$dao->is_reminder) {
        $tabs['reminder']['valid'] = FALSE;
      }
      if (!$dao->is_repeating_event) {
        $tabs['repeat']['valid'] = FALSE;
      }
    }

    // see if any other modules want to add any tabs
    // note: status of 'valid' flag of any injected tab, needs to be taken care in the hook implementation.
    CRM_Utils_Hook::tabset('civicrm/event/manage', $tabs,
      ['event_id' => $eventID]);

    $fullName = $form->getVar('_name');
    $className = CRM_Utils_String::getClassName($fullName);
    $new = '';

    // hack for special cases.
    switch ($className) {
      case 'Event':
        $attributes = $form->getVar('_attributes');
        $class = CRM_Utils_Request::retrieveComponent($attributes);
        break;

      case 'EventInfo':
        $class = 'settings';
        break;

      case 'ScheduleReminders':
        $class = 'reminder';
        break;

      default:
        $class = strtolower($className);
        break;
    }

    if (array_key_exists($class, $tabs)) {
      $tabs[$class]['current'] = TRUE;
      $qfKey = $form->get('qfKey');
      if ($qfKey) {
        $tabs[$class]['qfKey'] = "&qfKey={$qfKey}";
      }
    }

    if ($eventID) {
      $reset = !empty($_GET['reset']) ? 'reset=1&' : '';

      foreach ($tabs as $key => $value) {
        if (!isset($tabs[$key]['qfKey'])) {
          $tabs[$key]['qfKey'] = NULL;
        }

        $action = 'update';
        if ($key == 'reminder') {
          $action = 'browse';
        }

        $link = "civicrm/event/manage/{$key}";
        $query = "{$reset}action={$action}&id={$eventID}&component=event{$tabs[$key]['qfKey']}";

        $tabs[$key]['link'] = (isset($value['link']) ? $value['link'] :
          CRM_Utils_System::url($link, $query));
      }
    }

    return $tabs;
  }

  /**
   * @param CRM_Event_Form_ManageEvent $form
   */
  public static function reset(&$form) {
    $tabs = self::process($form);
    $form->set('tabHeader', $tabs);
  }

  /**
   * @param $tabs
   *
   * @return int|string
   */
  public static function getCurrentTab($tabs) {
    static $current = FALSE;

    if ($current) {
      return $current;
    }

    if (is_array($tabs)) {
      foreach ($tabs as $subPage => $pageVal) {
        if (CRM_Utils_Array::value('current', $pageVal) === TRUE) {
          $current = $subPage;
          break;
        }
      }
    }

    $current = $current ? $current : 'settings';
    return $current;
  }

}
