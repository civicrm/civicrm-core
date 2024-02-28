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
class CRM_Member_Info extends CRM_Core_Component_Info {

  /**
   * @var string
   * @inheritDoc
   */
  protected $keyword = 'member';

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
      'name' => 'CiviMember',
      'translatedName' => ts('CiviMember'),
      'title' => ts('CiviCRM Membership Engine'),
      'search' => 1,
      'showActivitiesInCore' => 1,
    ];
  }

  /**
   * @inheritDoc
   */
  public function getPermissions(): array {
    $permissions = [
      'access CiviMember' => [
        'label' => ts('access CiviMember'),
        'description' => ts('View memberships'),
      ],
      'edit memberships' => [
        'label' => ts('edit memberships'),
        'description' => ts('Create and update memberships'),
      ],
      'delete in CiviMember' => [
        'label' => ts('delete in CiviMember'),
        'description' => ts('Delete memberships'),
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

  /**
   * @return array|null
   */
  public function getUserDashboardElement() {
    return [
      'name' => ts('Memberships'),
      'title' => ts('Your Membership(s)'),
      // this is CiviContribute specific permission, since
      // there is no permission that could be checked for
      // CiviMember
      'perm' => ['make online contributions'],
      'weight' => 30,
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
      'title' => ts('Memberships'),
      'url' => 'membership',
      'weight' => 30,
    ];
  }

  /**
   * @inheritDoc
   * @return string
   */
  public function getIcon() {
    return 'crm-i fa-id-badge';
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
      'title' => ts('Memberships'),
      'weight' => 30,
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
    if (CRM_Core_Permission::check('access CiviMember') &&
      CRM_Core_Permission::check('edit memberships')
    ) {
      $shortCut[] = [
        'path' => 'civicrm/member/add',
        'query' => "reset=1&action=add&context=standalone",
        'ref' => 'new-membership',
        'title' => ts('Membership'),
      ];
      if ($newCredit) {
        $title = ts('Membership') . '<br />&nbsp;&nbsp;(' . ts('credit card') . ')';
        $shortCut[0]['shortCuts'][] = [
          'path' => 'civicrm/member/add',
          'query' => "reset=1&action=add&context=standalone&mode=live",
          'ref' => 'new-membership-cc',
          'title' => $title,
        ];
      }
      $shortCuts = array_merge($shortCuts, $shortCut);
    }
  }

}
