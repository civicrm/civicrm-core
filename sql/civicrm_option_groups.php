<?php

return [
  'preferred_communication_method' => CRM_Core_CodeGen_OptionGroup::create('preferred_communication_method')
    ->addMetadata([
      'title' => ts('Preferred Communication Method'),
    ])
    ->addValues(['label', 'name'], [
      ['Phone', 'Phone'],
      ['Email', 'Email'],
      ['Postal Mail', 'Postal Mail'],
      ['SMS', 'SMS'],
      ['Fax', 'Fax'],
    ]),

  'activity_type' => CRM_Core_CodeGen_OptionGroup::create('activity_type')
    ->addMetadata([
      'title' => ts('Activity Type'),
      'description' => ts('Activities track interactions with contacts. Some activity types are reserved for use by automated processes, others can be freely configured.'),
      'data_type' => 'Integer',
      'option_value_fields' => 'name,label,description,icon',
    ])
    ->addValues(['label', 'name', 'value', 'weight'], [
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
      [ts('Add Client To Case'), 'Add Client To Case', '27', 27-1, 'is_reserved' => '1', 'component_id' => '7', 'icon' => 'fa-users', 'description' => ''],

      // Activity Types for CiviCampaign
      // NOTE: These values+weights are out-of-step. Consider adjusting the weights.
      [ts('Survey'), 'Survey', '28', 28-1, 'is_reserved' => '1', 'component_id' => '9', 'description' => ''],
      [ts('Canvass'), 'Canvass', '29', 29-1, 'is_reserved' => '1', 'component_id' => '9', 'description' => ''],
      [ts('PhoneBank'), 'PhoneBank', '30', 30-1, 'is_reserved' => '1', 'component_id' => '9', 'description' => ''],
      [ts('WalkList'), 'WalkList', '31', 31-1, 'is_reserved' => '1', 'component_id' => '9', 'description' => ''],
      [ts('Petition Signature'), 'Petition', '32', 32-1, 'is_reserved' => '1', 'component_id' => '9', 'description' => ''],
      [ts('Mass SMS'), 'Mass SMS', '34', '34', 'filter' => '1', 'description' => ts('Mass SMS'), 'is_reserved' => '1'],

      // Additional Membership-related Activity Types
      [ts('Change Membership Status'), 'Change Membership Status', '35', '35', 'filter' => '1', 'description' => ts('Change Membership Status.'), 'is_reserved' => '1', 'component_id' => '3'],
      [ts('Change Membership Type'), 'Change Membership Type', '36', '36', 'filter' => '1', 'description' => ts('Change Membership Type.'), 'is_reserved' => '1', 'component_id' => '3'],

      [ts('Cancel Recurring Contribution'), 'Cancel Recurring Contribution', '37', '37', 'filter' => '1', 'is_reserved' => '1', 'component_id' => '2', 'description' => ''],
      [ts('Update Recurring Contribution Billing Details'), 'Update Recurring Contribution Billing Details', '38', '38', 'filter' => '1', 'is_reserved' => '1', 'component_id' => '2', 'description' => ''],
      [ts('Update Recurring Contribution'), 'Update Recurring Contribution', '39', '39', 'filter' => '1', 'is_reserved' => '1', 'component_id' => '2', 'description' => ''],

      [ts('Reminder Sent'), 'Reminder Sent', '40', '40', 'filter' => '1', 'is_reserved' => '1', 'description' => ''],

      // Activity Types for Financial Transactions Batch
      [ts('Export Accounting Batch'), 'Export Accounting Batch', '41', '41', 'filter' => '1', 'description' => ts('Export Accounting Batch'), 'is_reserved' => '1', 'component_id' => '2'],
      [ts('Create Batch'), 'Create Batch', '42', '42', 'filter' => '1', 'description' => ts('Create Batch'), 'is_reserved' => '1', 'component_id' => '2'],
      [ts('Edit Batch'), 'Edit Batch', '43', '43', 'filter' => '1', 'description' => ts('Edit Batch'), 'is_reserved' => '1', 'component_id' => '2'],

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
    ]),

  'gender' => CRM_Core_CodeGen_OptionGroup::create('gender')
    ->addMetadata([
      'title' => ts('Gender'),
      'description' => ts('CiviCRM is pre-configured with standard options for individual gender (Male, Female, Other). Modify these options as needed for your installation.'),
      'data_type' => 'Integer',
    ]),
  'instant_messenger_service' => CRM_Core_CodeGen_OptionGroup::create('instant_messenger_service')
    ->addMetadata([
      'title' => ts('Instant Messenger (IM) screen-names'),
      'description' => ts('Commonly-used messaging apps are listed here. Administrators may define as many additional providers as needed.'),
    ]),
  'mobile_provider' => CRM_Core_CodeGen_OptionGroup::create('mobile_provider')
    ->addMetadata([
      'title' => ts('Mobile Phone Providers'),
      'description' => ts('When recording mobile phone numbers for contacts, it may be useful to include the Mobile Phone Service Provider (e.g. Cingular, Sprint, etc.). CiviCRM is installed with the most commonly encountered service providers. Administrators may define as many additional providers as needed.'),
    ]),
  'individual_prefix' => CRM_Core_CodeGen_OptionGroup::create('individual_prefix')
    ->addMetadata([
      'title' => ts('Individual contact prefixes'),
      'description' => ts('CiviCRM is pre-configured with standard options for individual contact prefixes (Ms., Mr., Dr. etc.). Customize these options and add new ones as needed for your installation.'),
    ]),
  'individual_suffix' => CRM_Core_CodeGen_OptionGroup::create('individual_suffix')
    ->addMetadata([
      'title' => ts('Individual contact suffixes'),
      'description' => ts('CiviCRM is pre-configured with standard options for individual contact name suffixes (Jr., Sr., II etc.). Customize these options and add new ones as needed for your installation.'),
    ]),
  'acl_role' => CRM_Core_CodeGen_OptionGroup::create('acl_role')
    ->addMetadata([
      'title' => ts('ACL Role'),
    ]),
  'accept_creditcard' => CRM_Core_CodeGen_OptionGroup::create('accept_creditcard')
    ->addMetadata([
      'title' => ts('Accepted Credit Cards'),
      'description' => ts('The following credit card options will be offered to contributors using Online Contribution pages. You will need to verify which cards are accepted by your chosen Payment Processor and update these entries accordingly.IMPORTANT: These options do not control credit card/payment method choices for sites and/or contributors using the PayPal Express service (e.g. where billing information is collected on the Payment Processor\\\'s website).'),
    ]),
  'payment_instrument' => CRM_Core_CodeGen_OptionGroup::create('payment_instrument')
    ->addMetadata([
      'title' => ts('Payment Methods'),
      'description' => ts('You may choose to record the payment method used for each contribution and fee. Reserved payment methods are required - you may modify their labels but they can not be deleted (e.g. Check, Credit Card, Debit Card). If your site requires additional payment methods, you can add them here. You can associate each payment method with a Financial Account which specifies where the payment is going (e.g. a bank account for checks and cash).'),
      'data_type' => 'Integer',
    ]),
  'contribution_status' => CRM_Core_CodeGen_OptionGroup::create('contribution_status')
    ->addMetadata([
      'title' => ts('Contribution Status'),
      'is_locked' => '1',
    ]),
  'pcp_status' => CRM_Core_CodeGen_OptionGroup::create('pcp_status')
    ->addMetadata([
      'title' => ts('PCP Status'),
      'is_locked' => '1',
    ]),
  'pcp_owner_notify' => CRM_Core_CodeGen_OptionGroup::create('pcp_owner_notify')
    ->addMetadata([
      'title' => ts('PCP owner notifications'),
      'is_locked' => '1',
    ]),
  'participant_role' => CRM_Core_CodeGen_OptionGroup::create('participant_role')
    ->addMetadata([
      'title' => ts('Participant Role'),
      'description' => ts('Define participant roles for events here (e.g. Attendee, Host, Speaker...). You can then assign roles and search for participants by role.'),
      'data_type' => 'Integer',
    ]),
  'event_type' => CRM_Core_CodeGen_OptionGroup::create('event_type')
    ->addMetadata([
      'title' => ts('Event Type'),
      'description' => ts('Use Event Types to categorize your events. Event feeds can be filtered by Event Type and participant searches can use Event Type as a criteria.'),
      'data_type' => 'Integer',
    ]),
  'contact_view_options' => CRM_Core_CodeGen_OptionGroup::create('contact_view_options')
    ->addMetadata([
      'title' => ts('Contact View Options'),
      'is_locked' => '1',
    ]),
  'contact_smart_group_display' => CRM_Core_CodeGen_OptionGroup::create('contact_smart_group_display')
    ->addMetadata([
      'title' => ts('Contact Smart Group View Options'),
      'is_locked' => '1',
    ]),
  'contact_edit_options' => CRM_Core_CodeGen_OptionGroup::create('contact_edit_options')
    ->addMetadata([
      'title' => ts('Contact Edit Options'),
      'is_locked' => '1',
    ]),
  'advanced_search_options' => CRM_Core_CodeGen_OptionGroup::create('advanced_search_options')
    ->addMetadata([
      'title' => ts('Advanced Search Options'),
      'is_locked' => '1',
    ]),
  'user_dashboard_options' => CRM_Core_CodeGen_OptionGroup::create('user_dashboard_options')
    ->addMetadata([
      'title' => ts('User Dashboard Options'),
      'is_locked' => '1',
    ]),
  'address_options' => CRM_Core_CodeGen_OptionGroup::create('address_options')
    ->addMetadata([
      'title' => ts('Addressing Options'),
    ]),
  'group_type' => CRM_Core_CodeGen_OptionGroup::create('group_type')
    ->addMetadata([
      'title' => ts('Group Type'),
    ]),
  'custom_search' => CRM_Core_CodeGen_OptionGroup::create('custom_search')
    ->addMetadata([
      'title' => ts('Custom Search'),
    ]),
  'activity_status' => CRM_Core_CodeGen_OptionGroup::create('activity_status')
    ->addMetadata([
      'title' => ts('Activity Status'),
      'data_type' => 'Integer',
      'option_value_fields' => 'name,label,description,color',
    ]),
  'case_type' => CRM_Core_CodeGen_OptionGroup::create('case_type')
    ->addMetadata([
      'title' => ts('Case Type'),
    ]),
  'case_status' => CRM_Core_CodeGen_OptionGroup::create('case_status')
    ->addMetadata([
      'title' => ts('Case Status'),
      'option_value_fields' => 'name,label,description,color',
    ]),
  'participant_listing' => CRM_Core_CodeGen_OptionGroup::create('participant_listing')
    ->addMetadata([
      'title' => ts('Participant Listing'),
    ]),
  'safe_file_extension' => CRM_Core_CodeGen_OptionGroup::create('safe_file_extension')
    ->addMetadata([
      'title' => ts('Safe File Extension'),
    ]),
  'from_email_address' => CRM_Core_CodeGen_OptionGroup::create('from_email_address')
    ->addMetadata([
      'title' => ts('From Email Address'),
      'description' => ts('By default, CiviCRM uses the primary email address of the logged in user as the FROM address when sending emails to contacts. However, you can use this page to define one or more general Email Addresses that can be selected as an alternative. EXAMPLE: "Client Services" <clientservices@example.org>.'),
    ]),
  'mapping_type' => CRM_Core_CodeGen_OptionGroup::create('mapping_type')
    ->addMetadata([
      'title' => ts('Mapping Type'),
      'is_locked' => '1',
    ]),
  'wysiwyg_editor' => CRM_Core_CodeGen_OptionGroup::create('wysiwyg_editor')
    ->addMetadata([
      'title' => ts('WYSIWYG Editor'),
    ]),
  'recur_frequency_units' => CRM_Core_CodeGen_OptionGroup::create('recur_frequency_units')
    ->addMetadata([
      'title' => ts('Recurring Frequency Units'),
    ]),
  'phone_type' => CRM_Core_CodeGen_OptionGroup::create('phone_type')
    ->addMetadata([
      'title' => ts('Phone Type'),
    ]),
  'custom_data_type' => CRM_Core_CodeGen_OptionGroup::create('custom_data_type')
    ->addMetadata([
      'title' => ts('Custom Data Type'),
    ]),
  'visibility' => CRM_Core_CodeGen_OptionGroup::create('visibility')
    ->addMetadata([
      'title' => ts('Visibility'),
    ]),
  'mail_protocol' => CRM_Core_CodeGen_OptionGroup::create('mail_protocol')
    ->addMetadata([
      'title' => ts('Mail Protocol'),
    ]),
  'priority' => CRM_Core_CodeGen_OptionGroup::create('priority')
    ->addMetadata([
      'title' => ts('Priority'),
    ]),
  'redaction_rule' => CRM_Core_CodeGen_OptionGroup::create('redaction_rule')
    ->addMetadata([
      'title' => ts('Redaction Rule'),
    ]),
  'report_template' => CRM_Core_CodeGen_OptionGroup::create('report_template')
    ->addMetadata([
      'title' => ts('Report Template'),
    ]),
  'email_greeting' => CRM_Core_CodeGen_OptionGroup::create('email_greeting')
    ->addMetadata([
      'title' => ts('Email Greeting Type'),
    ]),
  'postal_greeting' => CRM_Core_CodeGen_OptionGroup::create('postal_greeting')
    ->addMetadata([
      'title' => ts('Postal Greeting Type'),
    ]),
  'addressee' => CRM_Core_CodeGen_OptionGroup::create('addressee')
    ->addMetadata([
      'title' => ts('Addressee Type'),
    ]),
  'contact_autocomplete_options' => CRM_Core_CodeGen_OptionGroup::create('contact_autocomplete_options')
    ->addMetadata([
      'title' => ts('Autocomplete Contact Search'),
      'is_locked' => '1',
    ]),
  'contact_reference_options' => CRM_Core_CodeGen_OptionGroup::create('contact_reference_options')
    ->addMetadata([
      'title' => ts('Contact Reference Autocomplete Options'),
      'is_locked' => '1',
    ]),
  'website_type' => CRM_Core_CodeGen_OptionGroup::create('website_type')
    ->addMetadata([
      'title' => ts('Website Type'),
    ]),
  'tag_used_for' => CRM_Core_CodeGen_OptionGroup::create('tag_used_for')
    ->addMetadata([
      'title' => ts('Tag Used For'),
      'is_locked' => '1',
    ]),
  'note_used_for' => CRM_Core_CodeGen_OptionGroup::create('note_used_for')
    ->addMetadata([
      'title' => ts('Note Used For'),
      'is_locked' => '1',
    ]),
  'currencies_enabled' => CRM_Core_CodeGen_OptionGroup::create('currencies_enabled')
    ->addMetadata([
      'title' => ts('Currencies Enabled'),
    ]),
  'event_badge' => CRM_Core_CodeGen_OptionGroup::create('event_badge')
    ->addMetadata([
      'title' => ts('Event Name Badge'),
    ]),
  'note_privacy' => CRM_Core_CodeGen_OptionGroup::create('note_privacy')
    ->addMetadata([
      'title' => ts('Privacy levels for notes'),
    ]),
  'campaign_type' => CRM_Core_CodeGen_OptionGroup::create('campaign_type')
    ->addMetadata([
      'title' => ts('Campaign Type'),
    ]),
  'campaign_status' => CRM_Core_CodeGen_OptionGroup::create('campaign_status')
    ->addMetadata([
      'title' => ts('Campaign Status'),
    ]),
  'system_extensions' => CRM_Core_CodeGen_OptionGroup::create('system_extensions')
    ->addMetadata([
      'title' => ts('CiviCRM Extensions'),
    ]),
  'mail_approval_status' => CRM_Core_CodeGen_OptionGroup::create('mail_approval_status')
    ->addMetadata([
      'title' => ts('CiviMail Approval Status'),
    ]),
  'engagement_index' => CRM_Core_CodeGen_OptionGroup::create('engagement_index')
    ->addMetadata([
      'title' => ts('Engagement Index'),
    ]),
  'cg_extend_objects' => CRM_Core_CodeGen_OptionGroup::create('cg_extend_objects')
    ->addMetadata([
      'title' => ts('Objects a custom group extends to'),
    ]),
  'paper_size' => CRM_Core_CodeGen_OptionGroup::create('paper_size')
    ->addMetadata([
      'title' => ts('Paper Size'),
    ]),
  'pdf_format' => CRM_Core_CodeGen_OptionGroup::create('pdf_format')
    ->addMetadata([
      'title' => ts('PDF Page Format'),
    ]),
  'label_format' => CRM_Core_CodeGen_OptionGroup::create('label_format')
    ->addMetadata([
      'title' => ts('Mailing Label Format'),
    ]),
  'activity_contacts' => CRM_Core_CodeGen_OptionGroup::create('activity_contacts')
    ->addMetadata([
      'title' => ts('Activity Contacts'),
      'is_locked' => '1',
    ]),
  'account_relationship' => CRM_Core_CodeGen_OptionGroup::create('account_relationship')
    ->addMetadata([
      'title' => ts('Account Relationship'),
    ]),
  'event_contacts' => CRM_Core_CodeGen_OptionGroup::create('event_contacts')
    ->addMetadata([
      'title' => ts('Event Recipients'),
    ]),
  'conference_slot' => CRM_Core_CodeGen_OptionGroup::create('conference_slot')
    ->addMetadata([
      'title' => ts('Conference Slot'),
    ]),
  'batch_type' => CRM_Core_CodeGen_OptionGroup::create('batch_type')
    ->addMetadata([
      'title' => ts('Batch Type'),
      'is_locked' => '1',
    ]),
  'batch_mode' => CRM_Core_CodeGen_OptionGroup::create('batch_mode')
    ->addMetadata([
      'title' => ts('Batch Mode'),
      'is_locked' => '1',
    ]),
  'batch_status' => CRM_Core_CodeGen_OptionGroup::create('batch_status')
    ->addMetadata([
      'title' => ts('Batch Status'),
      'is_locked' => '1',
    ]),
  'sms_api_type' => CRM_Core_CodeGen_OptionGroup::create('sms_api_type')
    ->addMetadata([
      'title' => ts('Api Type'),
    ]),
  'sms_provider_name' => CRM_Core_CodeGen_OptionGroup::create('sms_provider_name')
    ->addMetadata([
      'title' => ts('Sms Provider Internal Name'),
    ]),
  'auto_renew_options' => CRM_Core_CodeGen_OptionGroup::create('auto_renew_options')
    ->addMetadata([
      'title' => ts('Auto Renew Options'),
      'is_locked' => '1',
    ]),
  'financial_account_type' => CRM_Core_CodeGen_OptionGroup::create('financial_account_type')
    ->addMetadata([
      'title' => ts('Financial Account Type'),
    ]),
  'financial_item_status' => CRM_Core_CodeGen_OptionGroup::create('financial_item_status')
    ->addMetadata([
      'title' => ts('Financial Item Status'),
      'is_locked' => '1',
    ]),
  'label_type' => CRM_Core_CodeGen_OptionGroup::create('label_type')
    ->addMetadata([
      'title' => ts('Label Type'),
    ]),
  'name_badge' => CRM_Core_CodeGen_OptionGroup::create('name_badge')
    ->addMetadata([
      'title' => ts('Name Badge Format'),
    ]),
  'communication_style' => CRM_Core_CodeGen_OptionGroup::create('communication_style')
    ->addMetadata([
      'title' => ts('Communication Style'),
    ]),
  'msg_mode' => CRM_Core_CodeGen_OptionGroup::create('msg_mode')
    ->addMetadata([
      'title' => ts('Message Mode'),
    ]),
  'contact_date_reminder_options' => CRM_Core_CodeGen_OptionGroup::create('contact_date_reminder_options')
    ->addMetadata([
      'title' => ts('Contact Date Reminder Options'),
      'is_locked' => '1',
    ]),
  'wysiwyg_presets' => CRM_Core_CodeGen_OptionGroup::create('wysiwyg_presets')
    ->addMetadata([
      'title' => ts('WYSIWYG Editor Presets'),
    ]),
  'relative_date_filters' => CRM_Core_CodeGen_OptionGroup::create('relative_date_filters')
    ->addMetadata([
      'title' => ts('Relative Date Filters'),
    ]),
  'pledge_status' => CRM_Core_CodeGen_OptionGroup::create('pledge_status')
    ->addMetadata([
      'title' => ts('Pledge Status'),
      'is_locked' => '1',
    ]),
  'contribution_recur_status' => CRM_Core_CodeGen_OptionGroup::create('contribution_recur_status')
    ->addMetadata([
      'title' => ts('Recurring Contribution Status'),
      'is_locked' => '1',
    ]),
  'environment' => CRM_Core_CodeGen_OptionGroup::create('environment')
    ->addMetadata([
      'title' => ts('Environment'),
    ]),
  'activity_default_assignee' => CRM_Core_CodeGen_OptionGroup::create('activity_default_assignee')
    ->addMetadata([
      'title' => ts('Activity default assignee'),
    ]),
  'entity_batch_extends' => CRM_Core_CodeGen_OptionGroup::create('entity_batch_extends')
    ->addMetadata([
      'title' => ts('Entity Batch Extends'),
    ]),
  'file_type' => CRM_Core_CodeGen_OptionGroup::create('file_type')
    ->addMetadata([
      'title' => ts('File Type'),
      'data_type' => 'Integer',
    ]),

  // Encounter Medium Option Values (for case activities)
  'encounter_medium' => CRM_Core_CodeGen_OptionGroup::create('encounter_medium')
    ->addMetadata([
      // Shouldn't these be translated?
      'title' => 'Encounter Medium',
      'description' => 'Encounter medium for case activities (e.g. In Person, By Phone, etc.)',
    ])
    ->addValues(['label', 'name'], [
      [ts('In Person'), 'in_person'],
      [ts('Phone'), 'phone', 'is_default' => 1],
      [ts('Email'), 'email'],
      [ts('Fax'), 'fax'],
      [ts('Letter Mail'), 'letter_mail'],
    ])
    ->addDefaults([
      'is_reserved' => 1,
    ]),

  // CRM-13833
  'soft_credit_type' => CRM_Core_CodeGen_OptionGroup::create('soft_credit_type')
    ->addMetadata([
      'title' => ts('Soft Credit Types'),
    ])
    ->addValues(['label', 'value', 'name'], [
      [ts('In Honor of'), 1, 'in_honor_of', 'is_reserved' => 1],
      [ts('In Memory of'), 2, 'in_memory_of', 'is_reserved' => 1],
      [ts('Solicited'), 3, 'solicited', 'is_reserved' => 1, 'is_default' => 1],
      [ts('Household'), 4, 'household'],
      [ts('Workplace Giving'), 5, 'workplace'],
      [ts('Foundation Affiliate'), 6, 'foundation_affiliate'],
      [ts('3rd-party Service'), 7, '3rd-party_service'],
      [ts('Donor-advised Fund'), 8, 'donor-advised_fund'],
      [ts('Matched Gift'), 9, 'matched_gift'],
      [ts('Personal Campaign Page'), 10, 'pcp', 'is_reserved' => 1],
      [ts('Gift'), 11, 'gift', 'is_reserved' => 1],
    ])
    ->addDefaults([]),

  // dev/core#3783 Recent Items providers
  'recent_items_providers' => CRM_Core_CodeGen_OptionGroup::create('recent_items_providers')
    ->addMetadata([
      'title' => ts('Recent Items Providers'),
    ])
    ->addValues(['label', 'value', 'name'], [
      [ts('Contacts'), 'Contact', 'Contact'],
      [ts('Relationships'), 'Relationship', 'Relationship'],
      [ts('Activities'), 'Activity', 'Activity'],
      [ts('Notes'), 'Note', 'Note'],
      [ts('Groups'), 'Group', 'Group'],
      [ts('Cases'), 'Case', 'Case'],
      [ts('Contributions'), 'Contribution', 'Contribution'],
      [ts('Participants'), 'Participant', 'Participant'],
      [ts('Memberships'), 'Membership', 'Membership'],
      [ts('Pledges'), 'Pledge', 'Pledge'],
      [ts('Events'), 'Event', 'Event'],
      [ts('Campaigns'), 'Campaign', 'Campaign'],
    ])
    ->addDefaults([
      'description' => '',
      'filter' => NULL,
      'weight' => 1,
      // Why do these all have the same weight? Shrug.
      'is_reserved' => 1,
    ]),
];
