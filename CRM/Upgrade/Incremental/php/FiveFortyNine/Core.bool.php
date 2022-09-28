<?php
return [
  'civicrm_action_schedule' => [
    'is_repeat' => "DEFAULT 0",
    'is_active' => "DEFAULT 1 COMMENT 'Is this option active?'",
    'record_activity' => "DEFAULT 0 COMMENT 'Record Activity for this reminder?'",
  ],
  'civicrm_action_log' => [
    'is_error' => "DEFAULT 0 COMMENT 'Was there any error sending the reminder?'",
  ],
  'civicrm_address' => [
    'is_primary' => "DEFAULT 0 COMMENT 'Is this the primary address.'",
    'is_billing' => "DEFAULT 0 COMMENT 'Is this the billing address.'",
    'manual_geo_code' => "DEFAULT 0 COMMENT 'Is this a manually entered geo code'",
  ],
  'civicrm_country' => [
    'is_province_abbreviated' => "DEFAULT 0 COMMENT 'Should state/province be displayed as abbreviation for contacts from this country?'",
    'is_active' => "DEFAULT 1 COMMENT 'Is this Country active?'",
  ],
  'civicrm_county' => [
    'is_active' => "DEFAULT 1 COMMENT 'Is this County active?'",
  ],
  'civicrm_custom_group' => [
    'collapse_display' => "DEFAULT 0 COMMENT 'Will this group be in collapsed or expanded mode on initial display ?'",
    'is_active' => "DEFAULT 1 COMMENT 'Is this property active?'",
    'is_multiple' => "DEFAULT 0 COMMENT 'Does this group hold multiple values?'",
    'collapse_adv_display' => "DEFAULT 0 COMMENT 'Will this group be in collapsed or expanded mode on advanced search display ?'",
    'is_reserved' => "DEFAULT 0 COMMENT 'Is this a reserved Custom Group?'",
    'is_public' => "DEFAULT 1 COMMENT 'Is this property public?'",
  ],
  'civicrm_custom_field' => [
    'is_required' => "DEFAULT 0 COMMENT 'Is a value required for this property.'",
    'is_searchable' => "DEFAULT 0 COMMENT 'Is this property searchable.'",
    'is_search_range' => "DEFAULT 0 COMMENT 'Is this property range searchable.'",
    'is_view' => "DEFAULT 0 COMMENT 'Is this property set by PHP Code? A code field is viewable but not editable'",
    'in_selector' => "DEFAULT 0 COMMENT 'Should the multi-record custom field values be displayed in tab table listing'",
  ],
  'civicrm_dashboard' => [
    'is_active' => "DEFAULT 0 COMMENT 'Is this dashlet active?'",
    'is_reserved' => "DEFAULT 0 COMMENT 'Is this dashlet reserved?'",
  ],
  'civicrm_email' => [
    'is_primary' => "DEFAULT 0 COMMENT 'Is this the primary email address'",
    'is_billing' => "DEFAULT 0 COMMENT 'Is this the billing?'",
  ],
  'civicrm_im' => [
    'is_primary' => "DEFAULT 0 COMMENT 'Is this the primary IM for this contact and location.'",
    'is_billing' => "DEFAULT 0 COMMENT 'Is this the billing?'",
  ],
  'civicrm_job' => [
    'is_active' => "DEFAULT 1 COMMENT 'Is this job active?'",
  ],
  'civicrm_mail_settings' => [
    'is_default' => "DEFAULT 0 COMMENT 'whether this is the default set of settings for this domain'",
    'is_ssl' => "DEFAULT 1 COMMENT 'whether to use SSL or not'",
    'is_non_case_email_skipped' => "DEFAULT 0 COMMENT 'Enabling this option will have CiviCRM skip any emails that do not have the Case ID or Case Hash so that the system will only process emails that can be placed on case records. Any emails that are not processed will be moved to the ignored folder.'",
    'is_contact_creation_disabled_if_no_match' => "DEFAULT 0",
  ],
  'civicrm_menu' => [
    'is_active' => "DEFAULT 1 COMMENT 'Is this menu item active?'",
    'is_public' => "DEFAULT 0 COMMENT 'Is this menu accessible to the public?'",
    'is_exposed' => "DEFAULT 1 COMMENT 'Is this menu exposed to the navigation system?'",
    'is_ssl' => "DEFAULT 1 COMMENT 'Should this menu be exposed via SSL if enabled?'",
    'skipBreadcrumb' => "DEFAULT 0 COMMENT 'skip this url being exposed to breadcrumb'",
  ],
  'civicrm_msg_template' => [
    'is_active' => "DEFAULT 1",
    'is_default' => "DEFAULT 1 COMMENT 'is this the default message template for the workflow referenced by workflow_id?'",
    'is_reserved' => "DEFAULT 0 COMMENT 'is this the reserved message template which we ship for the workflow referenced by workflow_id?'",
    'is_sms' => "DEFAULT 0 COMMENT 'Is this message template used for sms?'",
  ],
  'civicrm_prevnext_cache' => [
    'is_selected' => "DEFAULT 0",
  ],
  'civicrm_phone' => [
    'is_primary' => "DEFAULT 0 COMMENT 'Is this the primary phone for this contact and location.'",
    'is_billing' => "DEFAULT 0 COMMENT 'Is this the billing?'",
  ],
  'civicrm_state_province' => [
    'is_active' => "DEFAULT 1 COMMENT 'Is this StateProvince active?'",
  ],
  'civicrm_tag' => [
    'is_selectable' => "DEFAULT 1 COMMENT 'Is this tag selectable / displayed'",
    'is_reserved' => "DEFAULT 0",
    'is_tagset' => "DEFAULT 0",
  ],
  'civicrm_openid' => [
    'is_primary' => "DEFAULT 0 COMMENT 'Is this the primary email for this contact and location.'",
  ],
  'civicrm_setting' => [
    'is_domain' => "DEFAULT 0 COMMENT 'Is this setting a contact specific or site wide setting?'",
  ],
  'civicrm_print_label' => [
    'is_default' => "DEFAULT 1 COMMENT 'Is this default?'",
    'is_active' => "DEFAULT 1 COMMENT 'Is this option active?'",
    'is_reserved' => "DEFAULT 1 COMMENT 'Is this reserved label?'",
  ],
  'civicrm_word_replacement' => [
    'is_active' => "DEFAULT 1 COMMENT 'Is this entry active?'",
  ],
  'civicrm_status_pref' => [
    'is_active' => "DEFAULT 1 COMMENT 'Is this status check active?'",
  ],
  'civicrm_uf_group' => [
    'is_active' => "DEFAULT 1 COMMENT 'Is this form currently active? If false, hide all related fields for all sharing contexts.'",
    'add_captcha' => "DEFAULT 0 COMMENT 'Should a CAPTCHA widget be included this Profile form.'",
    'is_map' => "DEFAULT 0 COMMENT 'Do we want to map results from this profile.'",
    'is_edit_link' => "DEFAULT 0 COMMENT 'Should edit link display in profile selector'",
    'is_uf_link' => "DEFAULT 0 COMMENT 'Should we display a link to the website profile in profile selector'",
    'is_update_dupe' => "DEFAULT 0 COMMENT 'Should we update the contact record if we find a duplicate'",
    'is_cms_user' => "DEFAULT 0 COMMENT 'Should we create a cms user for this profile '",
    'is_reserved' => "DEFAULT 0 COMMENT 'Is this group reserved for use by some other CiviCRM functionality?'",
    'is_proximity_search' => "DEFAULT 0 COMMENT 'Should we include proximity search feature in this profile search form?'",
    'add_cancel_button' => "DEFAULT 1 COMMENT 'Should a Cancel button be included in this Profile form.'",
  ],
  'civicrm_uf_field' => [
    'is_active' => "DEFAULT 1 COMMENT 'Is this field currently shareable? If false, hide the field for all sharing contexts.'",
    'is_view' => "DEFAULT 0 COMMENT 'the field is view only and not editable in user forms.'",
    'is_required' => "DEFAULT 0 COMMENT 'Is this field required when included in a user or registration form?'",
    'in_selector' => "DEFAULT 0 COMMENT 'Is this field included as a column in the selector table?'",
    'is_searchable' => "DEFAULT 0 COMMENT 'Is this field included search form of profile?'",
    'is_reserved' => "DEFAULT 0 COMMENT 'Is this field reserved for use by some other CiviCRM functionality?'",
    'is_multi_summary' => "DEFAULT 0 COMMENT 'Include in multi-record listing?'",
  ],
  'civicrm_uf_join' => [
    'is_active' => "DEFAULT 1 COMMENT 'Is this join currently active?'",
  ],
];
