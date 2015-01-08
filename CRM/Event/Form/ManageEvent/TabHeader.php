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
 * Helper class to build navigation links
 */
class CRM_Event_Form_ManageEvent_TabHeader {

  /**
   * @param $form
   *
   * @return array
   */
  static function build(&$form) {
    $tabs = $form->get('tabHeader');
    if (!$tabs || empty($_GET['reset'])) {
      $tabs = self::process($form);
      $form->set('tabHeader', $tabs);
    }
    $form->assign_by_ref('tabHeader', $tabs);
    CRM_Core_Resources::singleton()
      ->addScriptFile('civicrm', 'templates/CRM/common/TabHeader.js', 1, 'html-header')
      ->addSetting(array('tabSettings' => array(
        'active' => self::getCurrentTab($tabs),
      )));
    CRM_Event_Form_ManageEvent::addProfileEditScripts();
    return $tabs;
  }

  /**
   * @param $form
   *
   * @return array
   * @throws Exception
   */
  static function process(&$form) {
    if ($form->getVar('_id') <= 0) {
      return NULL;
    }

    $default = array(
      'link' => NULL,
      'valid' => TRUE,
      'active' => TRUE,
      'current' => FALSE,
      'class' => 'ajaxForm',
    );

    $tabs = array();
    $tabs['settings'] = array('title' => ts('Info and Settings'), 'class' => 'ajaxForm livePage') + $default;
    $tabs['location'] = array('title' => ts('Event Location')) + $default;
    $tabs['fee'] = array('title' => ts('Fees')) + $default;
    $tabs['registration'] = array('title' => ts('Online Registration')) + $default;
    if (CRM_Core_Permission::check('administer CiviCRM')) {
      $tabs['reminder'] = array('title' => ts('Schedule Reminders'), 'class' => 'livePage') + $default;
    }
    $tabs['conference'] = array('title' => ts('Conference Slots')) + $default;
    $tabs['friend'] = array('title' => ts('Tell a Friend')) + $default;
    $tabs['pcp'] = array('title' => ts('Personal Campaigns')) + $default;


    // check if we're in shopping cart mode for events
    $enableCart = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::EVENT_PREFERENCES_NAME,
      'enable_cart'
    );
    if (!$enableCart) {
      unset($tabs['conference']);
    }

    $eventID = $form->getVar('_id');
    if ($eventID) {
      // disable tabs based on their configuration status
      $sql = "
SELECT     e.loc_block_id as is_location, e.is_online_registration, e.is_monetary, taf.is_active, pcp.is_active as is_pcp, sch.id as is_reminder
FROM       civicrm_event e
LEFT JOIN  civicrm_tell_friend taf ON ( taf.entity_table = 'civicrm_event' AND taf.entity_id = e.id )
LEFT JOIN  civicrm_pcp_block pcp   ON ( pcp.entity_table = 'civicrm_event' AND pcp.entity_id = e.id )
LEFT JOIN  civicrm_action_mapping  map ON ( map.entity_value = 'civicrm_event' )
LEFT JOIN  civicrm_action_schedule sch ON ( sch.mapping_id = map.id AND sch.entity_value = %1 )
WHERE      e.id = %1
";
      $params = array(1 => array($eventID, 'Integer'));
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
    }

    // see if any other modules want to add any tabs
    // note: status of 'valid' flag of any injected tab, needs to be taken care in the hook implementation.
    CRM_Utils_Hook::tabset('civicrm/event/manage', $tabs,
      array('event_id' => $eventID));

    $fullName  = $form->getVar('_name');
    $className = CRM_Utils_String::getClassName($fullName);
    $new       = '';
    // hack for special cases.
    switch ($className) {
      case 'Event':
        $attributes = $form->getVar('_attributes');
        $class = strtolower(basename(CRM_Utils_Array::value('action', $attributes)));
        break;

      case 'EventInfo':
        $class = 'settings';
        break;

      case 'ScheduleReminders':
        $class = 'reminder';
        $new = !empty($_GET['new']) ? '&new=1' : '';
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

        $tabs[$key]['link'] = CRM_Utils_System::url("civicrm/event/manage/{$key}",
          "{$reset}action=update&id={$eventID}&component=event{$new}{$tabs[$key]['qfKey']}"
        );
      }
    }

    return $tabs;
  }

  /**
   * @param $form
   */
  static function reset(&$form) {
    $tabs = self::process($form);
    $form->set('tabHeader', $tabs);
  }

  /**
   * @param $tabs
   *
   * @return int|string
   */
  static function getCurrentTab($tabs) {
    static $current = FALSE;

    if ($current) {
      return $current;
    }

    if (is_array($tabs)) {
      foreach ($tabs as $subPage => $pageVal) {
        if ($pageVal['current'] === TRUE) {
          $current = $subPage;
          break;
        }
      }
    }

    $current = $current ? $current : 'settings';
    return $current;
  }
}

