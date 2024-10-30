<?php

$subgroup = '{group.frontend_title}';
$suburl = '{subscribe.url}';
$welgroup = '{group.frontend_title}';
$unsubgroup = '{unsubscribe.group}';
$actresub = '{action.resubscribe}';
$actresuburl = '{action.resubscribeUrl}';
$resubgroup = '{resubscribe.group}';
$actunsub = '{action.unsubscribe}';
$actunsuburl = '{action.unsubscribeUrl}';
$domname = '{domain.name}';

return CRM_Core_CodeGen_SqlData::create('civicrm_mailing_component')
  ->addValues([
    [
      'name' => ts('Mailing Header'),
      "component_type" => 'Header',
      'subject' => ts('Descriptive Title for this Header'),
      'body_html' => ts('Sample Header for HTML formatted content.'),
      'body_text' => ts('Sample Header for TEXT formatted content.'),
    ],
    [
      'name' => ts("Mailing Footer"),
      "component_type" => "Footer",
      'subject' => ts("Descriptive Title for this Footer."),
      'body_html' => ts('Sample Footer for HTML formatted content<br/><a href="{action.optOutUrl}">Opt out of any future emails</a>  <br/> {domain.address}'),
      'body_text' => ts("Opt out of any future emails: {action.optOutUrl}\n{domain.address}"),
    ],
    [
      'name' => ts('Subscribe Message'),
      "component_type" => 'Subscribe',
      'subject' => ts('Subscription Confirmation Request'),
      'body_html' => ts("You have a pending subscription to the %1 mailing list. To confirm this subscription, reply to this email or click <a href=\"%2\">here</a>.", [
        1 => $subgroup,
        2 => $suburl,
      ]),
      'body_text' => ts('You have a pending subscription to the %1 mailing list. To confirm this subscription, reply to this email or click on this link: %2', [
        1 => $subgroup,
        2 => $suburl,
      ]),
    ],
    [
      'name' => ts('Welcome Message'),
      "component_type" => 'Welcome',
      'subject' => ts('Your Subscription has been Activated'),
      'body_html' => ts('Welcome. Your subscription to the %1 mailing list has been activated.', [1 => $welgroup]),
      'body_text' => ts('Welcome. Your subscription to the %1 mailing list has been activated.', [1 => $welgroup]),
    ],
    [
      'name' => ts('Unsubscribe Message'),
      'component_type' => 'Unsubscribe',
      'subject' => ts('Un-subscribe Confirmation'),
      'body_html' => ts("You have been un-subscribed from the following groups: %1. You can re-subscribe by mailing %2 or clicking <a href=\"%3\">here</a>.", [
        1 => $unsubgroup,
        2 => $actresub,
        3 => $actresuburl,
      ]),
      'body_text' => ts('You have been un-subscribed from the following groups: %1. You can re-subscribe by mailing %2 or clicking %3', [
        1 => $unsubgroup,
        2 => $actresub,
        3 => $actresuburl,
      ]),
    ],
    [
      'name' => ts('Resubscribe Message'),
      'component_type' => 'Resubscribe',
      'subject' => ts('Re-subscribe Confirmation'),
      'body_html' => ts('You have been re-subscribed to the following groups: %1. You can un-subscribe by mailing %2 or clicking <a href="%3">here</a>.', [
        1 => $resubgroup,
        2 => $actunsub,
        3 => $actunsuburl,
      ]),
      'body_text' => ts('You have been re-subscribed to the following groups: %1. You can un-subscribe by mailing %2 or clicking %3', [
        1 => $resubgroup,
        2 => $actunsub,
        3 => $actunsuburl,
      ]),
    ],
    [
      'name' => ts('Opt-out Message'),
      'component_type' => 'OptOut',
      'subject' => ts('Opt-out Confirmation'),
      'body_html' => ts('Your email address has been removed from %1 mailing lists.', [1 => $domname]),
      'body_text' => ts('Your email address has been removed from %1 mailing lists.', [1 => $domname]),
    ],
    [
      'name' => ts('Auto-responder'),
      'component_type' => 'Reply',
      'subject' => ts('Please Send Inquiries to Our Contact Email Address'),
      'body_html' => ts('This is an automated reply from an un-attended mailbox. Please send any inquiries to the contact email address listed on our web-site.'),
      'body_text' => ts('This is an automated reply from an un-attended mailbox. Please send any inquiries to the contact email address listed on our web-site.'),
    ],
  ])
  ->addDefaults([
    'is_default' => 1,
    'is_active' => 1,
  ]);
