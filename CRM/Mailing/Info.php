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
 * This class introduces component to the system and provides all the
 * information about it. It needs to extend CRM_Core_Component_Info
 * abstract class.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class CRM_Mailing_Info extends CRM_Core_Component_Info {

  // docs inherited from interface
  protected $keyword = 'mailing';


  // docs inherited from interface
  /**
   * @return array
   */
  public function getInfo() {
    return array(
      'name' => 'CiviMail',
      'translatedName' => ts('CiviMail'),
      'title' => 'CiviCRM Mailing Engine',
      'search' => 1,
      'showActivitiesInCore' => 1,
    );
  }

  public function getAngularModules() {
    $result = array();
    $result['crmMailing'] = array(
      'ext' => 'civicrm',
      'js' => array('js/angular-newMailing.js'),
    );

    $civiMails = civicrm_api3('Mailing', 'get', array());
    $campNames = civicrm_api3('Campaign', 'get', array());
    $mailStatus = civicrm_api3('MailingJob', 'get', array());
		$groupNames = civicrm_api3('Group', 'get', array());
		$headerfooterList = civicrm_api3('MailingComponent', 'get', array());
		$mesTemplate = civicrm_api3('MessageTemplate', 'get', array( 'sequential' => 1,
			'return' => "msg_title",
			'id' => array('>' => 58),)
			);
				
    CRM_Core_Resources::singleton()->addSetting(array(
      'crmMailing' => array(
        'civiMails' => array_values($civiMails['values']),
        'campNames' => array_values($campNames['values']),
        'mailStatus' => array_values($mailStatus['values']),
        'groupNames' => array_values($groupNames['values']),
        'headerfooterList' => array_values($headerfooterList['values']),
        'mesTemplate' => array_values($mesTemplate['values']),
        ),
      ));
    return $result;
  }

  /**
   * @return bool
   */
  static function workflowEnabled() {
    $config = CRM_Core_Config::singleton();

    // early exit, since not true for most
    if (!$config->userSystem->is_drupal ||
      !function_exists('module_exists')
    ) {
      return FALSE;
    }

    if (!module_exists('rules')) {
      return FALSE;
    }

    $enableWorkflow = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
      'civimail_workflow',
      NULL,
      FALSE
    );

    return ($enableWorkflow &&
      $config->userSystem->is_drupal
    ) ? TRUE : FALSE;
  }

  // docs inherited from interface
  /**
   * @param bool $getAllUnconditionally
   *
   * @return array
   */
  public function getPermissions($getAllUnconditionally = FALSE) {
    $permissions = array(
      'access CiviMail',
      'access CiviMail subscribe/unsubscribe pages',
      'delete in CiviMail',
      'view public CiviMail content',
    );

    if (self::workflowEnabled() || $getAllUnconditionally) {
      $permissions[] = 'create mailings';
      $permissions[] = 'schedule mailings';
      $permissions[] = 'approve mailings';
    }

    return $permissions;
  }


  // docs inherited from interface
  /**
   * @return null
   */
  public function getUserDashboardElement() {
    // no dashboard element for this component
    return NULL;
  }

  /**
   * @return null
   */
  public function getUserDashboardObject() {
    // no dashboard element for this component
    return NULL;
  }

  // docs inherited from interface
  /**
   * @return array
   */
  public function registerTab() {
    return array(
      'title' => ts('Mailings'),
      'id' => 'mailing',
      'url' => 'mailing',
      'weight' => 45,
    );
  }

  // docs inherited from interface
  /**
   * @return array
   */
  public function registerAdvancedSearchPane() {
    return array('title' => ts('Mailings'),
      'weight' => 20,
    );
  }

  // docs inherited from interface
  /**
   * @return null
   */
  public function getActivityTypes() {
    return NULL;
  }

  // add shortcut to Create New
  /**
   * @param $shortCuts
   */
  public function creatNewShortcut(&$shortCuts) {}
}

