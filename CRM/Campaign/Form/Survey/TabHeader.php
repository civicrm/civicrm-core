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
class CRM_Campaign_Form_Survey_TabHeader {

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
    if ($form->getVar('_surveyId') <= 0) {
      return NULL;
    }

    $tabs = array(
      'main' => array('title' => ts('Main Information'),
        'link' => NULL,
        'valid' => FALSE,
        'active' => FALSE,
        'current' => FALSE,
      ),
      'questions' => array('title' => ts('Questions'),
        'link' => NULL,
        'valid' => FALSE,
        'active' => FALSE,
        'current' => FALSE,
      ),
      'results' => array('title' => ts('Results'),
        'link' => NULL,
        'valid' => FALSE,
        'active' => FALSE,
        'current' => FALSE,
      ),
    );

    $surveyID  = $form->getVar('_surveyId');
    $class     = $form->getVar('_name');
    $class     = CRM_Utils_String::getClassName($class);
    $class     = strtolower($class);

    if (array_key_exists($class, $tabs)) {
      $tabs[$class]['current'] = TRUE;
      $qfKey = $form->get('qfKey');
      if ($qfKey) {
        $tabs[$class]['qfKey'] = "&qfKey={$qfKey}";
      }
    }

    if ($surveyID) {
      $reset = CRM_Utils_Array::value('reset', $_GET) ? 'reset=1&' : '';

      foreach ($tabs as $key => $value) {
        if (!isset($tabs[$key]['qfKey'])) {
          $tabs[$key]['qfKey'] = NULL;
        }

        $tabs[$key]['link'] = CRM_Utils_System::url("civicrm/survey/configure/{$key}",
          "{$reset}action=update&snippet=5&id={$surveyID}{$tabs[$key]['qfKey']}"
        );
        $tabs[$key]['active'] = $tabs[$key]['valid'] = TRUE;
      }
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

    $current = $current ? $current : 'main';
    return $current;
  }

  static function getNextTab(&$form) {
    static $next = FALSE;
    if ($next)
      return $next;

    $tabs = $form->get('tabHeader');
    if (is_array($tabs)) {
      $current = false;
      foreach ($tabs as $subPage => $pageVal) {
        if ($current) {
          $next = $subPage;
          break;
        }
        if ($pageVal['current'] === TRUE) {
          $current = $subPage;
        }
      }
    }

    $next = $next ? $next : 'main';
    return $next;
  }
}
