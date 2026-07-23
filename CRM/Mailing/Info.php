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
 * This class introduces component to the system and provides all the
 * information about it. It needs to extend CRM_Core_Component_Info
 * abstract class.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Mailing_Info extends CRM_Core_Component_Info {

  /**
   * @var string
   * @inheritDoc
   */
  protected $keyword = 'mailing';

  /**
   * @inheritDoc
   * @return array
   */
  public function getInfo() {
    return [
      'name' => 'CiviMail',
      'translatedName' => ts('CiviMail'),
      'title' => ts('CiviCRM Mailing Engine'),
      'search' => 1,
      'showActivitiesInCore' => 1,
    ];
  }

  /**
   * @return bool
   */
  public static function workflowEnabled() {
    $config = CRM_Core_Config::singleton();
    return $config->userSystem->mailingWorkflowIsEnabled();
  }

  /**
   * @inheritDoc
   */
  public function getPermissions(): array {
    $permissions = [
      'access CiviMail' => [
        'label' => ts('access CiviMail'),
      ],
      'access CiviMail subscribe/unsubscribe pages' => [
        'label' => ts('access CiviMail subscribe/unsubscribe pages'),
        'description' => ts('Subscribe/unsubscribe from mailing list group'),
      ],
      'delete in CiviMail' => [
        'label' => ts('delete in CiviMail'),
        'description' => ts('Delete Mailing'),
      ],
      'view public CiviMail content' => [
        'label' => ts('view public CiviMail content'),
      ],
    ];
    // Workflow permissions
    $permissions['create mailings'] = [
      'label' => ts('create mailings'),
      'disabled' => !self::workflowEnabled(),
    ];
    $permissions['schedule mailings'] = [
      'label' => ts('schedule mailings'),
      'disabled' => !self::workflowEnabled(),
    ];
    $permissions['approve mailings'] = [
      'label' => ts('approve mailings'),
      'disabled' => !self::workflowEnabled(),
    ];
    return $permissions;
  }

  /**
   * @inheritDoc
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

  /**
   * @inheritDoc
   * @return array
   */
  public function registerTab() {
    return [
      'title' => ts('Mailings'),
      'id' => 'mailing',
      'url' => 'mailing',
      'weight' => 45,
    ];
  }

  /**
   * @inheritDoc
   * @return string
   */
  public function getIcon() {
    return 'crm-i fa-envelope-o';
  }

  /**
   * @inheritDoc
   * @return array
   */
  public function registerAdvancedSearchPane() {
    return [
      'title' => ts('Mailings'),
      'weight' => 20,
    ];
  }

  /**
   * @inheritDoc
   * @return null
   */
  public function getActivityTypes() {
    return NULL;
  }

  /**
   * add shortcut to Create New.
   * @param $shortCuts
   */
  public function creatNewShortcut(&$shortCuts) {
  }

}
