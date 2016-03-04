<?php
#http://pastebin.com/U32jjBVu - D7 has renamed admin settings
function civicrm_engage_admin_settings($form_state = NULL) {
  $form['last_donate'] = array(
    '#type' => 'fieldset',
    '#title' => t('CiviEngage Walklist Last Donate presentation'),
    '#weight' => 0,
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );
  $form['last_donate']['civicrm_engage_no_canvas_period'] = array(
    '#title' => t("Exclusion Period"),
    '#type' => 'textfield',
    '#description' => t('If you wish to leave someone alone for a period after their last donation please enter it here (in months). 0 means disabled'),
    '#default_value' => variable_get('civicrm_engage_no_canvas_period', "0"),
  );
  $form['last_donate']['civicrm_engage_no_canvass_text'] = array(
    '#title' => t("Text when within exclusion period"),
    '#type' => 'textfield',
    '#description' => t('Text to put in if contact not to be canvassed'),
    '#default_value' => variable_get('civicrm_engage_no_canvass_text', "Do Not Canvass"),
  );
  $form['break_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('CiviEngage Walklist data grouping'),
    '#weight' => 0,
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );
  $form['break_settings']['civicrm_engage_groupbreak_odd_even'] = array(
    '#title' => t("Group break on odds / evens"),
    '#type' => 'checkbox',
    '#description' => t('Do you want a group-break between each side of the street'),
    '#default_value' => variable_get('civicrm_engage_groupbreak_odd_even', "1"),
  );
  $form['break_settings']['civicrm_engage_groupbreak_street'] = array(
    '#title' => t("Group break on street "),
    '#type' => 'checkbox',
    '#description' => t('Do you want a group-break between each street'),
    '#default_value' => variable_get('civicrm_engage_groupbreak_street', "1"),
  );
  $form['break_settings']['civicrm_engage_groupbreak_city'] = array(
    '#title' => t("Group break on city"),
    '#type' => 'checkbox',
    '#description' => t('Do you want a group-break between each city'),
    '#default_value' => variable_get('civicrm_engage_groupbreak_city', "1"),
  );
  $form['break_settings']['civicrm_engage_groupbreak_zip'] = array(
    '#title' => t("Group break on zip code"),
    '#type' => 'checkbox',
    '#description' => t('Do you want a group-break between each side of the street'),
    '#default_value' => variable_get('civicrm_engage_groupbreak_zip', "1"),
  );
  $form['break_settings']['civicrm_engage_lines_per_group'] = array(
    '#title' => t("Row limit per group"),
    '#type' => 'textfield',
    '#description' => t('Limit rows per group - ie. if you want to print out in blocks of 6'),
    '#default_value' => variable_get('civicrm_engage_lines_per_group', "6"),
  );
  $form['break_settings']['civicrm_engage_group_per_page'] = array(
    '#title' => t("New page for each group?"),
    '#type' => 'checkbox',
    '#description' => t('Do you want each group to have new page? (default is yes)'),
    '#default_value' => variable_get('civicrm_engage_group_per_page', "1"),
  );
  return system_settings_form($form);
}

function civicrm_engage_admin_load_data($form_state = NULL) {
  $subtypes_added = NULL;
  if(civicrm_engage_subtypes_added()) {
    $subtypes_added = ' <em>' . t("It appears that the contact sub types are present.") . '</em>';
  } 
  $custom_data_added = NULL;
  if(civicrm_engage_custom_data_added()) {
    $custom_data_added = ' <em>' . t("It appears that the custom data is present.") . '</em>';
  }
  $custom_voter_us_data_added = NULL;
  if(civicrm_engage_custom_voter_us_data_added()) {
    $custom_voter_us_data_added = ' <em>' . t("It appears that the US custom voter data is present.") . '</em>';
  }
  $address_parsing_enabled = NULL;
  if(civicrm_engage_address_parsing_enabled()) {
    $address_parsing_enabled = ' <em>' . t("It appears that address parsing is enabled.") . '</em>';
  } 
  $autocomplete_options_set = NULL;
  if(civicrm_engage_autocomplete_options_set()) {
    $autocomplete_options_set = ' <em>' . t("It appears that the autocomplete options are properly set.") . '</em>';
  }
  $form['load_data'] = array(
    '#type' => 'fieldset',
    '#title' => t('CiviEngage Load Data and Set Configuration Options'),
    '#weight' => 0,
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );

  $form['load_data']['contact_subtypes'] = array(
    '#type' => 'checkbox',
    '#title' => t('Add contact subtypes'),
    '#description' => t("Civicrm Engage depends on the presence of a Media, Funder, Elected Official, Media Outlet and Foundation contact subtypes. Check this box to add them. This step is not reversible.") . $subtypes_added
  ); 
  $form['load_data']['address_parsing'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable address parsing'),
    '#description' => t("Street parsing is required for walk lists because it needs to sort by even/odd address numbers.") . $address_parsing_enabled
  );
  $form['load_data']['autocomplete_options'] = array(
    '#type' => 'checkbox',
    '#title' => t('Set phone to autocomplete'),
    '#description' => t("Autocomplete options double as indicator of whether a field should show up in batch update. For civicrm engage to work properly, phone should be set to autocomplete so it shows up in batch update.") . $autocomplete_options_set
  );
  $form['load_data']['custom_data'] = array(
    '#type' => 'checkbox',
    '#title' => t('Add general custom groups, fields and other data'),
    '#description' => t("Civicrm Engage depends on a number of custom data groups and fields and profiles. Check this box to add them. This step is not reversible and will take several minutes to complete.") . $custom_data_added
  );
  $form['load_data']['custom_voter_us_data'] = array(
    '#type' => 'checkbox',
    '#title' => t('Add US voter custom groups, fields and other data'),
    '#description' => t("Civicrm Engage depends on voter-related fields being imported (to track congressional district, city district, etc.). Current only US-specific voter fields are available.") . $custom_voter_us_data_added
  );
  $form['load_data']['submit'] = array(
    '#type' => 'submit',
    '#value' => 'Submit'
  );
  return $form;
}


