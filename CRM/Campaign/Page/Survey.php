<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * Page for displaying Surveys
 */
class CRM_Campaign_Page_Survey extends CRM_Core_Page {

  public $useLivePageJS = TRUE;

  private static $_actionLinks;

  /**
   * @return array
   */
  function &actionLinks() {
    // check if variable _actionsLinks is populated
    if (!isset(self::$_actionLinks)) {

      self::$_actionLinks = array(
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/survey/add',
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
    return self::$_actionLinks;
  }

  function browse() {

    $surveys = CRM_Campaign_BAO_Survey::getSurveySummary();

    if (!empty($surveys)) {

      $surveyType    = CRM_Campaign_BAO_Survey::getSurveyActivityType();
      $campaigns     = CRM_Campaign_BAO_Campaign::getAllCampaign();
      $activityTypes = CRM_Core_OptionGroup::values('activity_type', FALSE, FALSE, FALSE, FALSE, 'name');
      foreach ($surveys as $sid => $survey) {
        $surveys[$sid]['campaign_id'] = $campaigns[$survey['campaign_id']];
        $surveys[$sid]['activity_type_id'] = $surveyType[$survey['activity_type_id']];
        $surveys[$sid]['release_frequency'] = $survey['release_frequency_interval'] . ' ' . $survey['release_frequency_unit'];

        $action = array_sum(array_keys($this->actionLinks()));
        if ($survey['is_active']) {
          $action -= CRM_Core_Action::ENABLE;
        }
        else {
          $action -= CRM_Core_Action::DISABLE;
        }

        $surveys[$sid]['action'] = CRM_Core_Action::formLink(
          $this->actionLinks(),
          $action,
          array('id' => $sid),
          ts('more'),
          FALSE,
          'survey.selector.row',
          'Survey',
          $sid
        );
      }
    }

    $this->assign('surveys', $surveys);
    $this->assign('addSurveyUrl', CRM_Utils_System::url('civicrm/survey/add', 'reset=1&action=add'));
  }

  /**
   * @return string
   */
  function run() {
    if (!CRM_Campaign_BAO_Campaign::accessCampaign()) {
      CRM_Utils_System::permissionDenied();
    }

    $action = CRM_Utils_Request::retrieve('action', 'String',
      $this, FALSE, 0
    );
    $this->assign('action', $action);
    $this->browse();

    return parent::run();
  }
}

