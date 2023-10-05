<?php
/**
 * Class CRM_Core_CodeGen_Util_MessageTemplate
 */
class CRM_Core_CodeGen_Util_MessageTemplates {

  public static function assignSmartyVariables($smarty): void {
    // Assigns for message templates
    $ogNames = [
      'case'         => ts('Message Template Workflow for Cases', ['escape' => 'sql']),
      'contribution' => ts('Message Template Workflow for Contributions', ['escape' => 'sql']),
      'event'        => ts('Message Template Workflow for Events', ['escape' => 'sql']),
      'friend'       => ts('Message Template Workflow for Tell-a-Friend', ['escape' => 'sql']),
      'membership'   => ts('Message Template Workflow for Memberships', ['escape' => 'sql']),
      'meta'         => ts('Message Template Workflow for Meta Templates', ['escape' => 'sql']),
      'pledge'       => ts('Message Template Workflow for Pledges', ['escape' => 'sql']),
      'uf'           => ts('Message Template Workflow for Profiles', ['escape' => 'sql']),
      'petition'     => ts('Message Template Workflow for Petition', ['escape' => 'sql']),
    ];
    $ovNames = [
      'case' => [
        'case_activity' => ts('Cases - Send Copy of an Activity', ['escape' => 'sql']),
      ],
      'contribution' => [
        'contribution_dupalert'         => ts('Contributions - Duplicate Organization Alert', ['escape' => 'sql']),
        'contribution_offline_receipt'  => ts('Contributions - Receipt (off-line)', ['escape' => 'sql']),
        'contribution_online_receipt'   => ts('Contributions - Receipt (on-line)', ['escape' => 'sql']),
        'contribution_invoice_receipt'   => ts('Contributions - Invoice', ['escape' => 'sql']),
        'contribution_recurring_notify' => ts('Contributions - Recurring Start and End Notification', ['escape' => 'sql']),
        'contribution_recurring_cancelled' => ts('Contributions - Recurring Cancellation Notification', ['escape' => 'sql']),
        'contribution_recurring_billing' => ts('Contributions - Recurring Billing Updates', ['escape' => 'sql']),
        'contribution_recurring_edit'    => ts('Contributions - Recurring Updates', ['escape' => 'sql']),
        'pcp_notify'                    => ts('Personal Campaign Pages - Admin Notification', ['escape' => 'sql']),
        'pcp_status_change'             => ts('Personal Campaign Pages - Supporter Status Change Notification', ['escape' => 'sql']),
        'pcp_supporter_notify'          => ts('Personal Campaign Pages - Supporter Welcome', ['escape' => 'sql']),
        'pcp_owner_notify'              => ts('Personal Campaign Pages - Owner Notification', ['escape' => 'sql']),
        'payment_or_refund_notification' => ts('Additional Payment Receipt or Refund Notification', ['escape' => 'sql']),
      ],
      'event' => [
        'event_offline_receipt' => ts('Events - Registration Confirmation and Receipt (off-line)', ['escape' => 'sql']),
        'event_online_receipt'  => ts('Events - Registration Confirmation and Receipt (on-line)', ['escape' => 'sql']),
        'event_registration_receipt'  => ts('Events - Receipt only', ['escape' => 'sql']),
        'participant_cancelled' => ts('Events - Registration Cancellation Notice', ['escape' => 'sql']),
        'participant_confirm'   => ts('Events - Registration Confirmation Invite', ['escape' => 'sql']),
        'participant_expired'   => ts('Events - Pending Registration Expiration Notice', ['escape' => 'sql']),
        'participant_transferred'   => ts('Events - Registration Transferred Notice', ['escape' => 'sql']),
      ],
      'friend' => [
        'friend' => ts('Tell-a-Friend Email', ['escape' => 'sql']),
      ],
      'membership' => [
        'membership_offline_receipt' => ts('Memberships - Signup and Renewal Receipts (off-line)', ['escape' => 'sql']),
        'membership_online_receipt'  => ts('Memberships - Receipt (on-line)', ['escape' => 'sql']),
        'membership_autorenew_cancelled' => ts('Memberships - Auto-renew Cancellation Notification', ['escape' => 'sql']),
        'membership_autorenew_billing' => ts('Memberships - Auto-renew Billing Updates', ['escape' => 'sql']),
      ],
      'meta' => [
        'test_preview' => ts('Test-drive - Receipt Header', ['escape' => 'sql']),
      ],
      'pledge' => [
        'pledge_acknowledge' => ts('Pledges - Acknowledgement', ['escape' => 'sql']),
        'pledge_reminder'    => ts('Pledges - Payment Reminder', ['escape' => 'sql']),
      ],
      'uf' => [
        'uf_notify' => ts('Profiles - Admin Notification', ['escape' => 'sql']),
      ],
      'petition' => [
        'petition_sign' => ts('Petition - signature added', ['escape' => 'sql']),
        'petition_confirmation_needed' => ts('Petition - need verification', ['escape' => 'sql']),
      ],
    ];
    $smarty->assign('ogNames', $ogNames);
    $smarty->assign('ovNames', $ovNames);
    $dir = $smarty->get_template_vars()['gencodeXmlDir'] . '/templates/message_templates/sample';
    $templates = [];
    foreach (preg_grep('/\.tpl$/', scandir($dir)) as $filename) {
      $templates[] = ['name' => basename($filename, '.tpl'), 'filename' => "$dir/$filename"];
    }
    $smarty->assign('templates', $templates);
  }

}