function civicrm_engage_admin_load_data_submit($form, &$form_state) {
  if($form_state['values']['contact_subtypes'] == 1) {
    civicrm_engage_load_contact_subtypes();
  }
  if($form_state['values']['custom_data'] == 1) {
    civicrm_engage_load_custom_data();
  }
  if($form_state['values']['custom_voter_us_data'] == 1) {
    civicrm_engage_load_custom_voter_us_data();
  }
  if($form_state['values']['address_parsing'] == 1) {
    civicrm_engage_enable_address_parsing();
  }
  if($form_state['values']['autocomplete_options'] == 1) {
    civicrm_engage_set_autocomplete_options();
  }
}

/**
 * Check to see if the subtypes have already been added.
 **/
function civicrm_engage_subtypes_added() {
  civicrm_initialize();
  require_once ("CRM/Contact/BAO/ContactType.php");
  $existing_types = CRM_Contact_BAO_ContactType::subTypeInfo();
  if(array_key_exists('Media_Outlet', $existing_types)) {
    return true;
  }
  return false;
}

/**
 * Check to see if the custom data has already been added. 
 **/
function civicrm_engage_custom_data_added() {
  civicrm_initialize();
  $sql = "SELECT is_active FROM civicrm_uf_group WHERE name = 'update_constituent_info'";
  $dao = CRM_Core_DAO::executeQuery($sql);
  $dao->fetch();
  if(!property_exists($dao, 'is_active')) return false;
  if($dao->is_active == 1) return true;
  return false;
}

/**
 * Check to see if the custom voter us data has already been added. 
 **/
function civicrm_engage_custom_voter_us_data_added() {
  civicrm_initialize();
  $sql = "SELECT is_active FROM civicrm_uf_group WHERE name = 'update_voter_info'";
  $dao = CRM_Core_DAO::executeQuery($sql);
  $dao->fetch();
  if(!property_exists($dao, 'is_active')) return false;
  if($dao->is_active == 1) return true;
  return false;
}

/**
 * Create contact subtypes which are referenced in the
 * xml file that is imported to boostrap the CiviCRM
 * Campaign component. This function is indempotent, so
 * it's run on enable.
 */
function civicrm_engage_load_contact_subtypes() {
  // parent_id : 1 is Individual, 3 is Organization
  $create = array(
    'Media_Contact' => array(
      'label' => 'Media Contact',
      'parent_id' => 1,
      'is_active' => 1,
      'name' => 'Media_Contact',
    ),
    'Funder_Contact' => array(
      'label' => 'Funder Contact',
      'parent_id' => 1,
      'is_active' => 1,
      'name' => 'Funder_Contact',
    ),
    'Elected_Official' => array(
      'label' => 'Elected Official',
      'parent_id' => 1,
      'is_active' => 1,
      'name' => 'Elected_Official',
    ),
    'Media_Outlet' => array(
      'label' => 'Media Outlet',
      'parent_id' => 3,
      'is_active' => 1,
      'name' => 'Media_Outlet',
    ),
    'Foundation' => array(
      'label' => 'Foundation',
      'parent_id' => 3,
      'is_active' => 1,
      'name' => 'Foundation',
    ),
  );
  require_once ("CRM/Contact/BAO/ContactType.php");
  $existing_types = CRM_Contact_BAO_ContactType::subTypeInfo();
  while (list($k) = each($existing_types)) {
    if (array_key_exists($k, $create)) {
      drupal_set_message(t("Not creating @type, already exists.", array("@type" => $k)));
      unset($create[$k]);
    }
  }
  $contact_type = new CRM_Contact_BAO_ContactType();
  while (list($k, $v) = each($create)) {
    drupal_set_message(t("Creating %c", array('%c' => $k)));
    $contact_type->add($v);
  }
}

