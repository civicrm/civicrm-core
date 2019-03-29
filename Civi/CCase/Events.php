<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
namespace Civi\CCase;

/**
 * Class Events
 *
 * @package Civi\CCase
 */
class Events {
  /**
   * @var array (int $caseId => bool $active) list of cases for which we are actively firing case-change event
   *
   * We do not want to fire case-change events recursively.
   */
  static $isActive = [];

  /**
   * Following a change to an activity or case, fire the case-change event.
   *
   * @param \Civi\Core\Event\PostEvent $event
   * @throws \CRM_Core_Exception
   */
  public static function fireCaseChange(\Civi\Core\Event\PostEvent $event) {
    // Activities can be linked to multiple cases, so $caseIds might be an array or an int
    $caseIds = NULL;
    switch ($event->entity) {
      case 'Activity':
        if (!empty($event->object->case_id)) {
          $caseIds = $event->object->case_id;
        }
        break;

      case 'Case':
        // by the time we get the post-delete event, the record is gone, so
        // there's nothing to analyze
        if ($event->action != 'delete') {
          $caseIds = $event->id;
        }
        break;

      default:
        throw new \CRM_Core_Exception("CRM_Case_Listener does not support entity {$event->entity}");
    }

    if ($caseIds) {
      foreach ((array) $caseIds as $caseId) {
        if (!isset(self::$isActive[$caseId])) {
          $tx = new \CRM_Core_Transaction();
          \CRM_Core_Transaction::addCallback(
            \CRM_Core_Transaction::PHASE_POST_COMMIT,
            [__CLASS__, 'fireCaseChangeForRealz'],
            [$caseId],
            "Civi_CCase_Events::fire::{$caseId}"
          );
        }
      }
    }
  }

  /**
   * Fire case change hook
   *
   * @param int|array $caseIds
   */
  public static function fireCaseChangeForRealz($caseIds) {
    foreach ((array) $caseIds as $caseId) {
      if (!isset(self::$isActive[$caseId])) {
        $tx = new \CRM_Core_Transaction();
        self::$isActive[$caseId] = 1;
        $analyzer = new \Civi\CCase\Analyzer($caseId);
        \CRM_Utils_Hook::caseChange($analyzer);
        unset(self::$isActive[$caseId]);
        unset($tx);
      }
    }
  }

  /**
   * Find any extra listeners declared in XML and pass the event along to them.
   *
   * @param \Civi\CCase\Event\CaseChangeEvent $event
   */
  public static function delegateToXmlListeners(\Civi\CCase\Event\CaseChangeEvent $event) {
    $p = new \CRM_Case_XMLProcessor_Process();
    $listeners = $p->getListeners($event->analyzer->getCaseType());
    foreach ($listeners as $listener) {
      /** @var $listener \Civi\CCase\CaseChangeListener */
      $listener->onCaseChange($event);
    }
  }

}
