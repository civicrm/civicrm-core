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
   * @var bool
   */
  public $submitOnce = TRUE;

  /**
   * Used by CRM_Mailing_BAO_Mailing::getMailingContent(), so cannot be removed
   * (even though at first glance it may look safe)
   *
   * @var bool
   * @internal
   */
  public $_mailing_id;

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
    if (!in_array($context, ['home', 'dashlet', 'dashletFullscreen'])) {
      $url = CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid={$cid}&selectedChild=activity");
    }
    else {
      $url = CRM_Utils_System::url('civicrm/dashboard', 'reset=1');
    }

    $session->pushUserContext($url);
    $defaults = [];
    $params = ['id' => $activityId];
    CRM_Activity_BAO_Activity::retrieve($params, $defaults);

    // Send activity type description to template.
    [, $activityTypeDescription] = CRM_Core_BAO_OptionValue::getActivityTypeDetails($defaults['activity_type_id']);
    $this->assign('activityTypeDescription', $activityTypeDescription);

    if (!empty($defaults['mailingId'])) {
      $this->_mailing_id = $defaults['source_record_id'] ?? NULL;
      $mailingReport = CRM_Mailing_BAO_Mailing::report($this->_mailing_id, TRUE);
      CRM_Mailing_BAO_Mailing::getMailingContent($mailingReport, $this);
      $this->assign('mailingReport', $mailingReport);

      $full_open_report = CRM_Mailing_Event_BAO_MailingEventOpened::getRows(
        $this->_mailing_id, NULL, FALSE, NULL, NULL, NULL, $cid);
      $this->assign('openreport', $full_open_report);

      $click_thru_report = CRM_Mailing_Event_BAO_MailingEventTrackableURLOpen::getRows($this->_mailing_id, NULL, FALSE, NULL, NULL, NULL, NULL, $cid);
      $this->assign('clickreport', $click_thru_report);
    }

    foreach ($defaults as $key => $value) {
      if (substr($key, -3) != '_id') {
        $values[$key] = $value;
      }
    }

    // ensure these are set so that they get assigned to the template
    $values['mailingId'] ??= NULL;
    $values['campaign'] ??= NULL;
    $values['engagement_level'] ??= NULL;
    // also this which doesn't get set for bulk emails
    $values['target_contact_value'] ??= NULL;

    // Get the campaign.
    $campaignId = $defaults['campaign_id'] ?? NULL;
    if ($campaignId) {
      $campaigns = CRM_Campaign_BAO_Campaign::getCampaigns($campaignId);
      $values['campaign'] = $campaigns[$campaignId];
    }
    $engagementLevel = $defaults['engagement_level'] ?? NULL;
    if ($engagementLevel) {
      $engagementLevels = CRM_Campaign_PseudoConstant::engagementLevel();
      $values['engagement_level'] = $engagementLevels[$engagementLevel] ?? $engagementLevel;
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