function civicrm_engage_load_custom_data() {
  drupal_set_message("Loading default civicrm_engage custom data.");
  $xml_file = civicrm_engage_get_absolute_module_path() . '/CustomGroupData.xml';
  require_once 'CRM/Utils/Migrate/Import.php';
  $import = new CRM_Utils_Migrate_Import();
  $import->run($xml_file);

  // The xml file sets up a field with a contact reference limited to the staff
  // group (staff responsible). So, we have to make sure the staff group exists
  // and if not create it and update the id number in the staff responsible
  // field definition.
  drupal_set_message("Creating staff group.");
  return civicrm_engage_load_staff_group();
}

/**
 * Create group that will be used to populate the responsible
 * staff custom data field.
 **/
function civicrm_engage_load_staff_group() {
  // Record the id of the group we create so we can ensure that the
  // staff responsible contact reference field is restricted to the 
  // right group.
  $id = NULL;
  try {
    // Check if it exists already
    $params = array('name' => 'staff');
    $result = civicrm_api3('Group', 'Get', $params);
    if($result['count'] > 0) {
      // We already have the group in place.
      drupal_set_message(t("Staff group already exists."));
      $row = array_pop($result['values']);
      $id = $row['id'];
    }
    else {
      // Otherwise, we have to add it.
      $params = array(
        'name' => 'staff',
        'title' => 'Staff',
        'description' => 'Contacts in this group are used to populate the staff responsible field.',
        'is_active' => 1,
        'group_type' => '2',
        'is_hidden' => '0',
        'is_reserved' => '0'
      );
      $result = civicrm_api3('Group', 'create', $params);
      drupal_set_message(t("Staff group created."));
      $row = array_pop($result['values']);
      $id = $row['id'];
    }
    if($id) {
      $sql = "UPDATE civicrm_custom_field SET filter = 'action=lookup&group=%0' WHERE
        name = 'staff_responsible' OR name = 'event_contact_person'";
      $params = array(
        0 => array($id, 'Integer')
      );
      CRM_Core_DAO::executeQuery($sql, $params);
    }
  }
  catch (CiviCRM_API3_Exception $e) {
    drupal_set_message(t("Failed to load staff group."));
    drupal_set_message($e->getMessage());
    return FALSE;
  }
  return TRUE;
}

function civicrm_engage_load_custom_voter_us_data() {
  drupal_set_message("Loading default civicrm_engage US custom voter data.");
  $xml_file = civicrm_engage_get_absolute_module_path() . '/Voter.us.xml';
  require_once 'CRM/Utils/Migrate/Import.php';
  $import = new CRM_Utils_Migrate_Import();
  $import->run($xml_file);
}

function civicrm_engage_address_parsing_enabled() {
  civicrm_initialize();
  include_once 'CRM/Core/BAO/Setting.php';
  $address_options = CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'address_options',
    TRUE, NULL, TRUE
  );
  if($address_options['street_address_parsing'] == 1) {
    return TRUE;
  }
  return FALSE;
}

/**
 * Helper file to get path to civicrm_engage directory
 **/
function civicrm_engage_get_absolute_module_path() {
  // we need to build the path to the Voter.us.xml file
  // shipped in the civicrm_engage directory. civicrm_engage could
  // be installed outside of the civicrm root, so we can't rely on
  // that contstant.

  // instead... start with the drupal root
  $root = getcwd();
  // and then added the relative path to the module
  return $root . '/' . drupal_get_path('module', 'civicrm_engage');
}

/**
 * Street parsing is required for walk lists because it needs to
 * sort by even/odd address numbers so, when canvassing a street
 * in which even addresses are on one side and odd on the other, you
 * can divide the task between two people with two different lists.
 */
function civicrm_engage_enable_address_parsing() {
  civicrm_initialize();
  include_once 'CRM/Core/BAO/Setting.php';
  $address_options = CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'address_options',
    TRUE, NULL, TRUE
  );
  $address_options['street_address_parsing'] = 1;
  CRM_Core_BAO_Setting::setValueOption(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'address_options',
    $address_options
  );
  drupal_set_message("Addressing parsing options saved.");
}

function civicrm_engage_autocomplete_options_set() {
  civicrm_initialize();
  include_once 'CRM/Core/BAO/Setting.php';
  $contact_autocomplete_options = CRM_Core_BAO_Setting::valueOptions(
    CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'contact_autocomplete_options',
    TRUE, NULL, TRUE
  );
  if($contact_autocomplete_options['phone'] == 1) {
    return TRUE;
  }
  return FALSE;
}

/**
 * Check Phone Number for auto complete. This setting doubles for batch
 * update, and when making a phone list in CiviCampaign you really need
 * to have the phone number included.
 */
function civicrm_engage_set_autocomplete_options() {
  civicrm_initialize();
  include_once 'CRM/Core/BAO/Setting.php';
  $contact_autocomplete_options = CRM_Core_BAO_Setting::valueOptions(
    CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'contact_autocomplete_options',
    TRUE, NULL, TRUE
  );
  $contact_autocomplete_options['phone'] = 1;
  CRM_Core_BAO_Setting::setValueOption(
    CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'contact_autocomplete_options',
    $contact_autocomplete_options
  );
  drupal_set_message(t("Autocomplete options have been saved."));
}
