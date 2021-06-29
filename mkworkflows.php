<?php
$workflows = [
  ['CRM_Case', 'case_activity', 'msg_tpl_workflow_case', ['CRM_Case_BAO_Case::sendActivityCopy']],
  ['CRM_Contribute', 'contribution_dupalert', 'msg_tpl_workflow_contribution', ['CRM_Contribute_BAO_ContributionPage::sendMail']],
  ['CRM_Contribute', 'contribution_offline_receipt', 'msg_tpl_workflow_contribution', ['CRM_Contribute_Form_AdditionalInfo::emailReceipt']],
  ['CRM_Contribute', 'contribution_online_receipt', 'msg_tpl_workflow_contribution', ['CRM_Contribute_BAO_ContributionPage::sendMail']],
  ['CRM_Contribute', 'contribution_invoice_receipt', 'msg_tpl_workflow_contribution', ['CRM_Contribute_Form_Task_Invoice::printPDF']],
  ['CRM_Contribute', 'contribution_recurring_notify', 'msg_tpl_workflow_contribution', ['CRM_Contribute_BAO_ContributionPage::recurringNotify']],
  ['CRM_Contribute', 'contribution_recurring_cancelled', 'msg_tpl_workflow_contribution', ['CRM_Contribute_Form_CancelSubscription::postProcess']],
  ['CRM_Contribute', 'contribution_recurring_billing', 'msg_tpl_workflow_contribution', ['CRM_Contribute_Form_UpdateBilling::postProcess']],
  ['CRM_Contribute', 'contribution_recurring_edit', 'msg_tpl_workflow_contribution', ['CRM_Contribute_Form_UpdateSubscription::postProcess']],
  ['CRM_PCP', 'pcp_notify', 'msg_tpl_workflow_contribution', ['CRM_PCP_Form_Campaign::postProcess']],
  ['CRM_PCP', 'pcp_status_change', 'msg_tpl_workflow_contribution', ['CRM_PCP_BAO_PCP::sendStatusUpdate']],
  ['CRM_PCP', 'pcp_supporter_notify', 'msg_tpl_workflow_contribution', ['CRM_PCP_BAO_PCP::sendStatusUpdate']],
  ['CRM_PCP', 'pcp_owner_notify', 'msg_tpl_workflow_contribution', ['CRM_Contribute_BAO_ContributionSoft::pcpNotifyOwner']],
  ['CRM_Contribute', 'payment_or_refund_notification', 'msg_tpl_workflow_contribution', ['CRM_Financial_BAO_Payment::sendConfirmation']],
  ['CRM_Event', 'event_offline_receipt', 'msg_tpl_workflow_event', ['CRM_Event_Form_Participant::submit', 'CRM_Event_Form_ParticipantFeeSelection::emailReceipt']],
  ['CRM_Event', 'event_online_receipt', 'msg_tpl_workflow_event', ['CRM_Event_Form_SelfSvcTransfer::participantTransfer', 'CRM_Event_BAO_Event::sendMail']],
  ['CRM_Event', 'event_registration_receipt', 'msg_tpl_workflow_event', ['CRM_Event_Cart_Form_Checkout_Payment::emailReceipt']],
  ['CRM_Event', 'participant_cancelled', 'msg_tpl_workflow_event', ['CRM_Event_BAO_Participant::sendTransitionParticipantMail']],
  ['CRM_Event', 'participant_confirm', 'msg_tpl_workflow_event', ['CRM_Event_BAO_Participant::sendTransitionParticipantMail']],
  ['CRM_Event', 'participant_expired', 'msg_tpl_workflow_event', ['CRM_Event_BAO_Participant::sendTransitionParticipantMail']],
  ['CRM_Event', 'participant_transferred', 'msg_tpl_workflow_event', ['CRM_Event_BAO_Participant::sendTransitionParticipantMail']],
  ['CRM_Friend', 'friend', 'msg_tpl_workflow_friend', ['CRM_Friend_BAO_Friend::sendMail']],
  ['CRM_Member', 'membership_offline_receipt', 'msg_tpl_workflow_membership', ['CRM_Member_Form_MembershipRenewal::sendReceipt', 'CRM_Member_Form_Membership::emailReceipt', 'CRM_Batch_Form_Entry::emailReceipt']],
  ['CRM_Member', 'membership_online_receipt', 'msg_tpl_workflow_membership', ['CRM_Contribute_BAO_ContributionPage::sendMail']],
  ['CRM_Member', 'membership_autorenew_cancelled', 'msg_tpl_workflow_membership', ['CRM_Contribute_Form_CancelSubscription::postProcess']],
  ['CRM_Member', 'membership_autorenew_billing', 'msg_tpl_workflow_membership', ['CRM_Contribute_Form_UpdateBilling::postProcess']],
  ['CRM_Core', 'test_preview', 'msg_tpl_workflow_meta', ['CRM_Core_BAO_MessageTemplate::loadTemplate']],
  ['CRM_Campaign', 'petition_sign', 'msg_tpl_workflow_petition', ['CRM_Campaign_BAO_Petition::sendEmail']],
  ['CRM_Campaign', 'petition_confirmation_needed', 'msg_tpl_workflow_petition', ['CRM_Campaign_BAO_Petition::sendEmail']],
  ['CRM_Pledge', 'pledge_acknowledge', 'msg_tpl_workflow_pledge', ['CRM_Pledge_BAO_Pledge::sendAcknowledgment']],
  ['CRM_Pledge', 'pledge_reminder', 'msg_tpl_workflow_pledge', ['CRM_Pledge_BAO_Pledge::updatePledgeStatus']],
  ['CRM_UF', 'uf_notify', 'msg_tpl_workflow_uf', ['CRM_Core_BAO_UFGroup::commonSendMail']],
];

$tpl = <<<FOO
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
 * Define the inputs expected by an automated message.
 * FIXME: Describe the use-case for "{{NAME}}" more precisely.
{{REFS}} */
class {{CLASS}} extends CRM_Core_WorkflowMessage {
  const WORKFLOW = {{NAME}};
  const GROUP = {{GROUP}};
}
FOO;


foreach ($workflows as $task) {
  list ($ns, $name, $group, $refs) = $task;
  if (empty($ns) || empty($name) || empty($group) || empty($refs) || !is_array($refs)) {
    throw new \Exception('Malformed task: ' . json_encode($task));
  }

  $class = $ns . '_WorkflowMessage_' . CRM_Utils_String::convertStringToCamel($name);
  $file = str_replace('_', '/', $class) . '.php';
  printf("### FILE: %s\n", $file);
  $code = '<' . "?php\n" . strtr(trim($tpl) . "\n", [
    '{{CLASS}}' => $class,
    '{{NAME}}' => var_export($name, TRUE),
    '{{GROUP}}' => var_export($group, TRUE),
    '{{REFS}}' => " *\n" . implode('', array_map(function($ref) {
      return ' * @see ' . $ref . "\n";
    }, $refs)),
  ]);
  // echo $code;
  if (!is_dir(dirname($file))) {
    mkdir(dirname($file));
  }
  file_put_contents($file, $code);
}
