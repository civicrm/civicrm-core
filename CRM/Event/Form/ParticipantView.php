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
 * This class generates form components for Participant
 *
 */
class CRM_Event_Form_ParticipantView extends CRM_Core_Form {

  public $useLivePageJS = TRUE;

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    $values        = $ids = array();
    $participantID = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
    $contactID     = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
    $params        = array('id' => $participantID);

    CRM_Event_BAO_Participant::getValues($params,
      $values,
      $ids
    );

    if (empty($values)) {
      CRM_Core_Error::statusBounce(ts('The requested participant record does not exist (possibly the record was deleted).'));
    }

    CRM_Event_BAO_Participant::resolveDefaults($values[$participantID]);

    if (!empty($values[$participantID]['fee_level'])) {
      CRM_Event_BAO_Participant::fixEventLevel($values[$participantID]['fee_level']);
    }

    $this->assign('contactId', $contactID);
    $this->assign('participantId', $participantID);

    $paymentId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_ParticipantPayment',
      $participantID, 'id', 'participant_id'
    );
    $this->assign('hasPayment', $paymentId);

    if ($parentParticipantId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Participant',
          $participantID, 'registered_by_id'
      )) {
      $parentHasPayment = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_ParticipantPayment',
        $parentParticipantId, 'id', 'participant_id'
      );
      $this->assign('parentHasPayment', $parentHasPayment);
    }

    $statusId = CRM_Core_DAO::getFieldValue('CRM_Event_BAO_Participant', $participantID, 'status_id', 'id');
    $participantStatuses = CRM_Event_PseudoConstant::participantStatus();

    if ($values[$participantID]['is_test']) {
      $values[$participantID]['status'] .= ' (test) ';
    }

    // Get Note
    $noteValue = CRM_Core_BAO_Note::getNote($participantID, 'civicrm_participant');

    $values[$participantID]['note'] = array_values($noteValue);


    // Get Line Items
    $lineItem = CRM_Price_BAO_LineItem::getLineItems($participantID);

    if (!CRM_Utils_System::isNull($lineItem)) {
      $values[$participantID]['lineItem'][] = $lineItem;
    }

    $values[$participantID]['totalAmount'] = CRM_Utils_Array::value('fee_amount', $values[$participantID]);

    // Get registered_by contact ID and display_name if participant was registered by someone else (CRM-4859)
    if (!empty($values[$participantID]['participant_registered_by_id'])) {
      $values[$participantID]['registered_by_contact_id'] = CRM_Core_DAO::getFieldValue("CRM_Event_DAO_Participant",
        $values[$participantID]['participant_registered_by_id'],
        'contact_id', 'id'
      );
      $values[$participantID]['registered_by_display_name'] = CRM_Contact_BAO_Contact::displayName($values[$participantID]['registered_by_contact_id']);
    }

    // Check if this is a primaryParticipant (registered for others) and retrieve additional participants if true  (CRM-4859)
    if (CRM_Event_BAO_Participant::isPrimaryParticipant($participantID)) {
      $values[$participantID]['additionalParticipants'] = CRM_Event_BAO_Participant::getAdditionalParticipants($participantID);
    }

    // get the option value for custom data type
    $roleCustomDataTypeID = CRM_Core_OptionGroup::getValue('custom_data_type', 'ParticipantRole', 'name');
    $eventNameCustomDataTypeID = CRM_Core_OptionGroup::getValue('custom_data_type', 'ParticipantEventName', 'name');
    $eventTypeCustomDataTypeID = CRM_Core_OptionGroup::getValue('custom_data_type', 'ParticipantEventType', 'name');
    $allRoleIDs = explode(CRM_Core_DAO::VALUE_SEPARATOR, $values[$participantID]['role_id']);
    $groupTree = array();
    $finalTree = array();

    foreach ($allRoleIDs as $k => $v) {
      $roleGroupTree = CRM_Core_BAO_CustomGroup::getTree('Participant', $this, $participantID, NULL, $v, $roleCustomDataTypeID);
      $eventGroupTree = &CRM_Core_BAO_CustomGroup::getTree('Participant', $this, $participantID, NULL,
        $values[$participantID]['event_id'], $eventNameCustomDataTypeID
      );
      $eventTypeID        = CRM_Core_DAO::getFieldValue("CRM_Event_DAO_Event", $values[$participantID]['event_id'], 'event_type_id', 'id');
      $eventTypeGroupTree = CRM_Core_BAO_CustomGroup::getTree('Participant', $this, $participantID, NULL, $eventTypeID, $eventTypeCustomDataTypeID);
      $groupTree          = CRM_Utils_Array::crmArrayMerge($roleGroupTree, $eventGroupTree);
      $groupTree          = CRM_Utils_Array::crmArrayMerge($groupTree, $eventTypeGroupTree);
      $groupTree          = CRM_Utils_Array::crmArrayMerge($groupTree, CRM_Core_BAO_CustomGroup::getTree('Participant', $this, $participantID));
      foreach ($groupTree as $treeId => $trees) {
        $finalTree[$treeId] = $trees;
      }
    }
    CRM_Core_BAO_CustomGroup::buildCustomDataView($this, $finalTree);
    $eventTitle = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $values[$participantID]['event_id'], 'title');
    //CRM-7150, show event name on participant view even if the event is disabled
    if (empty($values[$participantID]['event'])) {
      $values[$participantID]['event'] = $eventTitle;
    }

    //do check for campaigns
    if ($campaignId = CRM_Utils_Array::value('campaign_id', $values[$participantID])) {
      $campaigns = CRM_Campaign_BAO_Campaign::getCampaigns($campaignId);
      $values[$participantID]['campaign'] = $campaigns[$campaignId];
    }

    $this->assign($values[$participantID]);

    // add viewed participant to recent items list
    $url = CRM_Utils_System::url('civicrm/contact/view/participant',
      "action=view&reset=1&id={$values[$participantID]['id']}&cid={$values[$participantID]['contact_id']}&context=home"
    );

    $recentOther = array();
    if (CRM_Core_Permission::check('edit event participants')) {
      $recentOther['editUrl'] = CRM_Utils_System::url('civicrm/contact/view/participant',
        "action=update&reset=1&id={$values[$participantID]['id']}&cid={$values[$participantID]['contact_id']}&context=home"
      );
    }
    if (CRM_Core_Permission::check('delete in CiviEvent')) {
      $recentOther['deleteUrl'] = CRM_Utils_System::url('civicrm/contact/view/participant',
        "action=delete&reset=1&id={$values[$participantID]['id']}&cid={$values[$participantID]['contact_id']}&context=home"
      );
    }

    $participantRoles = CRM_Event_PseudoConstant::participantRole();
    $displayName = CRM_Contact_BAO_Contact::displayName($values[$participantID]['contact_id']);

    $participantCount = array();
    foreach ($lineItem as $k => $v) {
      if (CRM_Utils_Array::value('participant_count', $lineItem[$k]) > 0) {
        $participantCount[] = $lineItem[$k]['participant_count'];
      }
    }
    if ($participantCount) {
      $this->assign('pricesetFieldsCount', $participantCount);
    }
    $this->assign('displayName', $displayName);
    // omitting contactImage from title for now since the summary overlay css doesn't work outside of our crm-container
    CRM_Utils_System::setTitle(ts('View Event Registration for') .  ' ' . $displayName);

    $roleId = CRM_Utils_Array::value('role_id', $values[$participantID]);
    $title = $displayName . ' (' . CRM_Utils_Array::value($roleId, $participantRoles) . ' - ' . $eventTitle . ')';

    $sep = CRM_Core_DAO::VALUE_SEPARATOR;
    $viewRoles = array();
    foreach (explode($sep, $values[$participantID]['role_id']) as $k => $v) {
      $viewRoles[] = $participantRoles[$v];
    }
    $values[$participantID]['role_id'] = implode(', ', $viewRoles);
    $this->assign('role', $values[$participantID]['role_id']);
    // add Participant to Recent Items
    CRM_Utils_Recent::add($title,
      $url,
      $values[$participantID]['id'],
      'Participant',
      $values[$participantID]['contact_id'],
      NULL,
      $recentOther
    );
  }

  /**
   * Function to build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    $this->addButtons(array(
        array(
          'type' => 'cancel',
          'name' => ts('Done'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ),
      )
    );
  }
}

