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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Campaign_BAO_Campaign extends CRM_Campaign_DAO_Campaign implements Civi\Core\HookInterface {

  /**
   * @deprecated
   *
   * @param array $params
   *
   * @return null|CRM_Campaign_DAO_Campaign
   */
  public static function create($params) {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
    if (empty($params)) {
      return NULL;
    }
    return self::writeRecord($params);
  }

  /**
   * Event fired prior to modifying a Campaign.
   * @param \Civi\Core\Event\PreEvent $event
   */
  public static function self_hook_civicrm_pre(\Civi\Core\Event\PreEvent $event) {
    if ($event->action === 'edit') {
      $event->params['last_modified_id'] ??= CRM_Core_Session::getLoggedInContactID();
    }
  }

  /**
   * Event fired after modifying a Campaign.
   * @param \Civi\Core\Event\PostEvent $event
   */
  public static function self_hook_civicrm_post(\Civi\Core\Event\PostEvent $event) {
    /* Create the campaign group record */
    $params = $event->params;
    if (in_array($event->action, ['create', 'edit']) && !empty($params['groups']['include']) && is_array($params['groups']['include'])) {
      foreach ($params['groups']['include'] as $entityId) {
        $dao = new CRM_Campaign_DAO_CampaignGroup();
        $dao->campaign_id = $event->id;
        $dao->entity_table = 'civicrm_group';
        $dao->entity_id = $entityId;
        $dao->group_type = 'Include';
        $dao->save();
      }
    }
  }

  /**
   * Delete the campaign.
   *
   * @param int $id
   *
   * @deprecated
   * @return bool|int
   */
  public static function del($id) {
    CRM_Core_Error::deprecatedFunctionWarning('deleteRecord');
    try {
      self::deleteRecord(['id' => $id]);
    }
    catch (CRM_Core_Exception $e) {
      return FALSE;
    }
    return 1;
  }

  /**
   * @deprecated
   * @param array $params
   * @param array $defaults
   * @return self|null
   */
  public static function retrieve($params, &$defaults) {
    CRM_Core_Error::deprecatedFunctionWarning('API');
    return self::commonRetrieve(self::class, $params, $defaults);
  }

  /**
   * Return the all eligible campaigns w/ cache.
   *
   * @param int $includeId
   *   Lets include this campaign by force.
   * @param int $excludeId
   *   Do not include this campaign.
   * @param bool $onlyActive
   *   Consider only active campaigns.
   *
   * @param bool $onlyCurrent
   * @param bool $appendDatesToTitle
   * @param bool $forceAll
   *
   * @return mixed
   *   $campaigns a set of campaigns.
   */
  public static function getCampaigns(
    $includeId = NULL,
    $excludeId = NULL,
    $onlyActive = TRUE,
    $onlyCurrent = TRUE,
    $appendDatesToTitle = FALSE,
    $forceAll = FALSE
  ) {
    static $campaigns;
    $cacheKey = 0;
    $cacheKeyParams = [
      'includeId',
      'excludeId',
      'onlyActive',
      'onlyCurrent',
      'appendDatesToTitle',
      'forceAll',
    ];
    foreach ($cacheKeyParams as $param) {
      $cacheParam = $$param;
      if (!$cacheParam) {
        $cacheParam = 0;
      }
      $cacheKey .= '_' . $cacheParam;
    }

    if (!isset($campaigns[$cacheKey])) {
      $where = ['( camp.title IS NOT NULL )'];
      if ($excludeId) {
        $where[] = "( camp.id != $excludeId )";
      }
      if ($onlyActive) {
        $where[] = '( camp.is_active = 1 )';
      }
      if ($onlyCurrent) {
        $where[] = '( camp.end_date IS NULL OR camp.end_date >= NOW() )';
      }
      $whereClause = implode(' AND ', $where);
      if ($includeId) {
        $whereClause .= " OR ( camp.id = $includeId )";
      }

      //lets force all.
      if ($forceAll) {
        $whereClause = '( 1 )';
      }

      $query = "
  SELECT  camp.id,
          camp.title,
          camp.start_date,
          camp.end_date
    FROM  civicrm_campaign camp
   WHERE  {$whereClause}
Order By  camp.title";

      $campaign = CRM_Core_DAO::executeQuery($query);
      $campaigns[$cacheKey] = [];
      $config = CRM_Core_Config::singleton();

      while ($campaign->fetch()) {
        $title = $campaign->title;
        if ($appendDatesToTitle) {
          $dates = [];
          foreach (['start_date', 'end_date'] as $date) {
            if ($campaign->$date) {
              $dates[] = CRM_Utils_Date::customFormat($campaign->$date, $config->dateformatFull);
            }
          }
          if (!empty($dates)) {
            $title .= ' (' . implode('-', $dates) . ')';
          }
        }
        $campaigns[$cacheKey][$campaign->id] = $title;
      }
    }

    return $campaigns[$cacheKey];
  }

  /**
   * Wrapper to self::getCampaigns( )
   * w/ permissions and component check.
   *
   * @param int $includeId
   * @param int $excludeId
   * @param bool $onlyActive
   * @param bool $onlyCurrent
   * @param bool $appendDatesToTitle
   * @param bool $forceAll
   * @param bool $doCheckForComponent
   * @param bool $doCheckForPermissions
   *
   * @return mixed
   */
  public static function getPermissionedCampaigns(
    $includeId = NULL,
    $excludeId = NULL,
    $onlyActive = TRUE,
    $onlyCurrent = TRUE,
    $appendDatesToTitle = FALSE,
    $forceAll = FALSE,
    $doCheckForComponent = TRUE,
    $doCheckForPermissions = TRUE
  ) {
    $cacheKey = 0;
    $cachekeyParams = [
      'includeId',
      'excludeId',
      'onlyActive',
      'onlyCurrent',
      'appendDatesToTitle',
      'doCheckForComponent',
      'doCheckForPermissions',
      'forceAll',
    ];
    foreach ($cachekeyParams as $param) {
      $cacheKeyParam = $$param;
      if (!$cacheKeyParam) {
        $cacheKeyParam = 0;
      }
      $cacheKey .= '_' . $cacheKeyParam;
    }

    static $validCampaigns;
    if (!isset($validCampaigns[$cacheKey])) {
      $isValid = TRUE;
      $campaigns = [
        'campaigns' => [],
        'hasAccessCampaign' => FALSE,
        'isCampaignEnabled' => FALSE,
      ];

      //do check for component.
      if ($doCheckForComponent) {
        $campaigns['isCampaignEnabled'] = $isValid = CRM_Core_Component::isEnabled('CiviCampaign');
      }

      //do check for permissions.
      if ($doCheckForPermissions) {
        $campaigns['hasAccessCampaign'] = $isValid = self::accessCampaign();
      }

      //finally retrieve campaigns from db.
      if ($isValid) {
        $campaigns['campaigns'] = self::getCampaigns($includeId,
          $excludeId,
          $onlyActive,
          $onlyCurrent,
          $appendDatesToTitle,
          $forceAll
        );
      }

      //store in cache.
      $validCampaigns[$cacheKey] = $campaigns;
    }

    return $validCampaigns[$cacheKey];
  }

  /**
   * Is CiviCampaign enabled.
   * @deprecated
   * @return bool
   */
  public static function isCampaignEnable(): bool {
    CRM_Core_Error::deprecatedFunctionWarning('isComponentEnabled');
    return self::isComponentEnabled();
  }

  /**
   * Get Campaigns groups.
   *
   * @param int $campaignId
   *   Campaign id.
   *
   * @return array
   */
  public static function getCampaignGroups($campaignId) {
    static $campaignGroups;
    if (!$campaignId) {
      return [];
    }

    if (!isset($campaignGroups[$campaignId])) {
      $campaignGroups[$campaignId] = [];

      $query = "
    SELECT  grp.title, grp.id
      FROM  civicrm_campaign_group campgrp
INNER JOIN  civicrm_group grp ON ( grp.id = campgrp.entity_id )
     WHERE  campgrp.group_type = 'Include'
       AND  campgrp.entity_table = 'civicrm_group'
       AND  campgrp.campaign_id = %1";

      $groups = CRM_Core_DAO::executeQuery($query, [1 => [$campaignId, 'Positive']]);
      while ($groups->fetch()) {
        $campaignGroups[$campaignId][$groups->id] = $groups->title;
      }
    }

    return $campaignGroups[$campaignId];
  }

  /**
   * @deprecated - this bypasses hooks.
   * @param int $id
   * @param bool $is_active
   * @return bool
   */
  public static function setIsActive($id, $is_active) {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
    return CRM_Core_DAO::setFieldValue('CRM_Campaign_DAO_Campaign', $id, 'is_active', $is_active);
  }

  /**
   * @return bool
   */
  public static function accessCampaign() {
    static $allow = NULL;

    if (!isset($allow)) {
      $allow = FALSE;
      if (CRM_Core_Permission::check('manage campaign') ||
        CRM_Core_Permission::check('administer CiviCampaign')
      ) {
        $allow = TRUE;
      }
    }

    return $allow;
  }

  /**
   * Add select element for campaign
   * and assign needful info to templates.
   *
   * @param CRM_Core_Form $form
   * @param int $connectedCampaignId
   */
  public static function addCampaign(&$form, $connectedCampaignId = NULL) {
    //some forms do set default and freeze.
    $appendDates = TRUE;
    if ($form->get('action') & CRM_Core_Action::VIEW) {
      $appendDates = FALSE;
    }

    $campaignDetails = self::getPermissionedCampaigns($connectedCampaignId, NULL, TRUE, TRUE, $appendDates);

    $campaigns = $campaignDetails['campaigns'] ?? NULL;
    $hasAccessCampaign = $campaignDetails['hasAccessCampaign'] ?? NULL;
    $isCampaignEnabled = $campaignDetails['isCampaignEnabled'] ?? NULL;

    $showAddCampaign = FALSE;
    if ($connectedCampaignId || ($isCampaignEnabled && $hasAccessCampaign)) {
      $showAddCampaign = TRUE;
      $campaign = $form->addEntityRef('campaign_id', ts('Campaign'), [
        'entity' => 'Campaign',
        'create' => TRUE,
        'select' => ['minimumInputLength' => 0],
      ]);
      //lets freeze when user does not has access or campaign is disabled.
      if (!$isCampaignEnabled || !$hasAccessCampaign) {
        $campaign->freeze();
      }
    }

    //carry this info to templates.
    $campaignInfo = [
      'showAddCampaign' => $showAddCampaign,
      'hasAccessCampaign' => $hasAccessCampaign,
      'isCampaignEnabled' => $isCampaignEnabled,
    ];

    $form->assign('campaignInfo', $campaignInfo);
  }

  /**
   * Add campaign in component search.
   * and assign needful info to templates.
   *
   * @param CRM_Core_Form $form
   * @param string $elementName
   */
  public static function addCampaignInComponentSearch(&$form, $elementName = 'campaign_id') {
    $campaignInfo = [];
    $campaignDetails = self::getPermissionedCampaigns(NULL, NULL, FALSE, FALSE, FALSE, TRUE);
    $campaigns = $campaignDetails['campaigns'] ?? NULL;
    $hasAccessCampaign = $campaignDetails['hasAccessCampaign'] ?? NULL;
    $isCampaignEnabled = $campaignDetails['isCampaignEnabled'] ?? NULL;

    $showCampaignInSearch = FALSE;
    if ($isCampaignEnabled && $hasAccessCampaign && !empty($campaigns)) {
      //get the current campaign only.
      $currentCampaigns = self::getCampaigns(NULL, NULL, FALSE);
      $pastCampaigns = array_diff($campaigns, $currentCampaigns);
      $allCampaigns = [];
      if (!empty($currentCampaigns)) {
        $allCampaigns = ['crm_optgroup_current_campaign' => ts('Current Campaigns')] + $currentCampaigns;
      }
      if (!empty($pastCampaigns)) {
        $allCampaigns += ['crm_optgroup_past_campaign' => ts('Past Campaigns')] + $pastCampaigns;
      }

      $showCampaignInSearch = TRUE;
      $form->add('select', $elementName, ts('Campaigns'), $allCampaigns, FALSE,
        ['id' => 'campaigns', 'multiple' => 'multiple', 'class' => 'crm-select2']
      );
    }

    $form->assign('campaignElementName', $showCampaignInSearch ? $elementName : '');
  }

  /**
   * @return array
   */
  public static function getEntityRefFilters() {
    return [
      ['key' => 'campaign_type_id', 'value' => ts('Campaign Type')],
      ['key' => 'status_id', 'value' => ts('Status')],
      [
        'key' => 'start_date',
        'value' => ts('Start Date'),
        'options' => [
          ['key' => '{">":"now"}', 'value' => ts('Upcoming')],
          [
            'key' => '{"BETWEEN":["now - 3 month","now"]}',
            'value' => ts('Past 3 Months'),
          ],
          [
            'key' => '{"BETWEEN":["now - 6 month","now"]}',
            'value' => ts('Past 6 Months'),
          ],
          [
            'key' => '{"BETWEEN":["now - 1 year","now"]}',
            'value' => ts('Past Year'),
          ],
        ],
      ],
      [
        'key' => 'end_date',
        'value' => ts('End Date'),
        'options' => [
          ['key' => '{">":"now"}', 'value' => ts('In the future')],
          ['key' => '{"<":"now"}', 'value' => ts('In the past')],
          ['key' => '{"IS NULL":"1"}', 'value' => ts('Not set')],
        ],
      ],
    ];
  }

  /**
   * Links to create new campaigns from entityRef widget
   *
   * @return array|bool
   */
  public static function getEntityRefCreateLinks() {
    if (CRM_Core_Permission::check([['administer CiviCampaign', 'manage campaign']])) {
      return [
        [
          'label' => ts('New Campaign'),
          'url' => CRM_Utils_System::url('civicrm/campaign/add', "reset=1",
            NULL, NULL, FALSE, FALSE, TRUE),
          'type' => 'Campaign',
        ],
      ];
    }
    return FALSE;
  }

}
