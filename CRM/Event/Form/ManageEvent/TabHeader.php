<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * Helper class to build navigation links
 */
class CRM_Event_Form_ManageEvent_TabHeader {

  static function build(&$form) {
    $tabs = $form->get('tabHeader');
    if (!$tabs || !CRM_Utils_Array::value('reset', $_GET)) {
      $tabs = self::process($form);
      $form->set('tabHeader', $tabs);
    }
    $form->assign_by_ref('tabHeader', $tabs);
    $selectedTab = self::getCurrentTab($tabs);
    $form->assign_by_ref('selectedTab', $selectedTab);
    return $tabs;
  }

  static function process(&$form) {
    if ($form->getVar('_id') <= 0) {
      return NULL;
    }

    $tabs = array(
      'settings' => array('title' => ts('Info and Settings'),
        'link' => NULL,
        'valid' => FALSE,
        'active' => FALSE,
        'current' => FALSE,
      ),
      'location' => array('title' => ts('Event Location'),
        'link' => NULL,
        'valid' => FALSE,
        'active' => FALSE,
        'current' => FALSE,
      ),
      'fee' => array('title' => ts('Fees'),
        'link' => NULL,
        'valid' => FALSE,
        'active' => FALSE,
        'current' => FALSE,
      ),
      'registration' => array('title' => ts('Online Registration'),
        'link' => NULL,
        'valid' => FALSE,
        'active' => FALSE,
        'current' => FALSE,
      ),
      'reminder' => array('title' => ts('Schedule Reminders'),
        'link' => NULL,
        'valid' => FALSE,
        'active' => FALSE,
        'current' => FALSE,
      ),
      'conference' => array('title' => ts('Conference Slots'),
        'link' => NULL,
        'valid' => FALSE,
        'active' => FALSE,
        'current' => FALSE,
      ),
      'friend' => array('title' => ts('Tell a Friend'),
        'link' => NULL,
        'valid' => FALSE,
        'active' => FALSE,
        'current' => FALSE,
      ),
      'pcp' => array('title' => ts('Personal Campaigns'),
        'link' => NULL,
        'valid' => FALSE,
        'active' => FALSE,
        'current' => FALSE,
      )
    );

    // check if we're in shopping cart mode for events
    $enableCart = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::EVENT_PREFERENCES_NAME,
      'enable_cart'
    );

    if (!$enableCart) {
      unset($tabs['conference']);
    }

    $eventID = $form->getVar('_id');

    $fullName  = $form->getVar('_name');
    $className = CRM_Utils_String::getClassName($fullName);
    $new       = '';
    // hack for special cases.
    switch ($className) {
      case 'Event':
        $attributes = $form->getVar('_attributes');
        $class = strtolower(basename(CRM_Utils_Array::value('action', $attributes)));
        break;

      case 'ScheduleReminders':
        $class = 'reminder';
        $new = CRM_Utils_Array::value('new', $_GET) ? '&new=1' : '';
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
      $reset = CRM_Utils_Array::value('reset', $_GET) ? 'reset=1&' : '';

      foreach ($tabs as $key => $value) {
        if (!isset($tabs[$key]['qfKey'])) {
          $tabs[$key]['qfKey'] = NULL;
        }

        $tabs[$key]['link'] = CRM_Utils_System::url("civicrm/event/manage/{$key}",
          "{$reset}action=update&snippet=5&id={$eventID}&component=event{$new}{$tabs[$key]['qfKey']}"
        );
        $tabs[$key]['active'] = $tabs[$key]['valid'] = TRUE;
      }

      // retrieve info about paid event, tell a friend and online reg
      $sql = "
SELECT     e.is_online_registration, e.is_monetary, taf.is_active
FROM       civicrm_event e
LEFT JOIN  civicrm_tell_friend taf ON ( taf.entity_table = 'civicrm_event' AND taf.entity_id = e.id )
WHERE      e.id = %1
";
      $params = array(1 => array($eventID, 'Integer'));
      $dao = CRM_Core_DAO::executeQuery($sql, $params);
      if (!$dao->fetch()) {
        CRM_Core_Error::fatal();
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

      //calculate if the reminder has been configured for this event
    }
    return $tabs;
  }

  static function reset(&$form) {
    $tabs = self::process($form);
    $form->set('tabHeader', $tabs);
  }

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

