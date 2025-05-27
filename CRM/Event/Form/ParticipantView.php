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
 * This class generates form components for Participant
 *
 */
class CRM_Event_Form_ParticipantView extends CRM_Core_Form {
  use CRM_Event_Form_EventFormTrait;

  public $useLivePageJS = TRUE;

  /**
   * Set variables up before form is built.
   *
   * @return void
   */
  public function preProcess() {
    $values = $ids = [];
    $participantID = $this->getParticipantID();
    $contactID = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
    $params = ['id' => $participantID];

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
    $this->assign('componentId', $participantID);
    $this->assign('component', 'event');
    $parentParticipantID = $this->getParticipantValue('registered_by_id');
    $this->assign('participant_registered_by_id', $parentParticipantID);
    // Check if this is a primaryParticipant (registered for others) and retrieve additional participants if true  (CRM-4859)
    if (CRM_Event_BAO_Participant::isPrimaryParticipant($this->getParticipantID())) {
      $additionalParticipants = CRM_Event_BAO_Participant::getAdditionalParticipants($this->getParticipantID());
    }
    $this->assign('additionalParticipants', $additionalParticipants ?? NULL);

    $this->assign('parentHasPayment', !$parentParticipantID ? NULL : CRM_Core_DAO::getFieldValue('CRM_Event_DAO_ParticipantPayment',
      $parentParticipantID, 'id', 'participant_id'
    ));

    $statusId = CRM_Core_DAO::getFieldValue('CRM_Event_BAO_Participant', $participantID, 'status_id', 'id');
    $status = CRM_Core_DAO::getFieldValue('CRM_Event_BAO_ParticipantStatusType', $statusId, 'name', 'id');
    if ($status === 'Transferred') {
      $transferId = CRM_Core_DAO::getFieldValue('CRM_Event_BAO_Participant', $participantID, 'transferred_to_contact_id', 'id');
      $pid = CRM_Core_DAO::getFieldValue('CRM_Event_BAO_Participant', $transferId, 'id', 'contact_id');
      $transferName = current(CRM_Contact_BAO_Contact::getContactDetails($transferId));
      $this->assign('pid', $pid);
      $this->assign('transferId', $transferId);
    }
    $this->assign('transferName', $transferName ?? NULL);

    // CRM-20879: Show 'Transfer or Cancel' option beside 'Change fee selection'
    //  only if logged in user have 'edit event participants' permission and
    //  participant status is not Cancelled or Transferred
    if (CRM_Core_Permission::check('edit event participants') && !in_array($status, ['Cancelled', 'Transferred'])) {
      $this->assign('transferOrCancelLink',
        CRM_Utils_System::url(
          'civicrm/event/selfsvcupdate',
          [
            'reset' => 1,
            'is_backoffice' => 1,
            'pid' => $participantID,
            'cs' => CRM_Contact_BAO_Contact_Utils::generateChecksum($contactID, NULL, 'inf'),
          ]
        )
      );
    }

    $this->assign('status', $this->getParticipantValue('status_id:label') . ($this->getParticipantValue('is_test') ? ' ' . ts('(test)') : ''));
    $this->assign('note', array_values(CRM_Core_BAO_Note::getNote($participantID, 'civicrm_participant')));

    // Get Line Items
    $lineItem = CRM_Price_BAO_LineItem::getLineItems($participantID);
    $this->assign('lineItem', [$lineItem]);

    // Assign registered_by contact ID and display_name if participant was registered by someone else (CRM-4859)
    $this->assign('registered_by_display_name', $this->getParticipantValue('registered_by_id.contact_id.display_name'));
    $this->assign('registered_by_contact_id', $this->getParticipantValue('registered_by_id.contact_id'));

    // get the option value for custom data type
    $customDataType = CRM_Core_OptionGroup::values('custom_data_type', FALSE, FALSE, FALSE, NULL, 'name');
    $roleCustomDataTypeID = array_search('ParticipantRole', $customDataType);
    $eventNameCustomDataTypeID = array_search('ParticipantEventName', $customDataType);
    $eventTypeCustomDataTypeID = array_search('ParticipantEventType', $customDataType);
    $allRoleIDs = explode(CRM_Core_DAO::VALUE_SEPARATOR, $values[$participantID]['role_id']);
    $finalTree = [];

    foreach ($allRoleIDs as $k => $v) {
      $roleGroupTree = CRM_Core_BAO_CustomGroup::getTree('Participant', NULL, $participantID, NULL, $v, $roleCustomDataTypeID,
         TRUE, NULL, FALSE, CRM_Core_Permission::VIEW);
      $eventGroupTree = CRM_Core_BAO_CustomGroup::getTree('Participant', NULL, $participantID, NULL,
        $values[$participantID]['event_id'], $eventNameCustomDataTypeID,
        TRUE, NULL, FALSE, CRM_Core_Permission::VIEW
      );
      $eventTypeID = CRM_Core_DAO::getFieldValue("CRM_Event_DAO_Event", $values[$participantID]['event_id'], 'event_type_id', 'id');
      $eventTypeGroupTree = CRM_Core_BAO_CustomGroup::getTree('Participant', NULL, $participantID, NULL, $eventTypeID, $eventTypeCustomDataTypeID,
        TRUE, NULL, FALSE, CRM_Core_Permission::VIEW);
      $participantGroupTree = CRM_Core_BAO_CustomGroup::getTree('Participant', NULL, $participantID, NULL, [], NULL,
        TRUE, NULL, FALSE, CRM_Core_Permission::VIEW);
      $groupTree = CRM_Utils_Array::crmArrayMerge($roleGroupTree, $eventGroupTree);
      $groupTree = CRM_Utils_Array::crmArrayMerge($groupTree, $eventTypeGroupTree);
      $groupTree = CRM_Utils_Array::crmArrayMerge($groupTree, $participantGroupTree);
      foreach ($groupTree as $treeId => $trees) {
        $finalTree[$treeId] = $trees;
      }
    }
    CRM_Core_BAO_CustomGroup::buildCustomDataView($this, $finalTree, FALSE, NULL, NULL, NULL, $participantID);
    $eventTitle = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $values[$participantID]['event_id'], 'title');
    //CRM-7150, show event name on participant view even if the event is disabled
    $this->assign('event', $eventTitle);
    $this->assign('campaign', $this->getParticipantValue('campaign_id:label'));
    // @todo - this assign makes it really hard to see what is being assigned - do individual assigns.
    $this->assign($values[$participantID]);

