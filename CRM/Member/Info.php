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

/**
 * This class introduces component to the system and provides all the
 * information about it. It needs to extend CRM_Core_Component_Info
 * abstract class.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
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
      'access CiviMember' => [
        ts('access CiviMember'),
        ts('View memberships'),
      ],
      'edit memberships' => [
        ts('edit memberships'),
        ts('Create and update memberships'),
      ],
      'delete in CiviMember' => [
        ts('delete in CiviMember'),
        ts('Delete memberships'),
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
