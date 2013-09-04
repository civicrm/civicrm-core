<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
Class CRM_Campaign_BAO_Campaign extends CRM_Campaign_DAO_Campaign {

  /**
   * takes an associative array and creates a campaign object
   *
   * the function extract all the params it needs to initialize the create a
   * contact object. the params array could contain additional unused name/value
   * pairs
   *
   * @param array  $params (reference ) an assoc array of name/value pairs
   *
   * @return object CRM_Campaign_DAO_Campaign object
   * @access public
   * @static
   */
  static function create(&$params) {
    if (empty($params)) {
      return;
    }

    if (!(CRM_Utils_Array::value('id', $params))) {

      if (!(CRM_Utils_Array::value('created_id', $params))) {
        $session = CRM_Core_Session::singleton();
        $params['created_id'] = $session->get('userID');
      }

      if (!(CRM_Utils_Array::value('created_date', $params))) {
        $params['created_date'] = date('YmdHis');
      }

      if (!(CRM_Utils_Array::value('name', $params))) {
        $params['name'] = CRM_Utils_String::titleToVar($params['title'], 64);
      }
    }

    $campaign = new CRM_Campaign_DAO_Campaign();
    $campaign->copyValues($params);
    $campaign->save();

    /* Create the campaign group record */

    $groupTableName = CRM_Contact_BAO_Group::getTableName();

    if (isset($params['groups']) && CRM_Utils_Array::value('include', $params['groups']) && is_array($params['groups']['include'])) {
      foreach ($params['groups']['include'] as $entityId) {
        $dao               = new CRM_Campaign_DAO_CampaignGroup();
        $dao->campaign_id  = $campaign->id;
        $dao->entity_table = $groupTableName;
        $dao->entity_id    = $entityId;
        $dao->group_type   = 'include';
        $dao->save();
        $dao->free();
      }
    }

    //store custom data
    if (CRM_Utils_Array::value('custom', $params) &&
      is_array($params['custom'])
    ) {
      CRM_Core_BAO_CustomValueTable::store($params['custom'], 'civicrm_campaign', $campaign->id);
    }

    return $campaign;
  }

  /**
   * function to delete the campaign
   *
   * @param  int $id id of the campaign
   */
  public static function del($id) {
    if (!$id) {
      return FALSE;
    }
    $dao = new CRM_Campaign_DAO_Campaign();
    $dao->id = $id;
    return $dao->delete();
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * campaign_id.
   *
   * @param array  $params   (reference ) an assoc array of name/value pairs
   * @param array  $defaults (reference ) an assoc array to hold the flattened values
   *
   * @access public
   */
  public function retrieve(&$params, &$defaults) {
    $campaign = new CRM_Campaign_DAO_Campaign();

    $campaign->copyValues($params);

    if ($campaign->find(TRUE)) {
      CRM_Core_DAO::storeValues($campaign, $defaults);
      return $campaign;
    }
    return NULL;
  }

  /**
   * Return the all eligible campaigns w/ cache.
   *
   * @param int      $includeId  lets inlcude this campaign by force.
   * @param int      $excludeId  do not include this campaign.
   * @param boolean  $onlyActive consider only active campaigns.
   *
   * @return $campaigns a set of campaigns.
   * @access public
   */
  public static function getCampaigns(
    $includeId = NULL,
    $excludeId          = NULL,
    $onlyActive         = TRUE,
    $onlyCurrent        = TRUE,
    $appendDatesToTitle = FALSE,
    $forceAll           = FALSE
  ) {
    static $campaigns;
    $cacheKey = 0;
    $cacheKeyParams = array(
      'includeId', 'excludeId', 'onlyActive',
      'onlyCurrent', 'appendDatesToTitle', 'forceAll',
    );
    foreach ($cacheKeyParams as $param) {
      $cacheParam = $$param;
      if (!$cacheParam) {
        $cacheParam = 0;
      }
      $cacheKey .= '_' . $cacheParam;
    }

    if (!isset($campaigns[$cacheKey])) {
      $where = array('( camp.title IS NOT NULL )');
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
      $campaigns[$cacheKey] = array();
      $config = CRM_Core_Config::singleton();

      while ($campaign->fetch()) {
        $title = $campaign->title;
        if ($appendDatesToTitle) {
          $dates = array();
          foreach (array('start_date', 'end_date') as $date) {
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
   */
  public static function getPermissionedCampaigns($includeId = NULL,
    $excludeId = NULL,
    $onlyActive = TRUE,
    $onlyCurrent = TRUE,
    $appendDatesToTitle = FALSE,
    $forceAll = FALSE,
    $doCheckForComponent = TRUE,
    $doCheckForPermissions = TRUE
  ) {
    $cacheKey = 0;
    $cachekeyParams = array(
      'includeId', 'excludeId', 'onlyActive', 'onlyCurrent',
      'appendDatesToTitle', 'doCheckForComponent', 'doCheckForPermissions', 'forceAll',
    );
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
      $campaigns = array('campaigns' => array(),
        'hasAccessCampaign' => FALSE,
        'isCampaignEnabled' => FALSE,
      );

      //do check for component.
      if ($doCheckForComponent) {
        $campaigns['isCampaignEnabled'] = $isValid = self::isCampaignEnable();
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

  /*
   * Is CiviCampaign enabled.
   *
   */
  public static function isCampaignEnable() {
    static $isEnable = NULL;

    if (!isset($isEnable)) {
      $isEnable = FALSE;
      $config = CRM_Core_Config::singleton();
      if (in_array('CiviCampaign', $config->enableComponents)) {
        $isEnable = TRUE;
      }
    }

    return $isEnable;
  }

  /**
   * Function to retrieve campaigns for dashboard.
   *
   * @static
   */
  static function getCampaignSummary($params = array(
    ), $onlyCount = FALSE) {
    $campaigns = array();

    //build the limit and order clause.
    $limitClause = $orderByClause = $lookupTableJoins = NULL;
    if (!$onlyCount) {
      $sortParams = array(
        'sort' => 'start_date',
        'offset' => 0,
        'rowCount' => 10,
        'sortOrder' => 'desc',
      );
      foreach ($sortParams as $name => $default) {
        if (CRM_Utils_Array::value($name, $params)) {
          $sortParams[$name] = $params[$name];
        }
      }


      //need to lookup tables.
      $orderOnCampaignTable = TRUE;
      if ($sortParams['sort'] == 'status') {
        $orderOnCampaignTable = FALSE;
        $lookupTableJoins = "
 LEFT JOIN civicrm_option_value status ON ( status.value = campaign.status_id OR campaign.status_id IS NULL )
INNER JOIN civicrm_option_group grp ON ( status.option_group_id = grp.id AND grp.name = 'campaign_status' )";
        $orderByClause = "ORDER BY status.label {$sortParams['sortOrder']}";
      }
      elseif ($sortParams['sort'] == 'campaign_type') {
        $orderOnCampaignTable = FALSE;
        $lookupTableJoins = "
 LEFT JOIN civicrm_option_value campaign_type ON ( campaign_type.value = campaign.campaign_type_id
                                                   OR campaign.campaign_type_id IS NULL )
INNER JOIN civicrm_option_group grp ON ( campaign_type.option_group_id = grp.id AND grp.name = 'campaign_type' )";
        $orderByClause = "ORDER BY campaign_type.label {$sortParams['sortOrder']}";
      }
      elseif ($sortParams['sort'] == 'isActive') {
        $sortParams['sort'] = 'is_active';
      }
      if ($orderOnCampaignTable) {
        $orderByClause = "ORDER BY campaign.{$sortParams['sort']} {$sortParams['sortOrder']}";
      }
      $limitClause = "LIMIT {$sortParams['offset']}, {$sortParams['rowCount']}";
    }

    //build the where clause.
    $queryParams = $where = array();
    if (CRM_Utils_Array::value('id', $params)) {
      $where[] = "( campaign.id = %1 )";
      $queryParams[1] = array($params['id'], 'Positive');
    }
    if (CRM_Utils_Array::value('name', $params)) {
      $where[] = "( campaign.name LIKE %2 )";
      $queryParams[2] = array('%' . trim($params['name']) . '%', 'String');
    }
    if (CRM_Utils_Array::value('title', $params)) {
      $where[] = "( campaign.title LIKE %3 )";
      $queryParams[3] = array('%' . trim($params['title']) . '%', 'String');
    }
    if (CRM_Utils_Array::value('start_date', $params)) {
      $startDate      = CRM_Utils_Date::processDate($params['start_date']);
      $where[]        = "( campaign.start_date >= %4 OR campaign.start_date IS NULL )";
      $queryParams[4] = array($startDate, 'String');
    }
    if (CRM_Utils_Array::value('end_date', $params)) {
      $endDate        = CRM_Utils_Date::processDate($params['end_date'], '235959');
      $where[]        = "( campaign.end_date <= %5 OR campaign.end_date IS NULL )";
      $queryParams[5] = array($endDate, 'String');
    }
    if (CRM_Utils_Array::value('description', $params)) {
      $where[] = "( campaign.description LIKE %6 )";
      $queryParams[6] = array('%' . trim($params['description']) . '%', 'String');
    }
    if (CRM_Utils_Array::value('campaign_type_id', $params)) {
      $typeId = $params['campaign_type_id'];
      if (is_array($params['campaign_type_id'])) {
        $typeId = implode(' , ', $params['campaign_type_id']);
      }
      $where[] = "( campaign.campaign_type_id IN ( {$typeId} ) )";
    }
    if (CRM_Utils_Array::value('status_id', $params)) {
      $statusId = $params['status_id'];
      if (is_array($params['status_id'])) {
        $statusId = implode(' , ', $params['status_id']);
      }
      $where[] = "( campaign.status_id IN ( {$statusId} ) )";
    }
    if (array_key_exists('is_active', $params)) {
      $active = "( campaign.is_active = 1 )";
      if (CRM_Utils_Array::value('is_active', $params)) {
        $active = "( campaign.is_active = 0 OR campaign.is_active IS NULL )";
      }
      $where[] = $active;
    }
    $whereClause = NULL;
    if (!empty($where)) {
      $whereClause = ' WHERE ' . implode(" \nAND ", $where);
    }

    $properties = array(
      'id',
      'name',
      'title',
      'start_date',
      'end_date',
      'status_id',
      'is_active',
      'description',
      'campaign_type_id',
    );

    $selectClause = '
SELECT  campaign.id               as id,
        campaign.name             as name,
        campaign.title            as title,
        campaign.is_active        as is_active,
        campaign.status_id        as status_id,
        campaign.end_date         as end_date,
        campaign.start_date       as start_date,
        campaign.description      as description,
        campaign.campaign_type_id as campaign_type_id';
    if ($onlyCount) {
      $selectClause = 'SELECT COUNT(*)';
    }
    $fromClause = 'FROM  civicrm_campaign campaign';

    $query = "{$selectClause} {$fromClause} {$lookupTableJoins} {$whereClause} {$orderByClause} {$limitClause}";

    //in case of only count.
    if ($onlyCount) {
      return (int)CRM_Core_DAO::singleValueQuery($query, $queryParams);
    }

    $campaign = CRM_Core_DAO::executeQuery($query, $queryParams);
    while ($campaign->fetch()) {
      foreach ($properties as $property) {
        $campaigns[$campaign->id][$property] = $campaign->$property;
      }
    }

    return $campaigns;
  }

  /**
   * Get the campaign count.
   *
   * @static
   */
  static function getCampaignCount() {
    return (int)CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_campaign');
  }

  /**
   * Function to get Campaigns groups
   *
   * @param int $campaignId campaign id
   *
   * @static
   */
  static function getCampaignGroups($campaignId) {
    static $campaignGroups;
    if (!$campaignId) {
      return array();
    }

    if (!isset($campaignGroups[$campaignId])) {
      $campaignGroups[$campaignId] = array();

      $query = "
    SELECT  grp.title, grp.id
      FROM  civicrm_campaign_group campgrp
INNER JOIN  civicrm_group grp ON ( grp.id = campgrp.entity_id )
     WHERE  campgrp.group_type = 'Include'
       AND  campgrp.entity_table = 'civicrm_group'
       AND  campgrp.campaign_id = %1";

      $groups = CRM_Core_DAO::executeQuery($query, array(1 => array($campaignId, 'Positive')));
      while ($groups->fetch()) {
        $campaignGroups[$campaignId][$groups->id] = $groups->title;
      }
    }

    return $campaignGroups[$campaignId];
  }

  /**
   * update the is_active flag in the db
   *
   * @param int      $id        id of the database record
   * @param boolean  $is_active value we want to set the is_active field
   *
   * @return Object             DAO object on sucess, null otherwise
   * @static
   */
  static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Campaign_DAO_Campaign', $id, 'is_active', $is_active);
  }

  static function accessCampaign() {
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

  /*
   * Add select element for campaign
   * and assign needful info to templates.
   *
   */
  public static function addCampaign(&$form, $connectedCampaignId = NULL) {
    //some forms do set default and freeze.
    $appendDates = TRUE;
    if ($form->get('action') & CRM_Core_Action::VIEW) {
      $appendDates = FALSE;
    }

    $campaignDetails = self::getPermissionedCampaigns($connectedCampaignId, NULL, TRUE, TRUE, $appendDates);
    $fields = array('campaigns', 'hasAccessCampaign', 'isCampaignEnabled');
    foreach ($fields as $fld)$$fld = CRM_Utils_Array::value($fld, $campaignDetails);

    //lets see do we have past campaigns.
    $hasPastCampaigns = FALSE;
    $allActiveCampaigns = CRM_Campaign_BAO_Campaign::getCampaigns(NULL, NULL, TRUE, FALSE);
    if (count($allActiveCampaigns) > count($campaigns)) {
      $hasPastCampaigns = TRUE;
    }
    $hasCampaigns = FALSE;
    if (!empty($campaigns)) {
      $hasCampaigns = TRUE;
    }
    if ($hasPastCampaigns) {
      $hasCampaigns = TRUE;
      $form->add('hidden', 'included_past_campaigns');
    }

    $showAddCampaign = FALSE;
    $alreadyIncludedPastCampaigns = FALSE;
    if ($connectedCampaignId || ($isCampaignEnabled && $hasAccessCampaign)) {
      $showAddCampaign = TRUE;
      //lets add past campaigns as options to quick-form element.
      if ($hasPastCampaigns && $form->getElementValue('included_past_campaigns')) {
        $campaigns = $allActiveCampaigns;
        $alreadyIncludedPastCampaigns = TRUE;
      }
      $campaign = &$form->add('select',
        'campaign_id',
        ts('Campaign'),
        array(
          '' => ts('- select -')) + $campaigns
      );
      //lets freeze when user does not has access or campaign is disabled.
      if (!$isCampaignEnabled || !$hasAccessCampaign) {
        $campaign->freeze();
      }
    }

    $addCampaignURL = NULL;
    if (empty($campaigns) && $hasAccessCampaign && $isCampaignEnabled) {
      $addCampaignURL = CRM_Utils_System::url('civicrm/campaign/add', 'reset=1');
    }

    $includePastCampaignURL = NULL;
    if ($hasPastCampaigns && $isCampaignEnabled && $hasAccessCampaign) {
      $includePastCampaignURL = CRM_Utils_System::url('civicrm/ajax/rest',
        'className=CRM_Campaign_Page_AJAX&fnName=allActiveCampaigns',
        FALSE, NULL, FALSE
      );
    }

    //carry this info to templates.
    $infoFields = array(
      'hasCampaigns',
      'addCampaignURL',
      'showAddCampaign',
      'hasPastCampaigns',
      'hasAccessCampaign',
      'isCampaignEnabled',
      'includePastCampaignURL',
      'alreadyIncludedPastCampaigns',
    );
    foreach ($infoFields as $fld) $campaignInfo[$fld] = $$fld;
    $form->assign('campaignInfo', $campaignInfo);
  }

  /*
   * Add campaign in compoent search.
   * and assign needful info to templates.
   *
   */
  public static function addCampaignInComponentSearch(&$form, $elementName = 'campaign_id') {
    $campaignInfo    = array();
    $campaignDetails = self::getPermissionedCampaigns(NULL, NULL, FALSE, FALSE, FALSE, TRUE);
    $fields          = array('campaigns', 'hasAccessCampaign', 'isCampaignEnabled');
    foreach ($fields as $fld)$$fld = CRM_Utils_Array::value($fld, $campaignDetails);
    $showCampaignInSearch = FALSE;
    if ($isCampaignEnabled && $hasAccessCampaign && !empty($campaigns)) {
      //get the current campaign only.
      $currentCampaigns = self::getCampaigns(NULL, NULL, FALSE);
      $pastCampaigns    = array_diff($campaigns, $currentCampaigns);
      $allCampaigns     = array();
      if (!empty($currentCampaigns)) {
        $allCampaigns = array('current_campaign' => ts('Current Campaigns'));
        foreach ($currentCampaigns as & $camp) $camp = "&nbsp;&nbsp;&nbsp;{$camp}";
        $allCampaigns += $currentCampaigns;
      }
      if (!empty($pastCampaigns)) {
        $allCampaigns += array('past_campaign' => ts('Past Campaigns'));
        foreach ($pastCampaigns as & $camp) $camp = "&nbsp;&nbsp;&nbsp;{$camp}";
        $allCampaigns += $pastCampaigns;
      }

      $showCampaignInSearch = TRUE;
      $form->add('select', $elementName, ts('Campaigns'), $allCampaigns, FALSE,
        array('id' => 'campaigns', 'multiple' => 'multiple', 'title' => ts('- select -'))
      );
    }
    $infoFields = array(
      'elementName',
      'hasAccessCampaign',
      'isCampaignEnabled',
      'showCampaignInSearch',
    );
    foreach ($infoFields as $fld) $campaignInfo[$fld] = $$fld;
    $form->assign('campaignInfo', $campaignInfo);
  }
}