    // add viewed participant to recent items list
    $url = CRM_Utils_System::url('civicrm/contact/view/participant',
      "action=view&reset=1&id={$values[$participantID]['id']}&cid={$values[$participantID]['contact_id']}&context=home"
    );

    $recentOther = [];
    if (CRM_Core_Permission::check('edit event participants')) {
      $recentOther['editUrl'] = CRM_Utils_System::url('civicrm/contact/view/participant',
        "action=update&reset=1&id={$values[$participantID]['id']}&cid={$values[$participantID]['contact_id']}&context=home"
      );
    }
    if (CRM_Core_Permission::check('delete in CiviEvent')) {
      $recentOther['deleteUrl'] = CRM_Utils_System::url('civicrm/participant/delete',
        "reset=1&id={$values[$participantID]['id']}"
      );
    }

    $displayName = CRM_Contact_BAO_Contact::displayName($values[$participantID]['contact_id']);

    $participantCount = [];
    $totalTaxAmount = $totalAmount = 0;
    foreach ($lineItem as $k => $v) {
      if (($lineItem[$k]['participant_count'] ?? 0) > 0) {
        $participantCount[] = $lineItem[$k]['participant_count'];
      }
      $totalTaxAmount = $v['tax_amount'] + $totalTaxAmount;
      $totalAmount += ($v['line_total'] + $v['tax_amount']);
    }
    $this->assign('currency', $this->getParticipantValue('fee_currency'));
    // It would be more  correct to assign totalTaxAmount & TotalAmount
    // from the order object - however, that assumes a contribution exists & there is this
    // we have this weird possibility of line items against a participant record with
    // no contribution attached to it - maybe we have eliminated this? But I have a nasty feeling about
    // webform.
    $this->assign('totalTaxAmount', $totalTaxAmount ?? NULL);
    $this->assign('totalAmount', $totalAmount);
    $this->assign('pricesetFieldsCount', $participantCount);
    $this->assign('taxTerm', Civi::settings()->get('tax_term'));
    $this->assign('displayName', $displayName);
    // omitting contactImage from title for now since the summary overlay css doesn't work outside of our crm-container
    $this->setTitle(ts('View Event Registration for') . ' ' . $displayName);
    $this->assign('role', implode(',', $this->getParticipantValue('role_id:label')));
    // add Participant to Recent Items
    $title = $displayName . ' (' . implode(',', $this->getParticipantValue('role_id:label')) . ' - ' . $eventTitle . ')';
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
   * Get id of participant being acted on.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   */
  public function getParticipantID(): int {
    return (int) CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
  }

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {
    $this->addButtons([
      [
        'type' => 'cancel',
        'name' => ts('Done'),
        'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
        'isDefault' => TRUE,
      ],
    ]);
  }

}
