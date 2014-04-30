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
class CRM_Grant_Form_GrantPage_TabHeader {
  static function build(&$form) {
    $tabs = $form->get('tabHeader');
    if (!$tabs || !CRM_Utils_Array::value('reset', $_GET)) {
      $tabs = self::process($form);
      $form->set('tabHeader', $tabs);
    }
    $form->assign_by_ref('tabHeader', $tabs);
    CRM_Core_Resources::singleton()
      ->addScriptFile('civicrm', 'templates/CRM/common/TabHeader.js')
      ->addSetting(array('tabSettings' => array(
        'active' => self::getCurrentTab($tabs),
      )));
    return $tabs;
  }

  static
  function process(&$form) {
    if ($form->getVar('_id') <= 0) {
      return NULL;
    }

    $tabs = array(
      'settings' => array('title' => ts('Title'),
        'link' => NULL,
        'valid' => FALSE,
        'active' => FALSE,
        'current' => FALSE,
      ),
      'thankyou' => array('title' => ts('Receipt'),
        'link' => NULL,
        'valid' => FALSE,
        'active' => FALSE,
        'current' => FALSE,
      ),
      'custom' => array('title' => ts('Profiles'),
        'link' => NULL,
        'valid' => FALSE,
        'active' => FALSE,
        'current' => FALSE,
      ),
    );

    $grantPageId = $form->getVar('_id');
    $fullName      = $form->getVar('_name');
    $className     = CRM_Utils_String::getClassName($fullName);
      
    if ( $className ) {
        $class = strtolower($className);
    }

    if (array_key_exists($class, $tabs)) {
      $tabs[$class]['current'] = TRUE;
      $qfKey = $form->get('qfKey');
      if ($qfKey) {
        $tabs[$class]['qfKey'] = "&qfKey={$qfKey}";
      }
    }

    if ($grantPageId) {
      $reset = CRM_Utils_Array::value('reset', $_GET) ? 'reset=1&' : '';
     
      foreach ($tabs as $key => $value) {
        if (!isset($tabs[$key]['qfKey'])) {
          $tabs[$key]['qfKey'] = NULL;
        }
        $tabs[$key]['link'] = CRM_Utils_System::url("civicrm/admin/grant/{$key}",
          "{$reset}action=update&snippet=5&id={$grantPageId}{$tabs[$key]['qfKey']}"
        );
        $tabs[$key]['active'] = $tabs[$key]['valid'] = TRUE;
      
      }
  
      //get all section info.
      $grantPageInfo = CRM_Grant_BAO_GrantApplicationPage::getSectionInfo(array($grantPageId));
     

      foreach ($grantPageInfo[$grantPageId] as $section => $info) {
        if (!$info) {
          $tabs[$section]['valid'] = FALSE;
        }
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

    $current = $current ? $current : 'settings';
    return $current;
  }
}

