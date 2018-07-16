<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 --------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * Page for displaying Campaigns.
 */
class CRM_Campaign_Page_DashBoard extends CRM_Core_Page {

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  private static $_campaignActionLinks;
  private static $_surveyActionLinks;
  private static $_petitionActionLinks;

  /**
   * Get the action links for this page.
   *
   * @return array
   */
  public static function campaignActionLinks() {
    // check if variable _actionsLinks is populated
    if (!isset(self::$_campaignActionLinks)) {
      self::$_campaignActionLinks = array(
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/campaign/add',
          'qs' => 'reset=1&action=update&id=%%id%%',
          'title' => ts('Update Campaign'),
        ),
        CRM_Core_Action::DISABLE => array(
          'name' => ts('Disable'),
          'title' => ts('Disable Campaign'),
          'ref' => 'crm-enable-disable',
        ),
        CRM_Core_Action::ENABLE => array(
          'name' => ts('Enable'),
          'title' => ts('Enable Campaign'),
          'ref' => 'crm-enable-disable',
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/campaign/add',
          'qs' => 'action=delete&reset=1&id=%%id%%',
          'title' => ts('Delete Campaign'),
        ),
      );
    }

    return self::$_campaignActionLinks;
  }

  /**
   * @return array
   */
  public static function surveyActionLinks() {
    // check if variable _actionsLinks is populated
    if (!isset(self::$_surveyActionLinks)) {
      self::$_surveyActionLinks = array(
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/survey/configure/main',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Update Survey'),
        ),
        CRM_Core_Action::DISABLE => array(
          'name' => ts('Disable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Disable Survey'),
        ),
        CRM_Core_Action::ENABLE => array(
          'name' => ts('Enable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Enable Survey'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/survey/delete',
          'qs' => 'id=%%id%%&reset=1',
          'title' => ts('Delete Survey'),
        ),
      );
    }

    return self::$_surveyActionLinks;
  }

  /**
   * @return array
   */
  public static function petitionActionLinks() {
    if (!isset(self::$_petitionActionLinks)) {
      self::$_petitionActionLinks = self::surveyActionLinks();
      self::$_petitionActionLinks[CRM_Core_Action::UPDATE] = array(
        'name' => ts('Edit'),
        'url' => 'civicrm/petition/add',
        'qs' => 'action=update&id=%%id%%&reset=1',
        'title' => ts('Update Petition'),
      );
      self::$_petitionActionLinks[CRM_Core_Action::DISABLE] = array(
        'name' => ts('Disable'),
        'ref' => 'crm-enable-disable',
        'title' => ts('Disable Petition'),
      );
      self::$_petitionActionLinks[CRM_Core_Action::ENABLE] = array(
        'name' => ts('Enable'),
        'ref' => 'crm-enable-disable',
        'title' => ts('Enable Petition'),
      );
      self::$_petitionActionLinks[CRM_Core_Action::DELETE] = array(
        'name' => ts('Delete'),
        'url' => 'civicrm/petition/add',
        'qs' => 'action=delete&id=%%id%%&reset=1',
        'title' => ts('Delete Petition'),
      );
      self::$_petitionActionLinks[CRM_Core_Action::PROFILE] = array(
        'name' => ts('Sign'),
        'url' => 'civicrm/petition/sign',
        'qs' => 'sid=%%id%%&reset=1',
        'title' => ts('Sign Petition'),
        'fe' => TRUE,
        //CRM_Core_Action::PROFILE is used because there isn't a specific action for sign
      );
      self::$_petitionActionLinks[CRM_Core_Action::BROWSE] = array(
        'name' => ts('Signatures'),
        'url' => 'civicrm/activity/search',
        'qs' => 'survey=%%id%%&force=1',
        'title' => ts('List the signatures'),
        //CRM_Core_Action::PROFILE is used because there isn't a specific action for sign
      );
    }

    return self::$_petitionActionLinks;
  }

  /**
   * @return mixed
   */
  public function browseCampaign() {
    // ensure valid javascript (these must have a value set)
    $this->assign('searchParams', json_encode(NULL));
    $this->assign('campaignTypes', json_encode(NULL));
    $this->assign('campaignStatus', json_encode(NULL));

    $this->assign('addCampaignUrl', CRM_Utils_System::url('civicrm/campaign/add', 'reset=1&action=add'));
    $campaignCount = CRM_Campaign_BAO_Campaign::getCampaignCount();
    //don't load find interface when no campaigns in db.
    if (!$campaignCount) {
      $this->assign('hasCampaigns', FALSE);
      return;
    }
    $this->assign('hasCampaigns', TRUE);

    //build the ajaxify campaign search and selector.
    $controller = new CRM_Core_Controller_Simple('CRM_Campaign_Form_Search_Campaign', ts('Search Campaigns'));
    $controller->set('searchTab', 'campaign');
    $controller->setEmbedded(TRUE);
    $controller->process();
    return $controller->run();
  }

  /**
   * @param array $params
   *
   * @return array
   */
  public static function getCampaignSummary($params = array()) {
    $campaignsData = array();

    //get the campaigns.
    $campaigns = CRM_Campaign_BAO_Campaign::getCampaignSummary($params);
    if (!empty($campaigns)) {
      $config = CRM_Core_Config::singleton();
      $campaignType = CRM_Campaign_PseudoConstant::campaignType();
      $campaignStatus = CRM_Campaign_PseudoConstant::campaignStatus();
      $properties = array(
        'id',
        'name',
        'title',
        'status_id',
        'description',
        'campaign_type_id',
        'is_active',
        'start_date',
        'end_date',
      );
      foreach ($campaigns as $cmpid => $campaign) {
        foreach ($properties as $prop) {
          $campaignsData[$cmpid][$prop] = CRM_Utils_Array::value($prop, $campaign);
        }
        $statusId = CRM_Utils_Array::value('status_id', $campaign);
        $campaignsData[$cmpid]['status'] = CRM_Utils_Array::value($statusId, $campaignStatus);
        $campaignsData[$cmpid]['campaign_id'] = $campaign['id'];
        $campaignsData[$cmpid]['campaign_type'] = $campaignType[$campaign['campaign_type_id']];

        $action = array_sum(array_keys(self::campaignActionLinks()));
        if ($campaign['is_active']) {
          $action -= CRM_Core_Action::ENABLE;
        }
        else {
          $action -= CRM_Core_Action::DISABLE;
        }

        $isActive = ts('No');
        if ($campaignsData[$cmpid]['is_active']) {
          $isActive = ts('Yes');
        }
        $campaignsData[$cmpid]['isActive'] = $isActive;

        if (!empty($campaignsData[$cmpid]['start_date'])) {
          $campaignsData[$cmpid]['start_date'] = CRM_Utils_Date::customFormat($campaignsData[$cmpid]['start_date'],
            $config->dateformatFull
          );
        }
        if (!empty($campaignsData[$cmpid]['end_date'])) {
          $campaignsData[$cmpid]['end_date'] = CRM_Utils_Date::customFormat($campaignsData[$cmpid]['end_date'],
            $config->dateformatFull
          );
        }
        $campaignsData[$cmpid]['action'] = CRM_Core_Action::formLink(self::campaignActionLinks(),
          $action,
          array('id' => $campaign['id']),
          ts('more'),
          FALSE,
          'campaign.dashboard.row',
          'Campaign',
          $campaign['id']
        );
      }
    }

    return $campaignsData;
  }

  /**
   * @return mixed
   */
  public function browseSurvey() {
    // ensure valid javascript - this must have a value set
    $this->assign('searchParams', json_encode(NULL));
    $this->assign('surveyTypes', json_encode(NULL));
    $this->assign('surveyCampaigns', json_encode(NULL));

    $this->assign('addSurveyUrl', CRM_Utils_System::url('civicrm/survey/add', 'reset=1&action=add'));

    $surveyCount = CRM_Campaign_BAO_Survey::getSurveyCount();
    //don't load find interface when no survey in db.
    if (!$surveyCount) {
      $this->assign('hasSurveys', FALSE);
      return;
    }
    $this->assign('hasSurveys', TRUE);

    //build the ajaxify survey search and selector.
    $controller = new CRM_Core_Controller_Simple('CRM_Campaign_Form_Search_Survey', ts('Search Survey'));
    $controller->set('searchTab', 'survey');
    $controller->setEmbedded(TRUE);
    $controller->process();
    return $controller->run();
  }

  /**
   * @param array $params
   *
   * @return array
   */
  public static function getSurveySummary($params = array()) {
    $surveysData = array();

    //get the survey.
    $config = CRM_Core_Config::singleton();
    $surveys = CRM_Campaign_BAO_Survey::getSurveySummary($params);
    if (!empty($surveys)) {
      $campaigns = CRM_Campaign_BAO_Campaign::getCampaigns(NULL, NULL, FALSE, FALSE, FALSE, TRUE);
      $surveyType = CRM_Campaign_BAO_Survey::getSurveyActivityType();
      foreach ($surveys as $sid => $survey) {
        $surveysData[$sid] = $survey;
        $campaignId = CRM_Utils_Array::value('campaign_id', $survey);
        $surveysData[$sid]['campaign'] = CRM_Utils_Array::value($campaignId, $campaigns);
        $surveysData[$sid]['activity_type'] = $surveyType[$survey['activity_type_id']];
        if (!empty($survey['release_frequency'])) {
          $surveysData[$sid]['release_frequency'] = ts('1 Day', array('plural' => '%count Days', 'count' => $survey['release_frequency']));
        }

        $action = array_sum(array_keys(self::surveyActionLinks($surveysData[$sid]['activity_type'])));
        if ($survey['is_active']) {
          $action -= CRM_Core_Action::ENABLE;
        }
        else {
          $action -= CRM_Core_Action::DISABLE;
        }

        $isActive = ts('No');
        if ($surveysData[$sid]['is_active']) {
          $isActive = ts('Yes');
        }
        $surveysData[$sid]['isActive'] = $isActive;

        $isDefault = NULL;
        if ($surveysData[$sid]['is_default']) {
          $isDefault = '<img src="' . $config->resourceBase . 'i/check.gif" alt="' . ts('Default') . '" />';
        }
        $surveysData[$sid]['is_default'] = $isDefault;

        if ($surveysData[$sid]['result_id']) {
          $resultSet = '<a href= "javascript:displayResultSet( ' . $sid . ',' . "'" . $surveysData[$sid]['title'] . "'" . ', ' . $surveysData[$sid]['result_id'] . ' )" title="' . ts('view result set') . '">' . ts('Result Set') . '</a>';
          $surveysData[$sid]['result_id'] = $resultSet;
        }
        else {
          $resultUrl = CRM_Utils_System::url("civicrm/survey/configure/results", "action=update&id={$sid}&reset=1");
          $surveysData[$sid]['result_id'] = "<a href='{$resultUrl}' class='status-warning'>(" . ts('Incomplete. Click to configure result set.') . ')</a>';
        }
        $surveysData[$sid]['action'] = CRM_Core_Action::formLink(self::surveyActionLinks($surveysData[$sid]['activity_type']),
          $action,
          array('id' => $sid),
          ts('more'),
          FALSE,
          'survey.dashboard.row',
          'Survey',
          $sid
        );

        if (CRM_Utils_Array::value('activity_type', $surveysData[$sid]) != 'Petition') {
          $surveysData[$sid]['voterLinks'] = CRM_Campaign_BAO_Survey::buildPermissionLinks($sid,
            TRUE,
            ts('more')
          );
        }

        if ($reportID = CRM_Campaign_BAO_Survey::getReportID($sid)) {
          $url = CRM_Utils_System::url("civicrm/report/instance/{$reportID}", 'reset=1');
          $surveysData[$sid]['title'] = "<a href='{$url}' title='View Survey Report'>{$surveysData[$sid]['title']}</a>";
        }
      }
    }

    return $surveysData;
  }

  /**
   * Browse petitions.
   *
   * @return mixed|null
   */
  public function browsePetition() {
    // Ensure valid javascript - this must have a value set
    $this->assign('searchParams', json_encode(NULL));
    $this->assign('petitionCampaigns', json_encode(NULL));

    $this->assign('addPetitionUrl', CRM_Utils_System::url('civicrm/petition/add', 'reset=1&action=add'));

    $petitionCount = CRM_Campaign_BAO_Petition::getPetitionCount();
    //don't load find interface when no petition in db.
    if (!$petitionCount) {
      $this->assign('hasPetitions', FALSE);
      return NULL;
    }
    $this->assign('hasPetitions', TRUE);

    // Build the ajax petition search and selector.
    $controller = new CRM_Core_Controller_Simple('CRM_Campaign_Form_Search_Petition', ts('Search Petition'));
    $controller->set('searchTab', 'petition');
    $controller->setEmbedded(TRUE);
    $controller->process();
    return $controller->run();
  }

  /**
   * @param array $params
   *
   * @return array
   */
  public static function getPetitionSummary($params = array()) {
    $config = CRM_Core_Config::singleton();
    $petitionsData = array();

    //get the petitions.
    $petitions = CRM_Campaign_BAO_Petition::getPetitionSummary($params);
    if (!empty($petitions)) {
      $campaigns = CRM_Campaign_BAO_Campaign::getCampaigns(NULL, NULL, FALSE, FALSE, FALSE, TRUE);
      $petitionType = CRM_Campaign_BAO_Survey::getSurveyActivityType('label', TRUE);
      foreach ($petitions as $pid => $petition) {
        $petitionsData[$pid] = $petition;
        $camapignId = CRM_Utils_Array::value('campaign_id', $petition);
        $petitionsData[$pid]['campaign'] = CRM_Utils_Array::value($camapignId, $campaigns);
        $petitionsData[$pid]['activity_type'] = $petitionType[$petition['activity_type_id']];

        $action = array_sum(array_keys(self::petitionActionLinks()));

        if ($petition['is_active']) {
          $action -= CRM_Core_Action::ENABLE;
        }
        else {
          $action -= CRM_Core_Action::DISABLE;
        }

        $isActive = ts('No');
        if ($petitionsData[$pid]['is_active']) {
          $isActive = ts('Yes');
        }
        $petitionsData[$pid]['isActive'] = $isActive;
        $isDefault = NULL;
        if ($petitionsData[$pid]['is_default']) {
          $isDefault = '<img src="' . $config->resourceBase . 'i/check.gif" alt="' . ts('Default') . '" />';
        }
        $petitionsData[$pid]['is_default'] = $isDefault;

        $petitionsData[$pid]['action'] = CRM_Core_Action::formLink(self::petitionActionLinks(),
          $action,
          array('id' => $pid),
          ts('more'),
          FALSE,
          'petition.dashboard.row',
          'Petition',
          $pid
        );
      }
    }

    return $petitionsData;
  }

  public function browse() {
    $this->_tabs = array(
      'campaign' => ts('Campaigns'),
      'survey' => ts('Surveys'),
      'petition' => ts('Petitions'),
    );

    $subPageType = CRM_Utils_Request::retrieve('type', 'String', $this);
    if ($subPageType) {
      if (!isset($this->_tabs[$subPageType])) {
        CRM_Utils_System::permissionDenied();
      }
      //load the data in tabs.
      $this->{'browse' . ucfirst($subPageType)}();
      $this->assign('subPageType', ucfirst($subPageType));
    }
    else {
      //build the tabs.
      $this->buildTabs();
    }
    CRM_Core_Resources::singleton()
      ->addScriptFile('civicrm', 'templates/CRM/common/TabHeader.js', 1, 'html-header')
      ->addSetting(array(
        'tabSettings' => array(
          'active' => strtolower(CRM_Utils_Array::value('subPage', $_GET, 'campaign')),
        ),
      ));
  }

  /**
   * @return string
   */
  public function run() {
    if (!CRM_Campaign_BAO_Campaign::accessCampaign()) {
      CRM_Utils_System::permissionDenied();
    }

    $this->browse();

    return parent::run();
  }

  public function buildTabs() {
    $allTabs = array();
    foreach ($this->_tabs as $name => $title) {
      $allTabs[$name] = array(
        'title' => $title,
        'valid' => TRUE,
        'active' => TRUE,
        'link' => CRM_Utils_System::url('civicrm/campaign', "reset=1&type=$name"),
      );
    }
    $allTabs['campaign']['class'] = 'livePage';
    $this->assign('tabHeader', $allTabs);
  }

}
