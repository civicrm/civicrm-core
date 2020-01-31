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
class CRM_Campaign_Info extends CRM_Core_Component_Info {

  /**
   * @var string
   * @inheritDoc
   */
  protected $keyword = 'campaign';

  /**
   * @inheritDoc
   * @return array
   */
  public function getInfo() {
    return [
      'name' => 'CiviCampaign',
      'translatedName' => ts('CiviCampaign'),
      'title' => ts('CiviCRM Campaign Engine'),
      'search' => 1,
      'showActivitiesInCore' => 1,
    ];
  }

  /**
   * @inheritDoc
   * @param bool $getAllUnconditionally
   * @param bool $descriptions
   *   Whether to return permission descriptions
   *
   * @return array
   */
  public function getPermissions($getAllUnconditionally = FALSE, $descriptions = FALSE) {
    $permissions = [
      'administer CiviCampaign' => [
        ts('administer CiviCampaign'),
        ts('Create new campaign, survey and petition types and their status'),
      ],
      'manage campaign' => [
        ts('manage campaign'),
        ts('Create new campaigns, surveys and petitions, reserve respondents'),
      ],
      'reserve campaign contacts' => [
        ts('reserve campaign contacts'),
        ts('Reserve campaign contacts for surveys and petitions'),
      ],
      'release campaign contacts' => [
        ts('release campaign contacts'),
        ts('Release reserved campaign contacts for surveys and petitions'),
      ],
      'interview campaign contacts' => [
        ts('interview campaign contacts'),
        ts('Record survey and petition responses from their reserved contacts'),
      ],
      'gotv campaign contacts' => [
        ts('GOTV campaign contacts'),
        ts('Record that contacts voted'),
      ],
      'sign CiviCRM Petition' => [
        ts('sign CiviCRM Petition'),
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
   * @return null
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
    return 'crm-i fa-star-o';
  }

  /**
   * @inheritDoc
   * @return null
   */
  public function registerAdvancedSearchPane() {
    // this component doesn't use advanced search
    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function getActivityTypes() {
    return NULL;
  }

  /**
   * add shortcut to Create New.
   * @param $shortCuts
   */
  public function creatNewShortcut(&$shortCuts) {
    if (CRM_Core_Permission::check('manage campaign') ||
      CRM_Core_Permission::check('administer CiviCampaign')
    ) {
      $shortCuts = array_merge($shortCuts, [
        [
          'path' => 'civicrm/campaign/add',
          'query' => "reset=1&action=add",
          'ref' => 'new-campaign',
          'title' => ts('Campaign'),
        ],
        [
          'path' => 'civicrm/survey/add',
          'query' => "reset=1&action=add",
          'ref' => 'new-survey',
          'title' => ts('Survey'),
        ],
      ]);
    }
  }

}
