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

/**
 * This class handle activity view mode.
 */
class CRM_Activity_Form_ActivityView extends CRM_Core_Form {

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    // Get the activity values.
    $activityId = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this);
    $cid = CRM_Utils_Request::retrieve('cid', 'Positive', $this);

    // Check for required permissions, CRM-6264.
    if ($activityId &&
      !CRM_Activity_BAO_Activity::checkPermission($activityId, CRM_Core_Action::VIEW)
    ) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
    }

    $session = CRM_Core_Session::singleton();
    if (!in_array($context, [
      'home',
      'dashlet',
      'dashletFullscreen',
    ])
    ) {
      $url = CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid={$cid}&selectedChild=activity");
    }
    else {
      $url = CRM_Utils_System::url('civicrm/dashboard', 'reset=1');
    }

    $session->pushUserContext($url);
    $defaults = [];
    $params = ['id' => $activityId];
    CRM_Activity_BAO_Activity::retrieve($params, $defaults);

    // Set activity type name and description to template.
    list($activityTypeName, $activityTypeDescription) = CRM_Core_BAO_OptionValue::getActivityTypeDetails($defaults['activity_type_id']);

    // activityTypeName - dev/core#1116-unknown-if-ok
    // It seems like activityTypeName is no longer used? Description is still used though. See PR notes for more details.
    $this->assign('activityTypeName', $activityTypeName);
    $this->assign('activityTypeDescription', $activityTypeDescription);

    if (!empty($defaults['mailingId'])) {
      $this->_mailing_id = CRM_Utils_Array::value('source_record_id', $defaults);
      $mailingReport = CRM_Mailing_BAO_Mailing::report($this->_mailing_id, TRUE);
      CRM_Mailing_BAO_Mailing::getMailingContent($mailingReport, $this);
      $this->assign('mailingReport', $mailingReport);

      $full_open_report = CRM_Mailing_Event_BAO_Opened::getRows(
        $this->_mailing_id, NULL, FALSE, NULL, NULL, NULL, $cid);
      $this->assign('openreport', $full_open_report);

      $click_thru_report = CRM_Mailing_Event_BAO_TrackableURLOpen::getRows($this->_mailing_id, NULL, FALSE, NULL, NULL, NULL, NULL, $cid);
      $this->assign('clickreport', $click_thru_report);
    }

    foreach ($defaults as $key => $value) {
      if (substr($key, -3) != '_id') {
        $values[$key] = $value;
      }
    }

    // Get the campaign.
    if ($campaignId = CRM_Utils_Array::value('campaign_id', $defaults)) {
      $campaigns = CRM_Campaign_BAO_Campaign::getCampaigns($campaignId);
      $values['campaign'] = $campaigns[$campaignId];
    }
    if ($engagementLevel = CRM_Utils_Array::value('engagement_level', $defaults)) {
      $engagementLevels = CRM_Campaign_PseudoConstant::engagementLevel();
      $values['engagement_level'] = CRM_Utils_Array::value($engagementLevel, $engagementLevels, $engagementLevel);
    }

    $values['attachment'] = CRM_Core_BAO_File::attachmentInfo('civicrm_activity', $activityId);
    $this->assign('values', $values);

    $url = CRM_Utils_System::url(implode("/", $this->urlPath), "reset=1&id={$activityId}&action=view&cid={$defaults['source_contact_id']}");
    CRM_Utils_Recent::add($defaults['subject'],
      $url,
      $activityId,
      'Activity',
      $defaults['source_contact_id'],
      $defaults['source_contact']
    );
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->addButtons([
        [
          'type' => 'cancel',
          'name' => ts('Done'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ],
    ]
    );
  }

}
