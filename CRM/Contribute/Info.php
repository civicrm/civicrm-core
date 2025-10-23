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
class CRM_Contribute_Info extends CRM_Core_Component_Info {


  /**
   * @var string
   * @inheritDoc
   */
  protected $keyword = 'contribute';

  /**
   * @inheritDoc
   * Provides base information about the component.
   * Needs to be implemented in component's information
   * class.
   *
   * @return array
   *   collection of required component settings
   */

  /**
   * @return array
   */
  public function getInfo() {
    return [
      'name' => 'CiviContribute',
      'translatedName' => ts('CiviContribute'),
      'title' => ts('CiviCRM Contribution Engine'),
      'search' => 1,
      'showActivitiesInCore' => 1,
    ];
  }

  /**
   * @inheritDoc
   */
  public function getPermissions(): array {
    $permissions = [
      'access CiviContribute' => [
        'label' => ts('access CiviContribute'),
        'description' => ts('Record backend contributions (with edit contributions) and view all contributions (for visible contacts)'),
      ],
      'edit contributions' => [
        'label' => ts('edit contributions'),
        'description' => ts('Record and update contributions'),
      ],
      'refund contributions' => [
        'label' => ts('Refund contributions'),
        'description' => ts('Allow refunds to be issued for contributions'),
      ],
      'make online contributions' => [
        'label' => ts('make online contributions'),
      ],
      'delete in CiviContribute' => [
        'label' => ts('delete in CiviContribute'),
        'description' => ts('Delete contributions'),
      ],
    ];
    return $permissions;
  }

  /**
   * Provides permissions that are unwise for Anonymous Roles to have.
   *
   * @return array
   *   list of permissions
   * @see CRM_Component_Info::getPermissions
   */

  /**
   * @return array
   */
  public function getAnonymousPermissionWarnings() {
    return [
      'access CiviContribute',
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

  /**
   * @return array|null
   */
  public function getUserDashboardElement() {
    return [
      'name' => ts('Contributions'),
      'title' => ts('Your Contribution(s)'),
      'perm' => ['make online contributions'],
      'weight' => 10,
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

  /**
   * @return array|null
   */
  public function registerTab() {
    return [
      'title' => ts('Contributions'),
      'url' => 'contribution',
      'weight' => 20,
    ];
  }

  /**
   * @inheritDoc
   * @return string
   */
  public function getIcon() {
    return 'crm-i fa-credit-card';
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
    return [
      'title' => ts('Contributions'),
      'weight' => 20,
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

  /**
   * @return array|null
   */
  public function getActivityTypes() {
    return NULL;
  }

  /**
   * add shortcut to Create New.
   * @param $shortCuts
   * @param $newCredit
   */
  public function creatNewShortcut(&$shortCuts, $newCredit) {
    if (CRM_Core_Permission::check('access CiviContribute') &&
      CRM_Core_Permission::check('edit contributions')
    ) {
      $shortCut[] = [
        'path' => 'civicrm/contribute/add',
        'query' => "reset=1&action=add&context=standalone",
        'ref' => 'new-contribution',
        'title' => ts('Contribution'),
      ];
      if ($newCredit) {
        $title = ts('Contribution') . '<br />&nbsp;&nbsp;(' . ts('credit card') . ')';
        $shortCut[0]['shortCuts'][] = [
          'path' => 'civicrm/contribute/add',
          'query' => "reset=1&action=add&context=standalone&mode=live",
          'ref' => 'new-contribution-cc',
          'title' => $title,
        ];
      }
      $shortCuts = array_merge($shortCuts, $shortCut);
    }
  }

}
