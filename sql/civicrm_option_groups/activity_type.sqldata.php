<?php

return CRM_Core_CodeGen_OptionGroup::create('activity_type', 'a/0002')
  ->addMetadata([
    'title' => ts('Activity Type'),
    'description' => ts('Activities track interactions with contacts. Some activity types are reserved for use by automated processes, others can be freely configured.'),
    'data_type' => 'Integer',
    'option_value_fields' => 'name,label,description,icon',
  ])
  ->addValueTable(['label', 'name', 'value', 'weight'], [
    [ts('Meeting'), 'Meeting', '1', '1', 'is_reserved' => '1', 'icon' => 'fa-slideshare'],
    [ts('Phone Call'), 'Phone Call', '2', '2', 'is_reserved' => '1', 'icon' => 'fa-phone'],
    [ts('Email'), 'Email', '3', '3', 'filter' => '1', 'description' => ts('Email sent.'), 'is_reserved' => '1', 'icon' => 'fa-envelope-o'],
    [ts('Outbound SMS'), 'SMS', '4', '4', 'filter' => '1', 'description' => ts('Text message (SMS) sent.'), 'is_reserved' => '1', 'icon' => 'fa-mobile'],
    [ts('Event Registration'), 'Event Registration', '5', '5', 'filter' => '1', 'description' => ts('Online or offline event registration.'), 'is_reserved' => '1', 'component_id' => '1'],
    [ts('Contribution'), 'Contribution', '6', '6', 'filter' => '1', 'description' => ts('Online or offline contribution.'), 'is_reserved' => '1', 'component_id' => '2'],
    [ts('Membership Signup'), 'Membership Signup', '7', '7', 'filter' => '1', 'description' => ts('Online or offline membership signup.'), 'is_reserved' => '1', 'component_id' => '3'],
    [ts('Membership Renewal'), 'Membership Renewal', '8', '8', 'filter' => '1', 'description' => ts('Online or offline membership renewal.'), 'is_reserved' => '1', 'component_id' => '3'],
    [ts('Tell a Friend'), 'Tell a Friend', '9', '9', 'filter' => '1', 'description' => ts('Send information about a contribution campaign or event to a friend.'), 'is_reserved' => '1'],
    [ts('Pledge Acknowledgment'), 'Pledge Acknowledgment', '10', '10', 'filter' => '1', 'description' => ts('Send Pledge Acknowledgment.'), 'is_reserved' => '1', 'component_id' => '6'],
    [ts('Pledge Reminder'), 'Pledge Reminder', '11', '11', 'filter' => '1', 'description' => ts('Send Pledge Reminder.'), 'is_reserved' => '1', 'component_id' => '6'],
    [ts('Inbound Email'), 'Inbound Email', '12', '12', 'filter' => '1', 'description' => ts('Inbound Email.'), 'is_reserved' => '1'],

    // Activity Types for case activities
    [ts('Open Case'), 'Open Case', '13', '13', 'is_reserved' => '1', 'component_id' => '7', 'icon' => 'fa-folder-open-o', 'description' => ''],
    [ts('Follow up'), 'Follow up', '14', '14', 'is_reserved' => '1', 'component_id' => '7', 'icon' => 'fa-share-square-o', 'description' => ''],
    [ts('Change Case Type'), 'Change Case Type', '15', '15', 'is_reserved' => '1', 'component_id' => '7', 'icon' => 'fa-random', 'description' => ''],
    [ts('Change Case Status'), 'Change Case Status', '16', '16', 'is_reserved' => '1', 'component_id' => '7', 'icon' => 'fa-pencil-square-o', 'description' => ''],
    [ts('Change Case Subject'), 'Change Case Subject', '53', '53', 'is_reserved' => '1', 'component_id' => '7', 'icon' => 'fa-pencil-square-o', 'description' => ''],
    [ts('Change Custom Data'), 'Change Custom Data', '33', '33', 'is_reserved' => '1', 'component_id' => '7', 'icon' => 'fa-table', 'description' => ''],

    [ts('Membership Renewal Reminder'), 'Membership Renewal Reminder', '17', '17', 'filter' => '1', 'description' => ts('offline membership renewal reminder.'), 'is_reserved' => '1', 'component_id' => '3'],
    [ts('Change Case Start Date'), 'Change Case Start Date', '18', '18', 'is_reserved' => '1', 'component_id' => '7', 'icon' => 'fa-calendar', 'description' => ''],
    [ts('Bulk Email'), 'Bulk Email', '19', '19', 'filter' => '1', 'description' => ts('Bulk Email Sent.'), 'is_reserved' => '1'],
    [ts('Assign Case Role'), 'Assign Case Role', '20', '20', 'is_reserved' => '1', 'component_id' => '7', 'icon' => 'fa-user-plus', 'description' => ''],
    [ts('Remove Case Role'), 'Remove Case Role', '21', '21', 'is_reserved' => '1', 'component_id' => '7', 'icon' => 'fa-user-times', 'description' => ''],
    [ts('Print/Merge Document'), 'Print PDF Letter', '22', '22', 'description' => ts('Export letters and other printable documents.'), 'is_reserved' => '1', 'icon' => 'fa-file-pdf-o'],
    [ts('Merge Case'), 'Merge Case', '23', '23', 'is_reserved' => '1', 'component_id' => '7', 'icon' => 'fa-compress', 'description' => ''],
    [ts('Reassigned Case'), 'Reassigned Case', '24', '24', 'is_reserved' => '1', 'component_id' => '7', 'icon' => 'fa-user-circle-o', 'description' => ''],
    [ts('Link Cases'), 'Link Cases', '25', '25', 'is_reserved' => '1', 'component_id' => '7', 'icon' => 'fa-link', 'description' => ''],
    [ts('Change Case Tags'), 'Change Case Tags', '26', '26', 'is_reserved' => '1', 'component_id' => '7', 'icon' => 'fa-tags', 'description' => ''],
    [ts('Add Client To Case'), 'Add Client To Case', '27', 27 - 1, 'is_reserved' => '1', 'component_id' => '7', 'icon' => 'fa-users', 'description' => ''],

    // Activity Types for CiviCampaign
    // FIXME: These values+weights are out-of-step. Consider adjusting the weights.
    [ts('Survey'), 'Survey', '28', 28 - 1, 'is_reserved' => '1', 'component_id' => '9', 'description' => ''],
    [ts('Canvass'), 'Canvass', '29', 29 - 1, 'is_reserved' => '1', 'component_id' => '9', 'description' => ''],
    [ts('PhoneBank'), 'PhoneBank', '30', 30 - 1, 'is_reserved' => '1', 'component_id' => '9', 'description' => ''],
    [ts('WalkList'), 'WalkList', '31', 31 - 1, 'is_reserved' => '1', 'component_id' => '9', 'description' => ''],
    [ts('Petition Signature'), 'Petition', '32', 32 - 1, 'is_reserved' => '1', 'component_id' => '9', 'description' => ''],
    [ts('Mass SMS'), 'Mass SMS', '34', '34', 'filter' => '1', 'description' => ts('Mass SMS'), 'is_reserved' => '1'],

    // Additional Membership-related Activity Types
    [ts('Change Membership Status'), 'Change Membership Status', '35', '35', 'filter' => '1', 'description' => ts('Change Membership Status.'), 'is_reserved' => '1', 'component_id' => '3'],
    [ts('Change Membership Type'), 'Change Membership Type', '36', '36', 'filter' => '1', 'description' => ts('Change Membership Type.'), 'is_reserved' => '1', 'component_id' => '3'],

    [ts('Cancel Recurring Contribution'), 'Cancel Recurring Contribution', '37', '37', 'filter' => '1', 'is_reserved' => '1', 'component_id' => '2', 'description' => ''],
    [ts('Update Recurring Contribution Billing Details'), 'Update Recurring Contribution Billing Details', '38', '38', 'filter' => '1', 'is_reserved' => '1', 'component_id' => '2', 'description' => ''],
    [ts('Update Recurring Contribution'), 'Update Recurring Contribution', '39', '39', 'filter' => '1', 'is_reserved' => '1', 'component_id' => '2', 'description' => ''],

    [ts('Reminder Sent'), 'Reminder Sent', '40', '40', 'filter' => '1', 'is_reserved' => '1', 'description' => ''],

    // Activity Types for Financial Transactions Batch
    // TODO: Shouldn't we have ts() for these descriptions?
    [ts('Export Accounting Batch'), 'Export Accounting Batch', '41', '41', 'filter' => '1', 'description' => 'Export Accounting Batch', 'is_reserved' => '1', 'component_id' => '2'],
    [ts('Create Batch'), 'Create Batch', '42', '42', 'filter' => '1', 'description' => 'Create Batch', 'is_reserved' => '1', 'component_id' => '2'],
    [ts('Edit Batch'), 'Edit Batch', '43', '43', 'filter' => '1', 'description' => 'Edit Batch', 'is_reserved' => '1', 'component_id' => '2'],

    // new sms options
    [ts('SMS delivery'), 'SMS delivery', '44', '44', 'filter' => '1', 'description' => ts('SMS delivery'), 'is_reserved' => '1'],
    [ts('Inbound SMS'), 'Inbound SMS', '45', '45', 'filter' => '1', 'description' => ts('Inbound SMS'), 'is_reserved' => '1'],

    // Activity types for particial payment
    [ts('Payment'), 'Payment', '46', '46', 'filter' => '1', 'description' => ts('Additional payment recorded for event or membership fee.'), 'is_reserved' => '1', 'component_id' => '2'],
    [ts('Refund'), 'Refund', '47', '47', 'filter' => '1', 'description' => ts('Refund recorded for event or membership fee.'), 'is_reserved' => '1', 'component_id' => '2'],

    [ts('Change Registration'), 'Change Registration', '48', '48', 'filter' => '1', 'description' => ts('Changes to an existing event registration.'), 'is_reserved' => '1', 'component_id' => '1'],

    [ts('Downloaded Invoice'), 'Downloaded Invoice', '49', '49', 'filter' => '1', 'description' => ts('Downloaded Invoice.'), 'is_reserved' => '1'],
    [ts('Emailed Invoice'), 'Emailed Invoice', '50', '50', 'filter' => '1', 'description' => ts('Emailed Invoice.'), 'is_reserved' => '1'],

    [ts('Contact Merged'), 'Contact Merged', '51', '51', 'filter' => '1', 'description' => ts('Contact Merged'), 'is_reserved' => '1'],
    [ts('Contact Deleted by Merge'), 'Contact Deleted by Merge', '52', '52', 'filter' => '1', 'description' => ts('Contact was merged into another contact'), 'is_reserved' => '1'],

    [ts('Failed Payment'), 'Failed Payment', '54', '54', 'filter' => '1', 'description' => ts('Failed Payment'), 'is_reserved' => '1', 'component_id' => '2'],
  ]);
