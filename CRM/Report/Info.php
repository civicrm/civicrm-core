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
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * $Id$
 *
 */
class CRM_Report_Info extends CRM_Core_Component_Info {

  /**
   * @var string
   * @inheritDoc
   */
  protected $keyword = 'report';

  /**
   * @inheritDoc
   * Provides base information about the component.
   * Needs to be implemented in component's information
   * class.
   *
   * @return array
   *   collection of required component settings
   */
  public function getInfo() {
    return [
      'name' => 'CiviReport',
      'translatedName' => ts('CiviReport'),
      'title' => ts('CiviCRM Report Engine'),
      'search' => 0,
      'showActivitiesInCore' => 1,
    ];
  }

  /**
   * @inheritDoc
   * Provides permissions that are used by component.
   * Needs to be implemented in component's information
   * class.
   *
   * NOTE: if using conditionally permission return,
   * implementation of $getAllUnconditionally is required.
   *
   * @param bool $getAllUnconditionally
   * @param bool $descriptions
   *   Whether to return permission descriptions
   *
   * @return array|null
   *   collection of permissions, null if none
   */
  public function getPermissions($getAllUnconditionally = FALSE, $descriptions = FALSE) {
    $permissions = [
      'access CiviReport' => [
        ts('access CiviReport'),
        ts('View reports'),
      ],
      'access Report Criteria' => [
        ts('access Report Criteria'),
        ts('Change report search criteria'),
      ],
      'save Report Criteria' => [
        ts('save Report Criteria'),
        ts('Save report search criteria'),
      ],
      'administer private reports' => [
        ts('administer private reports'),
        ts('Edit all private reports'),
      ],
      'administer reserved reports' => [
        ts('administer reserved reports'),
        ts('Edit all reports that have been marked as reserved'),
      ],
      'administer Reports' => [
        ts('administer Reports'),
        ts('Manage report templates'),
      ],
      'view report sql' => [
        ts('view report sql'),
        ts('View sql used in CiviReports'),
      ],
    ];

    if (!$descriptions) {
      foreach ($permissions as $name => $attr) {
        $permissions[$name] = array_shift($attr);
      }
    }

    return $permissions;
  }

  /**
   * @inheritDoc
   * Provides information about user dashboard element
   * offered by this component.
   *
   * @return array|null
   *   collection of required dashboard settings,
   *                    null if no element offered
   */
  public function getUserDashboardElement() {
    // no dashboard element for this component
    return NULL;
  }

  /**
   * Provides component's user dashboard page object.
   *
   * @return mixed
   *   component's User Dashboard applet object
   */

  /**
   * @return mixed
   */
  public function getUserDashboardObject() {
    // no dashboard element for this component
    return NULL;
  }

  /**
   * @inheritDoc
   * Provides information about user dashboard element
   * offered by this component.
   *
   * @return array|null
   *   collection of required dashboard settings,
   *                    null if no element offered
   */

  /**
   * @return array|null
   */
  public function registerTab() {
    // this component doesn't use contact record tabs
    return NULL;
  }

  /**
   * @inheritDoc
   * @return string
   */
  public function getIcon() {
    return 'crm-i fa-table';
  }

  /**
   * @inheritDoc
   * Provides information about advanced search pane
   * offered by this component.
   *
   * @return array|null
   *   collection of required pane settings,
   *                    null if no element offered
   */

  /**
   * @return array|null
   */
  public function registerAdvancedSearchPane() {
    // this component doesn't use advanced search
    return NULL;
  }

  /**
   * @inheritDoc
   * Provides potential activity types that this
   * component might want to register in activity history.
   * Needs to be implemented in component's information
   * class.
   *
   * @return array|null
   *   collection of activity types
   */

  /**
   * @return array|null
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
