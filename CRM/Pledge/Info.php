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
class CRM_Pledge_Info extends CRM_Core_Component_Info {

  /**
   * @var string
   * @inheritDoc
   */
  protected $keyword = 'pledge';

  /**
   * Provides base information about the component.
   * Needs to be implemented in component's information
   * class.
   *
   * @return array
   *   collection of required component settings
   */
  public function getInfo() {
    return [
      'name' => 'CiviPledge',
      'translatedName' => ts('CiviPledge'),
      'title' => ts('CiviCRM Pledge Engine'),
      'search' => 1,
      'showActivitiesInCore' => 1,
    ];
  }

  /**
   * @inheritDoc
   */
  public function getPermissions(): array {
    $permissions = [
      'access CiviPledge' => [
        'label' => ts('access CiviPledge'),
        'description' => ts('View pledges'),
      ],
      'edit pledges' => [
        'label' => ts('edit pledges'),
        'description' => ts('Create and update pledges'),
      ],
      'delete in CiviPledge' => [
        'label' => ts('delete in CiviPledge'),
        'description' => ts('Delete pledges'),
      ],
    ];
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
    return [
      'name' => ts('Pledges'),
      'title' => ts('Your Pledge(s)'),
      // we need to check this permission since you can click on contribution page link for making payment
      'perm' => ['make online contributions'],
      'weight' => 15,
    ];
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
  public function registerTab() {
    return [
      'title' => ts('Pledges'),
      'url' => 'pledge',
      'weight' => 25,
    ];
  }

  /**
   * @inheritDoc
   * @return string
   */
  public function getIcon() {
    return 'crm-i fa-paper-plane';
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
  public function registerAdvancedSearchPane() {
    return [
      'title' => ts('Pledges'),
      'weight' => 25,
    ];
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
  public function getActivityTypes() {
    return NULL;
  }

  /**
   * add shortcut to Create New.
   * @param $shortCuts
   */
  public function creatNewShortcut(&$shortCuts) {
    if (CRM_Core_Permission::check('access CiviPledge') &&
      CRM_Core_Permission::check('edit pledges')
    ) {
      $shortCuts = array_merge($shortCuts, [
        [
          'path' => 'civicrm/pledge/add',
          'query' => 'reset=1&action=add&context=standalone',
          'ref' => 'new-pledge',
          'title' => ts('Pledge'),
        ],
      ]);
    }
  }

}
