<?php

/**
 * If you edit this class, you need to run Regen in Github actions to regenerate the sample data in civicrm_generated.mysql
 */

use Civi\Api4\ACL;
use Civi\Api4\ACLEntityRole;
use Civi\Api4\Contact;
use Civi\Api4\Email;
use Civi\Api4\Group;
use Civi\Api4\OptionValue;

class CRM_Core_CodeGen_GenerateData {

  /**
   * Constants
   */

  // Set ADD_TO_DB = FALSE to do a dry run
  const ADD_TO_DB = TRUE;

  const DATA_FILENAME = "sample_data.xml";
  const NUM_DOMAIN = 1;
  const NUM_CONTACT = 200;
  const INDIVIDUAL_PERCENT = 80;
  const HOUSEHOLD_PERCENT = 10;
  const ORGANIZATION_PERCENT = 10;
  const NUM_INDIVIDUAL_PER_HOUSEHOLD = 4;
  const NUM_ACTIVITY = 150;

  // Location types from the table crm_location_type
  const HOME = 1;
  const WORK = 2;
  const MAIN = 3;
  const OTHER = 4;

  /**
   * Class constructor
   *
   * @param string|int $seed
   *   Some scalar value used as the starting point for random-number generation.
   * @param int $time
   *   A timestamp; some facsimile of "now".
   */
  public function __construct($seed, $time) {
    // initialize all the vars
    $this->seed = $seed;
    $this->time = $time;
    $this->numIndividual = self::INDIVIDUAL_PERCENT * self::NUM_CONTACT / 100;
    $this->numHousehold = self::HOUSEHOLD_PERCENT * self::NUM_CONTACT / 100;
    $this->numOrganization = self::ORGANIZATION_PERCENT * self::NUM_CONTACT / 100;
    $this->numStrictIndividual = $this->numIndividual - ($this->numHousehold * self::NUM_INDIVIDUAL_PER_HOUSEHOLD);

    // Parse data file
    foreach ((array) simplexml_load_file(self::getCivicrmDir() . '/sql/' . self::DATA_FILENAME) as $key => $val) {
      $val = (array) $val;
      $this->sampleData[$key] = (array) $val['item'];
    }
    // Init DB
    $config = CRM_Core_Config::singleton();

    // Relationship types indexed by name_a_b from the table civicrm_relationship_type
    $this->relTypes = CRM_Utils_Array::index(['name_a_b'], CRM_Core_PseudoConstant::relationshipType('name'));
  }

  /**
   * Create a full, standard set of random data.
   */
  public function generateAll() {
    $this->initID();
    $this->generate('Domain');
    $this->generate('Contact');
    $this->generate('Individual');
    $this->generate('Household');
    $this->generate('Organization');
    $this->generate('Relationship');
    $this->generate('EntityTag');
    $this->generate('Group');
    $this->generate('ACL');
    $this->generate('Note');
    $this->generate('Activity');
    $this->generate('Event');
    $this->generate('Contribution');
    $this->generate('ContributionLineItem');
    $this->generate('Membership');
    $this->generate('MembershipPayment');
    $this->generate('MembershipLog');
    $this->generate('PCP');
    $this->generate('SoftContribution');
    $this->generate('Pledge');
    $this->generate('PledgePayment');
    $this->generate('Participant');
    $this->generate('ParticipantPayment');
    $this->generate('LineItemParticipants');
    $this->generate('AccountingEntries');
  }

  /**
   * Write a log message.
   *
   * @param string $message
   */
  public function write($message) {
    echo $message;
  }

  /**
   * Public wrapper for calling private "add" functions
   * Provides user feedback
   *
   * @param $itemName
   */
  public function generate($itemName) {
    $this->write("Generating $itemName\n");
    $fn = "add$itemName";
    $this->$fn();
  }

  /**
   * this function creates arrays for the following
   *
   * domain id
   * contact id
   * contact_location id
   * contact_contact_location id
   * contact_email uuid
   * contact_phone_uuid
   * contact_instant_message uuid
   * contact_relationship uuid
   * contact_task uuid
   * contact_note uuid
   */
  public function initID() {
    // get the domain and contact id arrays
    $this->domain = range(1, self::NUM_DOMAIN);
    $this->domain = $this->shuffle($this->domain);

    // Get first contact id
    $this->startCid = $cid = CRM_Core_DAO::singleValueQuery("SELECT MAX(id) FROM civicrm_contact");
    $this->contact = range($cid + 1, $cid + self::NUM_CONTACT);
    $this->contact = $this->shuffle($this->contact);

    // get the individual, household  and organizaton contacts
    $offset = 0;
    $this->Individual = array_slice($this->contact, $offset, $this->numIndividual);
    $offset += $this->numIndividual;
    $this->Household = array_slice($this->contact, $offset, $this->numHousehold);
    $offset += $this->numHousehold;
    $this->Organization = array_slice($this->contact, $offset, $this->numOrganization);

    // get the strict individual contacts (i.e individual contacts not belonging to any household)
    $this->strictIndividual = array_slice($this->Individual, 0, $this->numStrictIndividual);

    // get the household to individual mapping array
    $this->householdIndividual = array_slice($this->Individual, $this->numStrictIndividual);
    $this->householdIndividual = array_chunk($this->householdIndividual, self::NUM_INDIVIDUAL_PER_HOUSEHOLD);
    $this->householdIndividual = array_combine($this->Household, $this->householdIndividual);
  }

  /*
   * private members
   *
   */

  /**
   * @var int
   */
  private $seed;

  /**
   * enum's from database
   *
   * @var array
   */
  private $preferredCommunicationMethod = ['1', '2', '3', '4', '5'];

  private $contactType = ['Individual', 'Household', 'Organization'];

  private $phoneType = ['1', '2', '3', '4'];

  /**
   * customizable enums (foreign keys)
   *
   * @var array
   */
  private $prefix = [
    // Female
    1 => [
      1 => 'Mrs.',
      2 => 'Ms.',
      4 => 'Dr.',
    ],
    // Male
    2 => [
      3 => 'Mr.',
      4 => 'Dr.',
    ],
  ];

  /**
   * @var array
   */
  private $suffix = [1 => 'Jr.', 2 => 'Sr.', 3 => 'II', 4 => 'III'];

  private $gender = [1 => 'female', 2 => 'male'];

  /**
   * store domain id's
   *
   * @var array
   */
  private $domain = [];

  /**
   * store contact id's
   *
   * @var array
   */
  private $contact = [];

  private $Individual = [];

  private $Household = [];

  private $Organization = [];

  // store which contacts have a location entity

  /**
   * for automatic management of is_primary field
   *
   * @var array
   */
  private $location = [
    'Email' => [],
    'Phone' => [],
    'Address' => [],
  ];

  /**
   * stores the strict individual id and household id to individual id mapping
   *
   * @var array
   */
  private $strictIndividual = [];

  private $householdIndividual = [];

  private $householdName = [];

  /**
   * sample data in xml format
   *
   * @var array
   */
  private $sampleData = [];

  /**
   * private vars
   *
   * @var array
   */
  private $startCid;

  private $numIndividual = 0;

  private $numHousehold = 0;

  private $numOrganization = 0;

  private $numStrictIndividual = 0;

  private $stateMap = [];

  private $states = [];

  private $groupMembershipStatus = ['Added', 'Removed', 'Pending'];

  private $subscriptionHistoryMethod = ['Admin', 'Email'];

  private $deceasedContactIds = [];

  private $time;

  private $relTypes;

  /*********************************
   * private methods
   * *******************************
   */

  /**
   * Random number generator.
   *
   * All other random() functions should derive from this.
   *
   * This is very weak RNG. The goal is to provide a reproducible sequence of
   * random-ish values for generating dummy-data.
   *
   * @param int $min
   * @param int $max
   *
   * @return int
   */
  private function randomInt($min, $max) {
    $range = min(1 + $max - $min, mt_getrandmax());
    $this->seed = md5($this->seed . chr(0) . $min . chr(0) . $max);
    return $min + (hexdec(substr($this->seed, 20, 8)) % $range);
  }

  /**
   * Get a randomly generated string.
   *
   * @param int $size
   *
   * @return string
   */
  private function randomString($size = 32) {
    $string = "";

    // get an ascii code for each character
    for ($i = 0; $i < $size; $i++) {
      $random_int = $this->randomInt(65, 122);
      if (($random_int < 97) && ($random_int > 90)) {
        // if ascii code between 90 and 97 substitute with space
        $random_int = 32;
      }
      $random_char = chr($random_int);
      $string .= $random_char;
    }
    return $string;
  }

  /**
   * @return string
   */
  private function randomChar() {
    return chr($this->randomInt(65, 90));
  }

  /**
   * Get a random item from the sample data or any other array
   *
   * @param array|string $items if string, used as key for sample data,
   *   if array, used as data source
   *
   * @return mixed (element from array)
   *
   * @private
   */
  private function randomItem($items) {
    if (!is_array($items)) {
      $key = $items;
      $items = $this->sampleData[$key];
    }
    if (!$items) {
      $this->write("Error: no items found for '$key'\n");
      return FALSE;
    }
    return $items[$this->randomInt(0, count($items) - 1)];
  }

  /**
   * @param $items
   *
   * @return mixed
   */
  private function randomIndex($items) {
    return $this->randomItem(array_keys($items));
  }

  /**
   * @param $items
   *
   * @return array
   */
  private function randomKeyValue($items) {
    $key = $this->randomIndex($items);
    return [$key, $items[$key]];
  }

  private function shuffle($array) {
    for ($i = count($array) - 1; $i >= 1; $i--) {
      $j = $this->randomInt(0, $i);
      $tmp = $array[$i];
      $array[$i] = $array[$j];
      $array[$j] = $tmp;
    }
    return $array;
  }

  /**
   * @param $chance
   *
   * @return int
   */
  private function probability($chance) {
    if ($this->randomInt(0, 100) < ($chance * 100)) {
      return 1;
    }
    return 0;
  }

  /**
   * Generate a random date.
   *
   *   If both $startDate and $endDate are defined generate
   *   date between them.
   *
   *   If only startDate is specified then date generated is
   *   between startDate + 1 year.
   *
   *   if only endDate is specified then date generated is
   *   between endDate - 1 year.
   *
   *   if none are specified - date is between today - 1year
   *   and today
   *
   * @param int $startDate Start Date in Unix timestamp
   * @param int $endDate End Date in Unix timestamp
   *
   * @access private
   *
   * @return string randomly generated date in the format "Ymd"
   *
   */
  private function randomDate($startDate = 0, $endDate = 0) {

    // number of seconds per year
    $numSecond = 31536000;
    $dateFormat = "YmdHis";
    $today = $this->time;

    // both are defined
    if ($startDate && $endDate) {
      return date($dateFormat, $this->randomInt($startDate, $endDate));
    }

    // only startDate is defined
    if ($startDate) {
      return date($dateFormat, $this->randomInt($startDate, $startDate + $numSecond));
    }

    // only endDate is defined
    if ($startDate) {
      return date($dateFormat, $this->randomInt($endDate - $numSecond, $endDate));
    }

    // none are defined
    return date($dateFormat, $this->randomInt($today - $numSecond, $today));
  }

  /**
   * Automatically manage the is_primary field by tracking which contacts have
   * each item
   *
   * @param $cid
   * @param $type
   *
   * @return int
   */
  private function isPrimary($cid, $type) {
    if (empty($this->location[$type][$cid])) {
      $this->location[$type][$cid] = TRUE;
      return 1;
    }
    return 0;
  }

  /**
   * Execute a query unless we are doing a dry run
   * Note: this wrapper should not be used for SELECT queries
   *
   * @param $query
   * @param array $params
   *
   * @return \CRM_Core_DAO
   */
  private function _query($query, $params = []) {
    if (self::ADD_TO_DB) {
      return CRM_Core_DAO::executeQuery($query, $params);
    }
  }

  /**
   * Call dao insert method unless we are doing a dry run
   *
   * @param $dao
   */
  private function _insert(&$dao) {
    if (self::ADD_TO_DB) {
      $dao->insert();
    }
  }

  /**
   * Call dao update method unless we are doing a dry run
   *
   * @param $dao
   */
  private function _update(&$dao) {
    if (self::ADD_TO_DB) {
      $dao->update();
    }
  }

  /**
   * Add core DAO object
   *
   * @param $type
   * @param $params
   */
  private function _addDAO($type, $params) {
    $daoName = "CRM_Core_DAO_$type";
    $obj = new $daoName();
    foreach ($params as $key => $value) {
      $obj->$key = $value;
    }
    if (isset($this->location[$type])) {
      $obj->is_primary = $this->isPrimary($params['contact_id'], $type);
    }
    $this->_insert($obj);
  }

  /**
   * Fetch contact type based on stored mapping
   *
   * @param $id
   *
   * @return string $type
   */
  private function getContactType($id) {
    foreach (['Individual', 'Household', 'Organization'] as $type) {
      if (in_array($id, $this->$type)) {
        return $type;
      }
    }
  }

  /**
   * This method adds NUM_DOMAIN domains and then adds NUM_REVISION
   * revisions for each domain with the latest revision being the last one..
   */
  private function addDomain() {

    /* Add a location for domain 1 */

    $domain = new CRM_Core_DAO_Domain();
    for ($id = 2; $id <= self::NUM_DOMAIN; $id++) {
      // domain name is pretty simple. it is "Domain $id"
      $domain->name = "Domain $id";
      $domain->description = "Description $id";
      $domain->contact_name = $this->randomName();

      // insert domain
      $this->_insert($domain);
    }
  }

  /**
   * @return string
   */
  public function randomName() {
    $first_name = $this->randomItem(($this->probability(.5) ? 'fe' : '') . 'male_name');
    $middle_name = ucfirst($this->randomChar());
    $last_name = $this->randomItem('last_name');
    return "$first_name $middle_name. $last_name";
  }

  /**
   * This method adds data to the contact table
   *
   * id - from $contact
   * contact_type 'Individual' 'Household' 'Organization'
   * preferred_communication (random 1 to 3)
   */
  private function addContact() {
    $contact = new CRM_Contact_DAO_Contact();
    $cid = $this->startCid;

    for ($id = $cid + 1; $id <= $cid + self::NUM_CONTACT; $id++) {
      $contact->contact_type = $this->getContactType($id);
      $contact->do_not_phone = $this->probability(.2);
      $contact->do_not_email = $this->probability(.2);
      $contact->do_not_mail = $this->probability(.2);
      $contact->do_not_trade = $this->probability(.2);
      $contact->preferred_communication_method = NULL;
      if ($this->probability(.5)) {
        $contact->preferred_communication_method = CRM_Core_DAO::VALUE_SEPARATOR . $this->randomItem($this->preferredCommunicationMethod) . CRM_Core_DAO::VALUE_SEPARATOR;
      }
      $contact->source = 'Sample Data';
      $this->_insert($contact);
    }
  }

  /**
   * addIndividual()
   *
   * This method adds individual's data to the contact table
   *
   * The following fields are generated and added.
   *
   * contact_uuid - individual
   * contact_rid - latest one
   * first_name 'First Name $contact_uuid'
   * middle_name 'Middle Name $contact_uuid'
   * last_name 'Last Name $contact_uuid'
   * job_title 'Job Title $contact_uuid'
   *
   */
  private function addIndividual() {

    $contact = new CRM_Contact_DAO_Contact();
    $year = 60 * 60 * 24 * 365.25;
    $now = $this->time;

    foreach ($this->Individual as $cid) {
      $contact->is_deceased = $contact->gender_id = $contact->birth_date = $contact->deceased_date = $email = NULL;
      [$gender_id, $gender] = $this->randomKeyValue($this->gender);
      $birth_date = $this->randomInt($now - 90 * $year, $now - 10 * $year);

      $contact->last_name = $this->randomItem('last_name');

      // Manage household names
      if (!in_array($contact->id, $this->strictIndividual)) {
        // Find position in household
        foreach ($this->householdIndividual as $householdId => $house) {
          foreach ($house as $position => $memberId) {
            if ($memberId == $cid) {
              break 2;
            }
          }
        }
        // Head of household: set name
        if (empty($this->householdName[$householdId])) {
          $this->householdName[$householdId] = $contact->last_name;
        }
        // Kids get household name, spouse might get it
        if ($position > 1 || $this->probability(.5)) {
          $contact->last_name = $this->householdName[$householdId];
        }
        elseif ($this->householdName[$householdId] != $contact->last_name) {
          // Spouse might hyphenate name
          if ($this->probability(.5)) {
            $contact->last_name .= '-' . $this->householdName[$householdId];
          }
          // Kids might hyphenate name
          else {
            $this->householdName[$householdId] .= '-' . $contact->last_name;
          }
        }
        // Sensible ages and genders
        $offset = $this->randomInt($now - 40 * $year, $now);
        // Parents
        if ($position < 2) {
          $birth_date = $this->randomInt($offset - 35 * $year, $offset - 20 * $year);
          if ($this->probability(.8)) {
            $gender_id = 2 - $position;
            $gender = $this->gender[$gender_id];
          }
        }
        // Kids
        else {
          $birth_date = $this->randomInt($offset - 10 * $year, $offset);
        }
      }
      // Non household people
      else {
        if ($this->probability(.6)) {
          $this->_addAddress($cid);
        }
      }

      $contact->first_name = $this->randomItem($gender . '_name');
      $contact->middle_name = $this->probability(.5) ? '' : ucfirst($this->randomChar());
      $age = intval(($now - $birth_date) / $year);

      // Prefix and suffix by gender and age
      $contact->prefix_id = $contact->suffix_id = $prefix = $suffix = NULL;
      if ($this->probability(.5) && $age > 20) {
        [$contact->prefix_id, $prefix] = $this->randomKeyValue($this->prefix[$gender_id]);
        $prefix .= ' ';
      }
      if ($gender === 'male' && $this->probability(.50)) {
        [$contact->suffix_id, $suffix] = $this->randomKeyValue($this->suffix);
        $suffix = ' ' . $suffix;
      }
      if ($this->probability(.7)) {
        $contact->gender_id = $gender_id;
      }
      if ($this->probability(.7)) {
        $contact->birth_date = date("Ymd", $birth_date);
      }

      // Deceased probability based on age
      if ($contact->gender_id && $contact->gender_id == 2) {
        $checkAge = 64;
      }
      else {
        $checkAge = 68;
      }
      if ($age > $checkAge && count($this->deceasedContactIds) < 4) {
        $contact->is_deceased = $this->probability(($age - 30) / 100);
        if ($contact->is_deceased && $this->probability(.7)) {
          $contact->deceased_date = $this->randomDate();
        }
      }

      // Add 0, 1 or 2 email address
      $count = $this->randomInt(0, 2);
      for ($i = 0; $i < $count; ++$i) {
        $email = $this->_individualEmail($contact);
        $this->_addEmail($cid, $email, self::HOME);
      }

      // Add 0, 1 or 2 phones
      $count = $this->randomInt(0, 2);
      for ($i = 0; $i < $count; ++$i) {
        $this->_addPhone($cid);
      }

      // Occasionally you get contacts with just an email in the db
      if ($this->probability(.2) && $email) {
        $contact->first_name = $contact->last_name = $contact->middle_name = NULL;
        $contact->is_deceased = $contact->gender_id = $contact->birth_date = $contact->deceased_date = NULL;
        $contact->display_name = $contact->sort_name = $email;
        $contact->postal_greeting_display = $contact->email_greeting_display = "Dear $email";
      }
      else {
        $contact->display_name = $prefix . $contact->first_name . ' ' . $contact->last_name . $suffix;
        $contact->sort_name = $contact->last_name . ', ' . $contact->first_name;
        $contact->postal_greeting_display = $contact->email_greeting_display = 'Dear ' . $contact->first_name;
      }
      $contact->addressee_id = $contact->postal_greeting_id = $contact->email_greeting_id = 1;
      $contact->addressee_display = $contact->display_name;
      $contact->hash = crc32($contact->sort_name);
      $contact->id = $cid;
      $this->_update($contact);
      if ($contact->is_deceased) {
        $this->deceasedContactIds[] = $cid;
      }
    }
  }

  /**
   * This method adds household's data to the contact table
   *
   * The following fields are generated and added.
   *
   * contact_uuid - household_individual
   * contact_rid - latest one
   * household_name 'household $contact_uuid primary contact
   * $primary_contact_uuid' nick_name 'nick $contact_uuid' primary_contact_uuid
   * = $household_individual[$contact_uuid][0];
   *
   */
  private function addHousehold() {

    $contact = new CRM_Contact_DAO_Contact();
    foreach ($this->Household as $cid) {
      // Add address
      $this->_addAddress($cid);

      $contact->id = $cid;
      $contact->household_name = $this->householdName[$cid] . " family";
      // need to update the sort name for the main contact table
      $contact->display_name = $contact->sort_name = $contact->household_name;
      $contact->postal_greeting_id = $contact->email_greeting_id = 5;
      $contact->postal_greeting_display = $contact->email_greeting_display = 'Dear ' . $contact->household_name;
      $contact->addressee_id = 2;
      $contact->addressee_display = $contact->display_name;
      $contact->hash = crc32($contact->sort_name);
      $contact->is_deceased = $this->probability(.1);
      if ($contact->is_deceased && $this->probability(.7)) {
        $contact->deceased_date = $this->randomDate();
      }
      $this->_update($contact);
    }
  }

  /**
   * This method adds organization data to the contact table
   *
   * The following fields are generated and added.
   *
   * contact_uuid - organization
   * contact_rid - latest one
   * organization_name 'organization $contact_uuid'
   * legal_name 'legal  $contact_uuid'
   * nick_name 'nick $contact_uuid'
   * sic_code 'sic $contact_uuid'
   * primary_contact_id - random individual contact uuid
   *
   */
  private function addOrganization() {

    $org = new CRM_Contact_DAO_Contact();
    $employees = $this->Individual;
    $employees = $this->shuffle($employees);

    foreach ($this->Organization as $key => $id) {
      $org->primary_contact_id = $website = $email = NULL;
      $org->id = $id;
      $address = $this->_addAddress($id);

      $namePre = $this->randomItem('organization_prefix');
      $nameMid = $this->randomItem('organization_name');
      $namePost = $this->randomItem('organization_suffix');

      // Some orgs are named after their location
      if ($this->probability(.7)) {
        $place = $this->randomItem(['city', 'street_name', 'state']);
        $namePre = $address[$place];
      }
      $org->organization_name = "$namePre $nameMid $namePost";

      // Most orgs have a website and email
      if ($this->probability(.8)) {
        $website = $this->_addWebsite($id, $org->organization_name);
        $url = str_replace('http://', '', $website['url']);
        $email = $this->randomItem('email_address') . '@' . $url;
        $this->_addEmail($id, $email, self::MAIN);
      }

      // current employee
      if ($this->probability(.8)) {
        $indiv = new CRM_Contact_DAO_Contact();
        $org->primary_contact_id = $indiv->id = $employees[$key];
        $indiv->organization_name = $org->organization_name;
        $indiv->employer_id = $id;
        $this->_update($indiv);
        // Share address with employee
        if ($this->probability(.8)) {
          $this->_addAddress($indiv->id, $id);
        }
        // Add work email for employee
        if ($website) {
          $indiv->find(TRUE);
          $email = $this->_individualEmail($indiv, $url);
          $this->_addEmail($indiv->id, $email, self::WORK);
        }
      }

      // need to update the sort name for the main contact table
      $org->display_name = $org->sort_name = $org->organization_name;
      $org->addressee_id = 3;
      $org->addressee_display = $org->display_name;
      $org->is_deceased = $this->probability(.1);
      if ($org->is_deceased && $this->probability(.7)) {
        $org->deceased_date = $this->randomDate();
      }
      $org->hash = crc32($org->sort_name);
      $this->_update($org);
    }
  }

  /**
   * This method adds data to the contact_relationship table
   */
  private function addRelationship() {

    $relationship = new CRM_Contact_DAO_Relationship();

    // Household relationships
    foreach ($this->householdIndividual as $household_id => $household_member) {
      // Default active
      $relationship->is_active = 1;

      // add child_of relationship for each child
      $relationship->relationship_type_id = $this->relTypes['Child of']['id'];
      foreach ([0, 1] as $parent) {
        foreach ([2, 3] as $child) {
          $relationship->contact_id_a = $household_member[$child];
          $relationship->contact_id_b = $household_member[$parent];
          $this->_insert($relationship);
        }
      }

      // add sibling_of relationship
      $relationship->relationship_type_id = $this->relTypes['Sibling of']['id'];
      $relationship->contact_id_a = $household_member[3];
      $relationship->contact_id_b = $household_member[2];
      $this->_insert($relationship);

      // add member_of_household relationships and shared address
      $relationship->relationship_type_id = $this->relTypes['Household Member of']['id'];
      $relationship->contact_id_b = $household_id;
      for ($i = 1; $i < 4; ++$i) {
        $relationship->contact_id_a = $household_member[$i];
        $this->_insert($relationship);
        $this->_addAddress($household_member[$i], $household_id);
      }

      // Divorced/separated couples - end relationship and different address
      if ($this->probability(.4)) {
        $relationship->is_active = 0;
        $this->_addAddress($household_member[0]);
      }
      else {
        $this->_addAddress($household_member[0], $household_id);
      }

      // add head_of_household relationship 1 for head of house
      $relationship->relationship_type_id = $this->relTypes['Head of Household for']['id'];
      $relationship->contact_id_a = $household_member[0];
      $relationship->contact_id_b = $household_id;
      $this->_insert($relationship);

      // add spouse_of relationship 1 for both the spouses
      $relationship->relationship_type_id = $this->relTypes['Spouse of']['id'];
      $relationship->contact_id_a = $household_member[1];
      $relationship->contact_id_b = $household_member[0];
      $this->_insert($relationship);
    }

    // Add current employer relationships
    $this->_query("INSERT INTO civicrm_relationship
      (contact_id_a, contact_id_b, relationship_type_id, is_active)
      (SELECT id, employer_id, " . $this->relTypes['Employee of']['id'] . ", 1 FROM civicrm_contact WHERE employer_id IN (" . implode(',', $this->Organization) . "))"
    );
  }

  /**
   * Create an address for a contact
   *
   * @param int $cid
   *   contact id
   * @param int $masterContactId
   *   set if this is a shared address
   *
   * @return array
   */
  private function _addAddress($cid, $masterContactId = NULL) {

    // Share existing address
    if ($masterContactId) {
      $dao = new CRM_Core_DAO_Address();
      $dao->is_primary = 1;
      $dao->contact_id = $masterContactId;
      $dao->find(TRUE);
      $dao->master_id = $dao->id;
      $dao->id = NULL;
      $dao->contact_id = $cid;
      $dao->is_primary = $this->isPrimary($cid, 'Address');
      $dao->location_type_id = $this->getContactType($masterContactId) == 'Organization' ? self::WORK : self::HOME;
      $this->_insert($dao);
    }

    // Generate new address
    else {
      $params = [
        'contact_id' => $cid,
        'location_type_id' => $this->getContactType($cid) == 'Organization' ? self::MAIN : self::HOME,
        'street_number' => $this->randomInt(1, 1000),
        'street_number_suffix' => ucfirst($this->randomChar()),
        'street_name' => $this->randomItem('street_name'),
        'street_type' => $this->randomItem('street_type'),
        'street_number_postdirectional' => $this->randomItem('address_direction'),
        'county_id' => 1,
      ];

      $params['street_address'] = $params['street_number'] . $params['street_number_suffix'] . " " . $params['street_name'] . " " . $params['street_type'] . " " . $params['street_number_postdirectional'];

      if ($params['location_type_id'] == self::MAIN) {
        $params['supplemental_address_1'] = $this->randomItem('supplemental_addresses_1');
      }

      // Hack to add lat/long (limited to USA based addresses)
      $params = array_merge($params, $this->getZipCodeInfo());

      $this->_addDAO('Address', $params);
      $params['state'] = $this->states[$params['state_province_id']];
      return $params;
    }
  }

  /**
   * Add a phone number for a contact
   *
   * @param int $cid contact id
   *
   * @return array
   */
  private function _addPhone($cid) {
    $area = $this->probability(.5) ? '' : $this->randomInt(201, 899);
    $pre = $this->randomInt(201, 899);
    $post = $this->randomInt(1000, 9999);
    $params = [
      'location_type_id' => $this->getContactType($cid) == 'Organization' ? self::MAIN : self::HOME,
      'contact_id' => $cid,
      'phone' => ($area ? "($area) " : '') . "$pre-$post",
      'phone_numeric' => $area . $pre . $post,
      'phone_type_id' => $this->randomInt(1, 2),
    ];
    $this->_addDAO('Phone', $params);
    return $params;
  }

  /**
   * Add an email for a contact
   *
   * @param int $cid contact id
   * @param $email
   * @param $locationType
   *
   * @return array
   */
  private function _addEmail($cid, $email, $locationType) {
    $params = [
      'location_type_id' => $locationType,
      'contact_id' => $cid,
      'email' => $email,
    ];
    $this->_addDAO('Email', $params);
    return $params;
  }

  /**
   * Add a website based on organization name
   * Using common naming patterns
   *
   * @param int $cid contact id
   * @param string $name contact name
   *
   * @return array
   */
  private function _addWebsite($cid, $name) {
    $part = array_pad(explode(' ', strtolower($name)), 3, '');
    if (count($part) > 3) {
      // Abbreviate the place name if it's two words
      $domain = $part[0][0] . $part[1][0] . $part[2] . $part[3];
    }
    else {
      // Common naming patterns
      switch ($this->randomInt(1, 3)) {
        case 1:
          $domain = $part[0] . $part[1] . $part[2];
          break;

        case 2:
          $domain = $part[0] . $part[1];
          break;

        case 3:
          $domain = $part[0] . $part[2];
          break;
      }
    }
    $params = [
      'website_type_id' => 1,
      'location_type_id' => self::MAIN,
      'contact_id' => $cid,
      'url' => "http://$domain.org",
    ];
    $this->_addDAO('Website', $params);
    return $params;
  }

  /**
   * Create an email address based on a person's name
   * Using common naming patterns
   *
   * @param CRM_Contact_DAO_Contact $contact individual contact record
   * @param string $domain supply a domain (i.e. for a work address)
   *
   * @return string
   */
  private function _individualEmail($contact, $domain = NULL) {
    $first = $contact->first_name ?? ($this->probability(.5) ? $this->randomItem('male_name') : $this->randomItem('female_name'));
    $last = $contact->last_name ?? $this->randomItem('last_name');
    $f = $first[0];
    $l = $last[0];
    $m = $contact->middle_name ? $contact->middle_name[0] . '.' : '';
    // Common naming patterns
    switch ($this->randomInt(1, 6)) {
      case 1:
        $email = $first . $last;
        break;

      case 2:
        $email = "$last.$first";
        break;

      case 3:
        $email = $last . $f;
        break;

      case 4:
        $email = $first . $l;
        break;

      case 5:
        $email = "$last.$m$first";
        break;

      case 6:
        $email = "$f$m$last";
        break;
    }
    //to ensure we dont insert
    //invalid characters in email
    $email = preg_replace("([^a-zA-Z0-9_\.-]*)", "", $email);

    // Some people have numbers in their address
    if ($this->probability(.4)) {
      $email .= $this->randomInt(1, 99);
    }
    // Generate random domain if not specified
    if (!$domain) {
      $domain = $this->randomItem('email_domain') . '.' . $this->randomItem('email_tld');
    }
    return strtolower($email) . '@' . $domain;
  }

  /**
   * This method populates the civicrm_entity_tag table
   */
  private function addEntityTag() {

    $entity_tag = new CRM_Core_DAO_EntityTag();

    // add categories 1,2,3 for Organizations.
    for ($i = 0; $i < $this->numOrganization; $i += 2) {
      $org_id = $this->Organization[$i];
      // echo "org_id = $org_id\n";
      $entity_tag->entity_id = $this->Organization[$i];
      $entity_tag->entity_table = 'civicrm_contact';
      $entity_tag->tag_id = $this->randomInt(1, 3);
      $this->_insert($entity_tag);
    }

    // add categories 4,5 for Individuals.
    for ($i = 0; $i < $this->numIndividual; $i += 2) {
      $entity_tag->entity_table = 'civicrm_contact';
      $entity_tag->entity_id = $this->Individual[$i];
      if (($entity_tag->entity_id) % 3) {
        $entity_tag->tag_id = $this->randomInt(4, 5);
        $this->_insert($entity_tag);
      }
      else {
        // some of the individuals are in both categories (4 and 5).
        $entity_tag->tag_id = 4;
        $this->_insert($entity_tag);
        $entity_tag->tag_id = 5;
        $this->_insert($entity_tag);
      }
    }
  }

  /**
   * This method populates the civicrm_group_contact table
   *
   * @throws \CRM_Core_Exception
   */
  private function addGroup() {
    // add the 3 groups first
    foreach ($this->sampleData['group'] as $groupName) {
      $group = new CRM_Contact_BAO_Group();
      $group->name = $group->title = $group->frontend_title = $groupName;
      $group->group_type = "12";
      $group->visibility = 'Public Pages';
      $group->is_active = 1;
      $group->save();
    }

    // 60 are for newsletter
    for ($i = 0; $i < 60; $i++) {
      $groupContact = new CRM_Contact_DAO_GroupContact();
      // newsletter subscribers
      $groupContact->group_id = 2;
      $groupContact->contact_id = $this->Individual[$i];
      // always add members
      $groupContact->status = 'Added';

      $subscriptionHistory = new CRM_Contact_DAO_SubscriptionHistory();
      $subscriptionHistory->contact_id = $groupContact->contact_id;

      $subscriptionHistory->group_id = $groupContact->group_id;
      $subscriptionHistory->status = $groupContact->status;
      // method
      $subscriptionHistory->method = $this->randomItem($this->subscriptionHistoryMethod);
      $subscriptionHistory->date = $this->randomDate();
      if ($groupContact->status !== 'Pending') {
        $this->_insert($groupContact);
      }
      $this->_insert($subscriptionHistory);
    }

    // 15 volunteers
    for ($i = 0; $i < 15; $i++) {
      $groupContact = new CRM_Contact_DAO_GroupContact();
      // Volunteers
      $groupContact->group_id = 3;
      $groupContact->contact_id = $this->Individual[$i + 60];
      // membership status
      $groupContact->status = 'Added';

      $subscriptionHistory = new CRM_Contact_DAO_SubscriptionHistory();
      $subscriptionHistory->contact_id = $groupContact->contact_id;
      $subscriptionHistory->group_id = $groupContact->group_id;
      $subscriptionHistory->status = $groupContact->status;
      // method
      $subscriptionHistory->method = $this->randomItem($this->subscriptionHistoryMethod);
      $subscriptionHistory->date = $this->randomDate();

      if ($groupContact->status !== 'Pending') {
        $this->_insert($groupContact);
      }
      $this->_insert($subscriptionHistory);
    }

    // 8 advisory board group + 1 with a login
    for ($i = 0; $i < 9; $i++) {
      $groupContact = new CRM_Contact_DAO_GroupContact();
      // advisory board group
      $groupContact->group_id = 4;
      if ($i !== 8) {
        $groupContact->contact_id = $this->Individual[$i * 7];
      }
      else {
        $advisorID = Contact::create(FALSE)->setValues([
          'first_name' => 'Jenny',
          'last_name' => 'Lee',
          'contact_type' => 'Individual',
          'job_title' => 'Volunteer coordinator',
        ])->addChain('email', Email::create(FALSE)->setValues([
          'contact_id' => '$id',
          'email' => 'jenny@example.com',
        ]))
          ->execute()->first()['id'];
        $groupContact->contact_id = $advisorID;
      }
      // membership status
      $groupContact->status = 'Added';

      $subscriptionHistory = new CRM_Contact_DAO_SubscriptionHistory();
      $subscriptionHistory->contact_id = $groupContact->contact_id;
      $subscriptionHistory->group_id = $groupContact->group_id;
      $subscriptionHistory->status = $groupContact->status;
      // method
      $subscriptionHistory->method = $this->randomItem($this->subscriptionHistoryMethod);
      $subscriptionHistory->date = $this->randomDate();

      if ($groupContact->status !== 'Pending') {
        $this->_insert($groupContact);
      }
      $this->_insert($subscriptionHistory);
    }

    //In this function when we add groups that time we are cache the contact fields
    //But at the end of setup we are appending sample custom data, so for consistency
    //reset the cache.
    Civi::cache('fields')->flush();
    Civi::rebuild(['system' => TRUE])->execute();
  }

  /**
   * This method sets up a basic ACL.
   *
   * It allows the members of the advisory group to edit the Summer volunteers group.
   *
   * @throws \CRM_Core_Exception
   */
  private function addACL(): void {
    $optionValueID = OptionValue::create(FALSE)->setValues([
      'option_group_id:name' => 'acl_role',
      'value' => 3,
      'name' => 'Advisory Board',
      'label' => 'Advisory Board',
    ])
      ->execute()->first()['value'];
    $advisoryGroupID = Group::get(FALSE)
      ->addWhere('name', '=', 'Advisory Board')
      ->execute()->first()['id'];
    $roleID = ACLEntityRole::create(FALSE)->setValues([
      'entity_table' => 'civicrm_group',
      'acl_role_id' => $optionValueID,
      'entity_id' => $advisoryGroupID,
    ])->execute()->first()['id'];
    $volunteerID = Group::get(FALSE)
      ->addWhere('name', '=', 'Summer Program Volunteers')
      ->execute()->first()['id'];

    ACL::create(FALSE)->setValues([
      'name' => 'Advisory board access to volunteers',
      'entity_table' => 'civicrm_acl_role',
      'operation' => 'Edit',
      'object_table' => 'civicrm_group',
      'entity_id' => $roleID,
      'object_id' => $volunteerID,
    ])->execute();
  }

  /**
   * This method populates the civicrm_note table
   */
  private function addNote() {
    $params = [
      'entity_table' => 'civicrm_contact',
      'contact_id' => 1,
      'privacy' => 0,
    ];
    for ($i = 0; $i < self::NUM_CONTACT; $i += 10) {
      $params['entity_id'] = $this->randomItem($this->contact);
      $params['note'] = $this->randomItem('note');
      $params['modified_date'] = $this->randomDate();
      $this->_addDAO('Note', $params);
    }
  }

  /**
   * This method populates the civicrm_activity_history table
   *
   * @noinspection PhpUnusedPrivateMethodInspection
   * @throws \Exception
   */
  private function addActivity(): void {
    $contactDAO = new CRM_Contact_DAO_Contact();
    $contactDAO->contact_type = 'Individual';
    $contactDAO->selectAdd();
    $contactDAO->selectAdd('id');
    $contactDAO->orderBy('sort_name');
    $contactDAO->find();

    $activityTypes = CRM_Core_DAO::executeQuery(
      "
    SELECT  label, name, value as activity_type_id
      FROM  civicrm_option_value
     WHERE  component_id IS NULL AND
       -- this filter mostly gives us user-type actions like 'Phone' & 'Email'
       -- but historically we have also included these two...
       (filter = 0 OR name IN ('Tell A Friend', 'Pledge Acknowledgment'))
       AND option_group_id IN (SELECT id from civicrm_option_group WHERE name = 'activity_type')
     ")->fetchAll();

    $activityTypeOptions = [];
    $nonAssignTypes = ['Pledge Acknowledgment', 'Print PDF Letter'];
    foreach ($activityTypes as $activityType) {
      $activityTypeOptions[$activityType['activity_type_id']] = ['label' => $activityType['label'], 'name' => $activityType['label']];
      $activityTypeOptions[$activityType['activity_type_id']]['is_add_targets'] = !in_array($activityType['name'], $nonAssignTypes, TRUE);
    }
    $count = 0;
    $activityContacts = array_flip(CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate'));

    while ($contactDAO->fetch()) {
      if ($count++ > 2) {
        break;
      }
      for ($i = 0; $i < self::NUM_ACTIVITY; $i++) {
        $activityTypeID = array_rand($activityTypeOptions);
        $activityType = $activityTypeOptions[$activityTypeID];
        $activityDAO = new CRM_Activity_DAO_Activity();
        $activityDAO->activity_type_id = $activityTypeID;
        $activityDAO->subject = "Subject for {$activityType['label']}";
        $activityDAO->duration = random_int(1, 6);
        $activityDAO->activity_date_time = $this->randomDate();
        $activityDAO->status_id = 2;
        $this->_insert($activityDAO);

        $activityContactDAO = new CRM_Activity_DAO_ActivityContact();
        $activityContactDAO->activity_id = $activityDAO->id;
        $activityContactDAO->contact_id = $contactDAO->id;
        $activityContactDAO->record_type_id = $activityContacts['Activity Source'];
        $this->_insert($activityContactDAO);

        if ($activityType['is_add_targets']) {
          $activityContactDAO = new CRM_Activity_DAO_ActivityContact();
          $activityContactDAO->activity_id = $activityDAO->id;
          $activityContactDAO->contact_id = $this->randomInt(1, 101);
          $activityContactDAO->record_type_id = $activityContacts['Activity Targets'];
          $this->_insert($activityContactDAO);
        }
      }
    }
  }

  /**
   * @return array
   */
  public function getZipCodeInfo() {

    if (!$this->stateMap) {
      $query = 'SELECT id, name, abbreviation FROM civicrm_state_province WHERE country_id = 1228';
      $dao = new CRM_Core_DAO();
      $dao->query($query);
      $this->stateMap = [];
      while ($dao->fetch()) {
        $this->stateMap[$dao->abbreviation] = $dao->id;
        $this->states[$dao->id] = $dao->name;
      }
    }

    static $zipCodes = NULL;
    if ($zipCodes === NULL) {
      $zipCodes = json_decode(file_get_contents(self::getCivicrmDir() . '/sql/zipcodes.json'));
    }

    $zipCode = $zipCodes[$this->randomInt(0, count($zipCodes))];

    if ($this->stateMap[$zipCode->state]) {
      $stateID = $this->stateMap[$zipCode->state];
    }
    else {
      $stateID = 1004;
    }

    $zip = str_pad($zipCode->zip, 5, '0', STR_PAD_LEFT);
    return [
      'country_id' => 1228,
      'state_province_id' => $stateID,
      'city' => $zipCode->city,
      'postal_code' => $zip,
      'geo_code_1' => $zipCode->latitude,
      'geo_code_2' => $zipCode->longitude,
    ];
  }

  /**
   * @param $zipCode
   *
   * @return array
   */
  public static function getLatLong($zipCode) {
    $query = "http://maps.google.com/maps?q=$zipCode&output=js";
    $userAgent = "Mozilla/5.0 (Macintosh; U; PPC Mac OS X Mach-O; en-US; rv:1.7.5) Gecko/20041107 Firefox/1.0";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $query);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    // grab URL and pass it to the browser
    $outstr = curl_exec($ch);

    $preg = "/'(<\?xml.+?)',/s";
    preg_match($preg, $outstr, $matches);
    if ($matches[1]) {
      $xml = simplexml_load_string($matches[1]);
      $attributes = $xml->center->attributes();
      if (!empty($attributes)) {
        return [(float ) $attributes['lat'], (float ) $attributes['lng']];
      }
    }
    return [NULL, NULL];
  }

  private function addMembershipType() {
    $organizationDAO = new CRM_Contact_DAO_Contact();
    $organizationDAO->id = 5;
    $organizationDAO->find(TRUE);
    $contact_id = $organizationDAO->contact_id;

    $membershipType = "INSERT INTO civicrm_membership_type
        (name, description, member_of_contact_id, financial_type_id, minimum_fee, duration_unit, duration_interval, period_type, fixed_period_start_day, fixed_period_rollover_day, relationship_type_id, relationship_direction, visibility, weight, is_active)
        VALUES
        ('General', 'Regular annual membership.', " . $contact_id . ", 2, 100, 'year', 1, 'rolling',null, null, 7, 'b_a', 'Public', 1, 1),
        ('Student', 'Discount membership for full-time students.', " . $contact_id . ", 2, 50, 'year', 1, 'rolling', null, null, 7, 'b_a', 'Public', 2, 1),
        ('Lifetime', 'Lifetime membership.', " . $contact_id . ", 2, 1200, 'lifetime', 1, 'rolling', null, null, 7, 'b_a', 'Admin', 3, 1);
        ";
    $this->_query($membershipType);
  }

  private function addMembership() {
    $contact = new CRM_Contact_DAO_Contact();
    $contact->query("SELECT id FROM civicrm_contact WHERE contact_type = 'Individual'");
    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    while ($contact->fetch()) {
      $contacts[] = $contact->id;
    }
    $contacts = $this->shuffle($contacts);

    $randomContacts = array_slice($contacts, 20, 30);

    $sources = ['Payment', 'Donation', 'Check'];
    $membershipTypes = [1, 2];
    $membershipTypeNames = ['General', 'Student'];
    $statuses = [3, 4];

    $membership = "
INSERT INTO civicrm_membership
        (contact_id, membership_type_id, join_date, start_date, end_date, source, status_id)
VALUES
";

    $activity = "
INSERT INTO civicrm_activity
        (source_record_id, activity_type_id, subject, activity_date_time, duration, location, phone_id, phone_number, details, priority_id,parent_id, is_test, status_id)
VALUES
";

    $activityContact = "
INSERT INTO civicrm_activity_contact
  (activity_id, contact_id, record_type_id)
VALUES
";

    $currentActivityID = CRM_Core_DAO::singleValueQuery("SELECT MAX(id) FROM civicrm_activity");
    $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
    foreach ($randomContacts as $count => $dontCare) {
      $source = $this->randomItem($sources);
      $activitySourceId = $count + 1;
      $currentActivityID++;
      $activityContact .= "( $currentActivityID, {$randomContacts[$count]}, {$sourceID} )";
      if ((($count + 1) % 11 == 0)) {
        // lifetime membership, status can be anything
        $startDate = date('Y-m-d', mktime(0, 0, 0, date('m'), (date('d') - $count), date('Y')));
        $membership .= "( {$randomContacts[$count]}, 3, '{$startDate}', '{$startDate}', null, '{$source}', 1)";
        $activity .= "( {$activitySourceId}, 7, 'Lifetime', '{$startDate} 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 )";
      }
      elseif (($count + 1) % 5 == 0) {
        // Grace or expired, memberhsip type is random of 1 & 2
        $randIndex = $this->randomIndex($membershipTypes);
        $membershipTypeId = $membershipTypes[$randIndex];
        $membershipStatusId = $statuses[$randIndex];
        $membershipTypeName = $membershipTypeNames[$randIndex];
        $YearFactor = $membershipTypeId * 2;
        //reverse the type and consider as year factor.
        if ($YearFactor != 2) {
          $YearFactor = 1;
        }
        $dateFactor = ($count * ($YearFactor) * ($YearFactor) * ($YearFactor));
        $startDate = date('Y-m-d', mktime(0, 0, 0,
          date('m'),
          (date('d') - ($dateFactor)),
          (date('Y') - ($YearFactor))
        ));
        $partOfDate = explode('-', $startDate);
        $endDate = date('Y-m-d', mktime(0, 0, 0,
          $partOfDate[1],
          ($partOfDate[2] - 1),
          ($partOfDate[0] + ($YearFactor))
        ));

        $membership .= "( {$randomContacts[$count]}, {$membershipTypeId}, '{$startDate}', '{$startDate}', '{$endDate}', '{$source}', {$membershipStatusId})";
        $activity .= "( {$activitySourceId}, 7, '{$membershipTypeName}', '{$startDate} 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 )";
      }
      elseif (($count + 1) % 2 == 0) {
        // membership type 2
        $startDate = date('Y-m-d', mktime(0, 0, 0, date('m'), (date('d') - $count), date('Y')));
        $endDate = date('Y-m-d', mktime(0, 0, 0, date('m'), (date('d') - ($count + 1)), (date('Y') + 1)));
        $membership .= "( {$randomContacts[$count]}, 2, '{$startDate}', '{$startDate}', '{$endDate}', '{$source}', 1)";
        $activity .= "( {$activitySourceId}, 7, 'Student', '{$startDate} 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 )";
      }
      else {
        // membership type 1
        $startDate = date('Y-m-d', mktime(0, 0, 0, date('m'), (date('d') - $count), date('Y')));
        $endDate = date('Y-m-d', mktime(0, 0, 0, date('m'), (date('d') - ($count + 1)), (date('Y') + 2)));
        $membership .= "( {$randomContacts[$count]}, 1, '{$startDate}', '{$startDate}', '{$endDate}', '{$source}', 1)";
        $activity .= "( {$activitySourceId}, 7, 'General', '{$startDate} 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 )";
      }

      if ($count != 29) {
        $membership .= ",";
        $activity .= ",";
        $activityContact .= ",";
      }
    }

    $this->_query($membership);
    $this->_query($activity);
    $this->_query($activityContact);
  }

  /**
   * @param $date
   *
   * @return string
   */
  public static function repairDate($date) {
    if ($date === NULL) {
      return '';
    }
    $dropArray = ['-' => '', ':' => '', ' ' => ''];
    return strtr($date, $dropArray);
  }

  private function addMembershipLog() {
    $membership = new CRM_Member_DAO_Membership();
    $membership->query("SELECT id FROM civicrm_membership");
    while ($membership->fetch()) {
      $ids[] = $membership->id;
    }
    foreach ($ids as $id) {
      $membership = new CRM_Member_DAO_Membership();
      $membership->id = $id;
      $membershipLog = new CRM_Member_DAO_MembershipLog();
      if ($membership->find(TRUE)) {
        $membershipLog->membership_id = $membership->id;
        $membershipLog->status_id = $membership->status_id;
        $membershipLog->start_date = self::repairDate($membership->start_date);
        $membershipLog->end_date = self::repairDate($membership->end_date);
        $membershipLog->modified_id = $membership->contact_id;
        $membershipLog->modified_date = date("Ymd");
        $membershipLog->membership_type_id = $membership->membership_type_id;
        $membershipLog->save();
      }
      $membershipLog = NULL;
    }
  }

  private function addEvent() {
    $event = "INSERT INTO civicrm_address ( contact_id, location_type_id, is_primary, is_billing, street_address, street_number, street_number_suffix, street_number_predirectional, street_name, street_type, street_number_postdirectional, street_unit, supplemental_address_1, supplemental_address_2, supplemental_address_3, city, county_id, state_province_id, postal_code_suffix, postal_code, usps_adc, country_id, geo_code_1, geo_code_2, timezone)
      VALUES
      ( NULL, 1, 1, 1, '14S El Camino Way E', 14, 'S', NULL, 'El Camino', 'Way', NULL, NULL, NULL, NULL, NULL, 'Collinsville', NULL, 1006, NULL, '6022', NULL, 1228, 41.8328, -72.9253, NULL),
      ( NULL, 1, 1, 1, '11B Woodbridge Path SW', 11, 'B', NULL, 'Woodbridge', 'Path', NULL, NULL, NULL, NULL, NULL, 'Dayton', NULL, 1034, NULL, '45417', NULL, 1228, 39.7531, -84.2471, NULL),
      ( NULL, 1, 1, 1, '581O Lincoln Dr SW', 581, 'O', NULL, 'Lincoln', 'Dr', NULL, NULL, NULL, NULL, NULL, 'Santa Fe', NULL, 1030, NULL, '87594', NULL, 1228, 35.5212, -105.982, NULL)
      ";
    $this->_query($event);

    $sql = "SELECT id FROM civicrm_address WHERE street_address = '14S El Camino Way E'";
    $eventAdd1 = CRM_Core_DAO::singleValueQuery($sql);
    $sql = "SELECT id FROM civicrm_address WHERE street_address = '11B Woodbridge Path SW'";
    $eventAdd2 = CRM_Core_DAO::singleValueQuery($sql);
    $sql = "SELECT id FROM civicrm_address WHERE street_address = '581O Lincoln Dr SW'";
    $eventAdd3 = CRM_Core_DAO::singleValueQuery($sql);

    $event = "INSERT INTO civicrm_email (contact_id, location_type_id, email, is_primary, is_billing, on_hold, hold_date, reset_date)
       VALUES
       (NULL, 1, 'development@example.org', 0, 0, 0, NULL, NULL),
       (NULL, 1, 'tournaments@example.org', 0, 0, 0, NULL, NULL),
       (NULL, 1, 'celebration@example.org', 0, 0, 0, NULL, NULL)
       ";
    $this->_query($event);

    $sql = "SELECT id FROM civicrm_email WHERE email = 'development@example.org'";
    $eventEmail1 = CRM_Core_DAO::singleValueQuery($sql);
    $sql = "SELECT id FROM civicrm_email WHERE email = 'tournaments@example.org'";
    $eventEmail2 = CRM_Core_DAO::singleValueQuery($sql);
    $sql = "SELECT id FROM civicrm_email WHERE email = 'celebration@example.org'";
    $eventEmail3 = CRM_Core_DAO::singleValueQuery($sql);

    $event = "INSERT INTO civicrm_phone (contact_id, location_type_id, is_primary, is_billing, mobile_provider_id, phone, phone_numeric, phone_type_id)
       VALUES
       (NULL, 1, 0, 0, NULL, '204 222-1000', '2042221000', '1'),
       (NULL, 1, 0, 0, NULL, '204 223-1000', '2042231000', '1'),
       (NULL, 1, 0, 0, NULL, '303 323-1000', '3033231000', '1')
       ";
    $this->_query($event);

    $sql = "SELECT id FROM civicrm_phone WHERE phone = '204 222-1000'";
    $eventPhone1 = CRM_Core_DAO::singleValueQuery($sql);
    $sql = "SELECT id FROM civicrm_phone WHERE phone = '204 223-1000'";
    $eventPhone2 = CRM_Core_DAO::singleValueQuery($sql);
    $sql = "SELECT id FROM civicrm_phone WHERE phone = '303 323-1000'";
    $eventPhone3 = CRM_Core_DAO::singleValueQuery($sql);

    $event = "INSERT INTO civicrm_loc_block ( address_id, email_id, phone_id, address_2_id, email_2_id, phone_2_id)
       VALUES
      ( $eventAdd1, $eventEmail1, $eventPhone1, NULL,NULL,NULL),
      ( $eventAdd2, $eventEmail2, $eventPhone2, NULL,NULL,NULL),
      ( $eventAdd3, $eventEmail3, $eventPhone3, NULL,NULL,NULL)
       ";

    $this->_query($event);

    $sql = "SELECT id from civicrm_loc_block where phone_id = $eventPhone1 AND email_id = $eventEmail1 AND address_id = $eventAdd1";
    $eventLok1 = CRM_Core_DAO::singleValueQuery($sql);
    $sql = "SELECT id from civicrm_loc_block where phone_id = $eventPhone2 AND email_id = $eventEmail2 AND address_id = $eventAdd2";
    $eventLok2 = CRM_Core_DAO::singleValueQuery($sql);
    $sql = "SELECT id from civicrm_loc_block where phone_id = $eventPhone3 AND email_id = $eventEmail3 AND address_id = $eventAdd3";
    $eventLok3 = CRM_Core_DAO::singleValueQuery($sql);

    $event = "INSERT INTO civicrm_event
        ( title, summary, description, event_type_id, participant_listing_id, is_public, start_date, end_date, is_online_registration, registration_link_text, max_participants, event_full_text, is_monetary, financial_type_id, is_map, is_active, fee_label, is_show_location, loc_block_id,intro_text, footer_text, confirm_title, confirm_text, confirm_footer_text, is_email_confirm, confirm_email_text, confirm_from_name, confirm_from_email, cc_confirm, bcc_confirm, default_fee_id, thankyou_title, thankyou_text, thankyou_footer_text, is_pay_later, pay_later_text, pay_later_receipt, is_multiple_registrations, allow_same_participant_emails, currency )
        VALUES
        ( 'Fall Fundraiser Dinner', 'Kick up your heels at our Fall Fundraiser Dinner/Dance at Glen Echo Park! Come by yourself or bring a partner, friend or the entire family!', 'This event benefits our teen programs. Admission includes a full 3 course meal and wine or soft drinks. Grab your dancing shoes, bring the kids and come join the party!', 3, 1, 1, '" . date('Y-m-d 17:00:00', strtotime("+6 months", $this->time)) . "', '" . date('Y-m-d 17:00:00', strtotime("+6 months +2 days", $this->time)) . "', 1, 'Register Now', 100, 'Sorry! The Fall Fundraiser Dinner is full. Please call Jane at 204 222-1000 ext 33 if you want to be added to the waiting list.', 1, 4, 1, 1, 'Dinner Contribution', 1 ,$eventLok1,'Fill in the information below to join as at this wonderful dinner event.', NULL, 'Confirm Your Registration Information', 'Review the information below carefully.', NULL, 1, 'Contact the Development Department if you need to make any changes to your registration.', 'Fundraising Dept.', 'development@example.org', NULL, NULL, NULL, 'Thanks for Registering!', '<p>Thank you for your support. Your contribution will help us build even better tools.</p><p>Please tell your friends and colleagues about this wonderful event.</p>', '<p><a href=https://civicrm.org>Back to CiviCRM Home Page</a></p>', 1, 'I will send payment by check', 'Send a check payable to Our Organization within 3 business days to hold your reservation. Checks should be sent to: 100 Main St., Suite 3, San Francisco CA 94110', 1, 0, 'USD'),
        ( 'Summer Solstice Festival Day Concert', 'Festival Day is coming! Join us and help support your parks.', 'We will gather at noon, learn a song all together,  and then join in a joyous procession to the pavilion. We will be one of many groups performing at this wonderful concert which benefits our city parks.', 5, 1, 1, '" . date('Y-m-d 12:00:00', strtotime("-1 day", $this->time)) . "', '" . date('Y-m-d 17:00:00', strtotime("-1 day", $this->time)) . "', 1, 'Register Now', 50, 'We have all the singers we can handle. Come to the pavilion anyway and join in from the audience.', 1, 2, 0, 1, 'Festival Fee', 1, $eventLok2, 'Complete the form below and click Continue to register online for the festival. Or you can register by calling us at 204 222-1000 ext 22.', '', 'Confirm Your Registration Information', '', '', 1, 'This email confirms your registration. If you have questions or need to change your registration - please do not hesitate to call us.', 'Event Dept.', 'events@example.org', '', NULL, NULL, 'Thanks for Your Joining In!', '<p>Thank you for your support. Your participation will help build new parks.</p><p>Please tell your friends and colleagues about the concert.</p>', '<p><a href=https://civicrm.org>Back to CiviCRM Home Page</a></p>', 0, NULL, NULL, 1, 0, 'USD'),
        ( 'Rain-forest Cup Youth Soccer Tournament', 'Sign up your team to participate in this fun tournament which benefits several Rain-forest protection groups in the Amazon basin.', 'This is a FYSA Sanctioned Tournament, which is open to all USSF/FIFA affiliated organizations for boys and girls in age groups: U9-U10 (6v6), U11-U12 (8v8), and U13-U17 (Full Sided).', 3, 1, 1, '" . date('Y-m-d 07:00:00', strtotime("+7 months", $this->time)) . "', '" . date('Y-m-d 17:00:00', strtotime("+7 months +3 days", $this->time)) . "', 1, 'Register Now', 500, 'Sorry! All available team slots for this tournament have been filled. Contact Jill Futbol for information about the waiting list and next years event.', 1, 4, 0, 1, 'Tournament Fees',1, $eventLok3, 'Complete the form below to register your team for this year''s tournament.', '<em>A Soccer Youth Event</em>', 'Review and Confirm Your Registration Information', '', '<em>A Soccer Youth Event</em>', 1, 'Contact our Tournament Director for eligibility details.', 'Tournament Director', 'tournament@example.org', '', NULL, NULL, 'Thanks for Your Support!', '<p>Thank you for your support. Your participation will help save thousands of acres of rainforest.</p>', '<p><a href=https://civicrm.org>Back to CiviCRM Home Page</a></p>', 0, NULL, NULL, 0, 0, 'USD')
         ";
    $this->_query($event);

    //CRM-4464
    $eventTemplates = "INSERT INTO civicrm_event
        ( is_template, template_title, event_type_id, default_role_id, participant_listing_id, is_public, is_monetary, is_online_registration, is_multiple_registrations, allow_same_participant_emails, is_email_confirm, financial_type_id, fee_label, confirm_title, thankyou_title, confirm_from_name, confirm_from_email, is_active, currency )
        VALUES
        ( 1, 'Free Meeting without Online Registration', 4, 1, 1, 1, 0, 0, 1, 0, 0, NULL,             NULL, NULL, NULL, NULL, NULL, 1, 'USD'  ),
        ( 1, 'Free Meeting with Online Registration',    4, 1, 1, 1, 0, 1,    1,    1,    0, NULL,             NULL, 'Confirm Your Registration Information', 'Thanks for Registering!', NULL, NULL, 1, 'USD'  ),
        ( 1, 'Paid Conference with Online Registration', 1, 1, 1, 1, 1, 1,    1,    1,    1,     4, 'Conference Fee', 'Confirm Your Registration Information', 'Thanks for Registering!', 'Event Template Dept.', 'event_templates@example.org', 1, 'USD' )";

    $this->_query($eventTemplates);

    $ufJoinValues = [];
    $profileID = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_uf_group WHERE name ='event_registration'");

    // grab id's for all events and event templates
    $query = "
SELECT  id
  FROM  civicrm_event";

    $template = CRM_Core_DAO::executeQuery($query);
    while ($template->fetch()) {
      if ($profileID) {
        $ufJoinValues[] = "( 1, 'CiviEvent', 'civicrm_event', {$template->id}, 1, {$profileID} )";
      }
    }

    //insert values in civicrm_uf_join for the required event_registration profile - CRM-9587
    if (!empty($ufJoinValues)) {
      $includeProfile = "INSERT INTO civicrm_uf_join
                               (is_active, module, entity_table, entity_id, weight, uf_group_id )
                               VALUES " . implode(',', $ufJoinValues);
      $this->_query($includeProfile);
    }
  }

  private function addParticipant() {
    $contact = new CRM_Contact_DAO_Contact();
    $contact->query("SELECT id FROM civicrm_contact");
    while ($contact->fetch()) {
      $contacts[] = $contact->id;
    }
    $contacts = $this->shuffle($contacts);
    $randomContacts = array_slice($contacts, 20, 50);

    $participant = "
INSERT INTO civicrm_participant
        (contact_id, event_id, status_id, role_id, register_date, source, fee_level, is_test, fee_amount, fee_currency)
VALUES
        ( " . $randomContacts[0] . ", 1, 1, 1, '2009-01-21', 'Check', 'Single', 0, 50, 'USD'),
        ( " . $randomContacts[1] . ", 2, 2, 2, '2008-05-07', 'Credit Card', 'Soprano', 0, 50, 'USD'),
        ( " . $randomContacts[2] . ", 3, 3, 3, '2008-05-05', 'Credit Card', 'Tiny-tots (ages 5-8)', 0, 800, 'USD') ,
        ( " . $randomContacts[3] . ", 1, 4, 4, '2008-10-21', 'Direct Transfer', 'Single', 0, 50, 'USD'),
        ( " . $randomContacts[4] . ", 2, 1, 1, '2008-01-10', 'Check', 'Soprano', 0, 50, 'USD'),
        ( " . $randomContacts[5] . ", 3, 2, 2, '2008-03-05', 'Direct Transfer', 'Tiny-tots (ages 5-8)', 0, 800, 'USD'),
        ( " . $randomContacts[6] . ", 1, 3, 3, '2009-07-21', 'Direct Transfer', 'Single', 0, 50, 'USD'),
        ( " . $randomContacts[7] . ", 2, 4, 4, '2009-03-07', 'Credit Card', 'Soprano', 0, 50, 'USD'),
        ( " . $randomContacts[8] . ", 3, 1, 1, '2008-02-05', 'Direct Transfer', 'Tiny-tots (ages 5-8)', 0, 800, 'USD'),
        ( " . $randomContacts[9] . ", 1, 2, 2, '2008-02-01', 'Check', 'Single', 0, 50, 'USD'),
        ( " . $randomContacts[10] . ", 2, 3, 3, '2009-01-10', 'Direct Transfer', 'Soprano', 0, 50, 'USD'),
        ( " . $randomContacts[11] . ", 3, 4, 4, '2009-03-06', 'Credit Card', 'Tiny-tots (ages 5-8)', 0, 800, 'USD'),
        ( " . $randomContacts[12] . ", 1, 1, 2, '2008-06-04', 'Credit Card', 'Single', 0, 50, 'USD'),
        ( " . $randomContacts[13] . ", 2, 2, 3, '2008-01-10', 'Direct Transfer', 'Soprano', 0, 50, 'USD'),
        ( " . $randomContacts[14] . ", 3, 4, 1, '2008-07-04', 'Check', 'Tiny-tots (ages 5-8)', 0, 800, 'USD'),
        ( " . $randomContacts[15] . ", 1, 4, 2, '2009-01-21', 'Credit Card', 'Single', 0, 50, 'USD'),
        ( " . $randomContacts[16] . ", 2, 2, 3, '2008-01-10', 'Credit Card', 'Soprano', 0, 50, 'USD'),
        ( " . $randomContacts[17] . ", 3, 3, 1, '2009-03-05', 'Credit Card', 'Tiny-tots (ages 5-8)', 0, 800, 'USD'),
        ( " . $randomContacts[18] . ", 1, 2, 1, '2008-10-21', 'Direct Transfer', 'Single', 0, 50, 'USD'),
        ( " . $randomContacts[19] . ", 2, 4, 1, '2009-01-10', 'Credit Card', 'Soprano', 0, 50, 'USD'),
        ( " . $randomContacts[20] . ", 3, 1, 4, '2008-03-25', 'Check', 'Tiny-tots (ages 5-8)', 0, 800, 'USD'),
        ( " . $randomContacts[21] . ", 1, 2, 3, '2009-10-21', 'Direct Transfer', 'Single', 0, 50, 'USD'),
        ( " . $randomContacts[22] . ", 2, 4, 1, '2008-01-10', 'Direct Transfer', 'Soprano', 0, 50, 'USD'),
        ( " . $randomContacts[23] . ", 3, 3, 1, '2008-03-11', 'Credit Card', 'Tiny-tots (ages 5-8)', 0, 800, 'USD'),
        ( " . $randomContacts[24] . ", 3, 2, 2, '2008-04-05', 'Direct Transfer', 'Tiny-tots (ages 5-8)', 0, 800, 'USD'),
        ( " . $randomContacts[25] . ", 1, 1, 1, '2009-01-21', 'Check', 'Single', 0, 50, 'USD'),
        ( " . $randomContacts[26] . ", 2, 2, 2, '2008-05-07', 'Credit Card', 'Soprano', 0, 50, 'USD'),
        ( " . $randomContacts[27] . ", 3, 3, 3, '2009-12-12', 'Direct Transfer', 'Tiny-tots (ages 5-8)', 0, 800, 'USD'),
        ( " . $randomContacts[28] . ", 1, 4, 4, '2009-12-13', 'Credit Card', 'Single', 0, 50, 'USD'),
        ( " . $randomContacts[29] . ", 2, 1, 1, '2009-12-14', 'Direct Transfer', 'Soprano', 0, 50, 'USD'),
        ( " . $randomContacts[30] . ", 3, 2, 2, '2009-12-15', 'Credit Card', 'Tiny-tots (ages 5-8)', 0, 800, 'USD'),
        ( " . $randomContacts[31] . ", 1, 3, 3, '2009-07-21', 'Check', 'Single', 0, 50, 'USD'),
        ( " . $randomContacts[32] . ", 2, 4, 4, '2009-03-07', 'Direct Transfer', 'Soprano', 0, 50, 'USD'),
        ( " . $randomContacts[33] . ", 3, 1, 1, '2009-12-15', 'Credit Card', 'Tiny-tots (ages 5-8)', 0, 800, 'USD'),
        ( " . $randomContacts[34] . ", 1, 2, 2, '2009-12-13', 'Direct Transfer', 'Single', 0, 50, 'USD'),
        ( " . $randomContacts[35] . ", 2, 3, 3, '2009-01-10', 'Direct Transfer', 'Soprano', 0, 50, 'USD'),
        ( " . $randomContacts[36] . ", 3, 4, 4, '2009-03-06', 'Check', 'Tiny-tots (ages 5-8)', 0, 800, 'USD'),
        ( " . $randomContacts[37] . ", 1, 1, 2, '2009-12-13', 'Direct Transfer', 'Single', 0, 50, 'USD'),
        ( " . $randomContacts[38] . ", 2, 2, 3, '2008-01-10', 'Direct Transfer', 'Soprano', 0, 50, 'USD'),
        ( " . $randomContacts[39] . ", 3, 4, 1, '2009-12-14', 'Credit Card', 'Tiny-tots (ages 5-8)', 0, 800, 'USD'),
        ( " . $randomContacts[40] . ", 1, 4, 2, '2009-01-21', 'Credit Card', 'Single', 0, 50, 'USD'),
        ( " . $randomContacts[41] . ", 2, 2, 3, '2009-12-15', 'Credit Card', 'Soprano', 0, 50, 'USD'),
        ( " . $randomContacts[42] . ", 3, 3, 1, '2009-03-05', 'Credit Card', 'Tiny-tots (ages 5-8)', 0, 800, 'USD'),
        ( " . $randomContacts[43] . ", 1, 2, 1, '2009-12-13', 'Direct Transfer', 'Single', 0, 50, 'USD'),
        ( " . $randomContacts[44] . ", 2, 4, 1, '2009-01-10', 'Direct Transfer', 'Soprano', 0, 50, 'USD'),
        ( " . $randomContacts[45] . ", 3, 1, 4, '2009-12-13', 'Check', 'Tiny-tots (ages 5-8)', 0, 800, 'USD'),
        ( " . $randomContacts[46] . ", 1, 2, 3, '2009-10-21', 'Credit Card', 'Single', 0, 50, 'USD'),
        ( " . $randomContacts[47] . ", 2, 4, 1, '2009-12-10', 'Credit Card', 'Soprano', 0, 50, 'USD'),
        ( " . $randomContacts[48] . ", 3, 3, 1, '2009-03-11', 'Credit Card', 'Tiny-tots (ages 5-8)', 0, 800, 'USD'),
        ( " . $randomContacts[49] . ", 3, 2, 2, '2009-04-05', 'Check', 'Tiny-tots (ages 5-8)', 0, 800, 'USD');
";
    $this->_query($participant);

    $query = "
INSERT INTO civicrm_activity
    (source_record_id, activity_type_id, subject, activity_date_time, duration, location, phone_id, phone_number, details, priority_id,parent_id, is_test, status_id)
VALUES
    (01, 5, 'NULL', '2009-01-21 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (02, 5, 'NULL', '2008-05-07 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (03, 5, 'NULL', '2008-05-05 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (04, 5, 'NULL', '2008-10-21 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (05, 5, 'NULL', '2008-01-10 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (06, 5, 'NULL', '2008-03-05 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (07, 5, 'NULL', '2009-07-21 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (08, 5, 'NULL', '2009-03-07 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (09, 5, 'NULL', '2008-02-05 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (10, 5, 'NULL', '2008-02-01 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (11, 5, 'NULL', '2009-01-10 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (12, 5, 'NULL', '2009-03-06 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (13, 5, 'NULL', '2008-06-04 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (14, 5, 'NULL', '2008-01-10 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (15, 5, 'NULL', '2008-07-04 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (16, 5, 'NULL', '2009-01-21 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (17, 5, 'NULL', '2008-01-10 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (18, 5, 'NULL', '2009-03-05 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (19, 5, 'NULL', '2008-10-21 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (20, 5, 'NULL', '2009-01-10 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (21, 5, 'NULL', '2008-03-25 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (22, 5, 'NULL', '2009-10-21 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (23, 5, 'NULL', '2008-01-10 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (24, 5, 'NULL', '2008-03-11 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (25, 5, 'NULL', '2008-04-05 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (26, 5, 'NULL', '2009-01-21 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (27, 5, 'NULL', '2008-05-07 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (28, 5, 'NULL', '2009-12-12 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (29, 5, 'NULL', '2009-12-13 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (30, 5, 'NULL', '2009-12-14 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (31, 5, 'NULL', '2009-12-15 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (32, 5, 'NULL', '2009-07-21 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (33, 5, 'NULL', '2009-03-07 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (34, 5, 'NULL', '2009-12-15 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (35, 5, 'NULL', '2009-12-13 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (36, 5, 'NULL', '2009-01-10 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (37, 5, 'NULL', '2009-03-06 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (38, 5, 'NULL', '2009-12-13 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (39, 5, 'NULL', '2008-01-10 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (40, 5, 'NULL', '2009-12-14 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (41, 5, 'NULL', '2009-01-21 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (42, 5, 'NULL', '2009-12-15 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (43, 5, 'NULL', '2009-03-05 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (44, 5, 'NULL', '2009-12-13 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (45, 5, 'NULL', '2009-01-10 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (46, 5, 'NULL', '2009-12-13 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (47, 5, 'NULL', '2009-10-21 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (48, 5, 'NULL', '2009-12-10 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (49, 5, 'NULL', '2009-03-11 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 ),
    (50, 5, 'NULL', '2009-04-05 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 )
    ";
    $this->_query($query);

    $activityContact = "
INSERT INTO civicrm_activity_contact
  (contact_id, activity_id, record_type_id)
VALUES
";
    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $currentActivityID = CRM_Core_DAO::singleValueQuery("SELECT MAX(id) FROM civicrm_activity");
    $currentActivityID -= 50;
    $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
    for ($i = 0; $i < 50; $i++) {
      $currentActivityID++;
      $activityContact .= "({$randomContacts[$i]}, $currentActivityID, $sourceID)";
      if ($i != 49) {
        $activityContact .= ", ";
      }
    }
    $this->_query($activityContact);
  }

  private function addPCP() {
    $query = "
INSERT INTO `civicrm_pcp`
    (contact_id, status_id, title, intro_text, page_text, donate_link_text, page_id, page_type, is_thermometer, is_honor_roll, goal_amount, currency, is_active, pcp_block_id, is_notify)
VALUES
    ({$this->Individual[3]}, 2, 'My Personal Civi Fundraiser', 'I''m on a mission to get all my friends and family to help support my favorite open-source civic sector CRM.', '<p>Friends and family - please help build much needed infrastructure for the civic sector by supporting my personal campaign!</p>\r\n<p><a href=\"https://civicrm.org\">You can learn more about CiviCRM here</a>.</p>\r\n<p>Then click the <strong>Contribute Now</strong> button to go to our easy-to-use online contribution form.</p>', 'Contribute Now', 1, 'contribute', 1, 1, 5000.00, 'USD', 1, 1, 1);
";
    $this->_query($query);
  }

  private function addContribution() {
    $defaults = [
      'financial_type_id' => 1,
      'payment_instrument_id' => 4,
      'receive_date' => 'now',
      'non_deductible_amount' => 0,
      'total_amount' => 25,
      'trxn_id' => '',
      'check_number' => '',
      'currency' => 'USD',
      'cancel_date' => NULL,
      'cancel_reason' => NULL,
      'receipt_date' => NULL,
      'thankyou_date' => NULL,
      'source' => 'April Mailer 1',
      'contribution_recur_id' => NULL,
    ];
    $contributions = [
      1 => [
        'contact_id' => 2,
        'receive_date' => '10 years ago',
        'total_amount' => 125,
        'check_number' => '1041',
      ],
      2 => [
        'contact_id' => 4,
        'payment_instrument_id' => 1,
        'receive_date' => '2 years 3 months ago',
        'total_amount' => 50,
        'trxn_id' => 'P20901X1',
        'source' => 'Online: Save the Penguins',
      ],
      3 => [
        'contact_id' => 6,
        'receive_date' => '6 years 25 days 780 minutes ago',
        'total_amount' => 25,
        'trxn_id' => 'GBP12',
        'check_number' => '2095',
        'currency' => 'GBP',
      ],
      4 => [
        'contact_id' => 8,
        'receive_date' => '2 years 3 months ago',
        'total_amount' => 50,
        'check_number' => '10552',
        'source' => 'Online: Save the Penguins',
      ],
      5 => [
        'contact_id' => 4,
        'payment_instrument_id' => 1,
        'receive_date' => '2 years 3 months ago',
        'total_amount' => 50,
        'trxn_id' => 'Q90901X1',
        'source' => 'Online: Save the Penguins',
      ],
      6 => [
        'contact_id' => 16,
        'receive_date' => '85 days 42 minutes ago',
        'total_amount' => 500,
        'check_number' => '509',
      ],
      7 => [
        'contact_id' => 19,
        'payment_instrument_id' => 1,
        'receive_date' => '2 days ago',
        'total_amount' => 1750,
        'check_number' => '102',
        'source' => 'Online: Save the Penguins',
      ],
      8 => [
        'contact_id' => 82,
        'payment_instrument_id' => 1,
        'receive_date' => '340789 minutes ago',
        'total_amount' => 50,
        'trxn_id' => 'P20193L2',
        'source' => 'Online: Save the Penguins',
      ],
      9 => [
        'contact_id' => 92,
        'payment_instrument_id' => 1,
        'receive_date' => '11 months ago',
        'total_amount' => 10,
        'trxn_id' => 'P40232Y3',
        'source' => 'Online: Help CiviCRM',
      ],
      10 => [
        'contact_id' => 34,
        'payment_instrument_id' => 1,
        'receive_date' => '52 months 33000 minutes ago',
        'total_amount' => 250,
        'trxn_id' => 'P20193L6',
        'source' => 'Online: Help CiviCRM',
      ],
      11 => [
        'contact_id' => 71,
        'payment_instrument_id' => 1,
        'receive_date' => '28 hours ago',
        'total_amount' => 500,
        'trxn_id' => 'PL71',
        'source' => '',
        'currency' => 'JPY',
      ],

      12 => [
        'contact_id' => 43,
        'payment_instrument_id' => 1,
        'receive_date' => '15 months 38000 seconds ago',
        'total_amount' => 50,
        'trxn_id' => 'P291X1',
        'source' => 'Online: Save the Penguins',
      ],
      13 => [
        'contact_id' => 32,
        'payment_instrument_id' => 1,
        'receive_date' => 'midnight 3 months ago',
        'total_amount' => 50,
        'trxn_id' => 'PL32I',
        'source' => '',
      ],
      14 => [
        'contact_id' => 32,
        'payment_instrument_id' => 1,
        'receive_date' => 'midnight 2 months ago',
        'total_amount' => 50,
        'trxn_id' => 'PL32II',
        'source' => '',
      ],
    ];
    $recurrings = [
      [
        'contact_id' => 59,
        'amount' => 25,
        'currency' => 'USD',
        'frequency_interval' => 1,
        'frequency_unit' => 'month',
        'installments' => 12,
        'start_date' => '15 months ago',
        'processor_id' => 'CLC45',
        'trxn_id' => '56799',
        'contribution_status_id' => 1,
        'payment_processor_id' => 1,
      ],
      [
        'contact_id' => 99,
        'amount' => 10,
        'currency' => 'CAD',
        'frequency_interval' => 1,
        'frequency_unit' => 'month',
        'installments' => 6,
        'start_date' => '8 months ago',
        'cancel_date' => '1 month ago',
        'cancel_reason' => 'No longer interested',
        'processor_id' => 'CLR35',
        'trxn_id' => '22799',
        'contribution_status_id' => 3,
        'payment_processor_id' => 1,
      ],
      [
        'contact_id' => 103,
        'amount' => 5,
        'currency' => 'EUR',
        'frequency_interval' => 3,
        'frequency_unit' => 'month',
        'installments' => 3,
        'start_date' => '1 month ago',
        'processor_id' => 'EGR12',
        'trxn_id' => '44889',
        'contribution_status_id' => 5,
        'next_sched_contribution_date' => '+ 2 months',
        'payment_processor_id' => 1,
      ],
    ];
    // The process is a bit weird & the payment processor gets added later...
    // so we need to disable foreign key checks here.
    $this->_query('SET foreign_key_checks = 0');
    $contributionRecurID = 1;
    foreach ($recurrings as $recur) {
      $startDate = date('Y-m-d H:i:s', strtotime($recur['start_date']));
      $cancelDate = empty($recur['cancel_date']) ? 'NULL' : "'" . date('Y-m-d H:i:s', strtotime($recur['cancel_date'])) . "'";
      $nextScheduledDate = empty($recur['next_sched_contribution_date']) ? 'NULL' : "'" . date('Y-m-d H:i:s', strtotime($recur['next_sched_contribution_date'])) . "'";
      $this->_query("
        INSERT INTO civicrm_contribution_recur (
          contact_id, amount, currency, frequency_unit,
          frequency_interval, installments,
          start_date, cancel_date, cancel_reason, processor_id,
          trxn_id, contribution_status_id, next_sched_contribution_date, payment_processor_id)
        VALUES (
          %1, %2, %3, %4, %5, %6,
          %7, {$cancelDate}, %8, %9,
          %10, %11, {$nextScheduledDate}, 1
        )", [
          1 => [$recur['contact_id'] ?? NULL, 'Integer'],
          2 => [$recur['amount'], 'Money'],
          3 => [$recur['currency'], 'String'],
          4 => [$recur['frequency_unit'], 'String'],
          5 => [$recur['frequency_interval'], 'Integer'],
          6 => [$recur['installments'], 'Integer'],
          7 => [date('Y-m-d H:i:s', strtotime($recur['start_date'])), 'String'],
          8 => [$recur['cancel_reason'] ?? '', 'String'],
          9 => [$recur['processor_id'] ?? '', 'String'],
          10 => [$recur['trxn_id'], 'String'],
          11 => [$recur['contribution_status_id'], 'Integer'],
        ]
      );
      $contributionNumber = 1;
      $receive_date = $startDate;
      while ($contributionNumber < $recur['installments'] && strtotime($receive_date) < time()) {
        if (!empty($recur['cancel_date']) && strtotime($receive_date) > strtotime($recur['cancel_date'])) {
          continue;
        }
        $contributions[] = [
          'contact_id' => $recur['contact_id'],
          'payment_instrument_id' => 1,
          'receive_date' => $receive_date,
          'total_amount' => $recur['amount'],
          'currency' => $recur['currency'],
          'trxn_id' => 'PL32I' . $recur['contact_id'] . $contributionNumber,
          'source' => 'Recurring contribution',
          'contribution_recur_id' => $contributionRecurID,
        ];
        $receive_date = date('Y-m-d H:i:s', strtotime("+ {$recur['frequency_interval']} {$recur['frequency_unit']}", strtotime($receive_date)));
        $contributionNumber++;
      }
      $contributionRecurID++;
    }
    $this->_query('SET foreign_key_checks = 1');
    $contributionID = 1;
    $currentActivityID = CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM civicrm_activity') + 1;
    foreach ($contributions as $contribution) {
      $contribution = array_merge($defaults, $contribution);
      $contribution['receive_date'] = date('Y-m-d H:i:s', strtotime($contribution['receive_date']));
      $contributionObject = new CRM_Contribute_BAO_Contribution();
      $contributionObject->copyValues($contribution);
      $contributionObject->save();

      $symbols = [
        'USD' => '$',
        'CAD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'JPY' => '¥',
      ];
      $subject = $symbols[$contribution['currency']] . ' ' . $contribution['total_amount'] . ' ' . $contribution['source'];
      $this->_query('
INSERT INTO civicrm_activity
    (source_record_id, activity_type_id, subject, activity_date_time, duration, location, phone_id, phone_number, details, priority_id,parent_id, is_test, status_id)
VALUES (
     %1, 6, %2, %3, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2
  )',
        [
          1 => [$contributionID, 'Integer'],
          2 => [$subject, 'String'],
          3 => [$receive_date, 'String'],
        ]
      );
      $this->_query("INSERT INTO civicrm_activity_contact
  (contact_id, activity_id, record_type_id)
VALUES ({$contribution['contact_id']}, $currentActivityID, 2)");
      $contributionID++;
      $currentActivityID++;
    }
  }

  private function addSoftContribution() {

    $sql = "SELECT id FROM civicrm_contribution WHERE contact_id = 92";
    $contriId1 = CRM_Core_DAO::singleValueQuery($sql);

    $sql = "SELECT id FROM civicrm_contribution WHERE contact_id = 34";
    $contriId2 = CRM_Core_DAO::singleValueQuery($sql);

    $sql = "SELECT cov.value FROM civicrm_option_value cov LEFT JOIN civicrm_option_group cog ON cog.id = cov.option_group_id WHERE cov.name = 'pcp' AND cog.name = 'soft_credit_type'";

    $pcpId = CRM_Core_DAO::singleValueQuery($sql);

    $query = "
INSERT INTO `civicrm_contribution_soft`
      ( contribution_id, contact_id ,amount , currency, pcp_id , pcp_display_in_roll ,pcp_roll_nickname,pcp_personal_note, soft_credit_type_id )
VALUES
    ( $contriId1, {$this->Individual[3]}, 10.00, 'USD', 1, 1, 'Jones Family', 'Helping Hands', $pcpId),
    ( $contriId2, {$this->Individual[3]}, 250.00, 'USD', 1, 1, 'Annie and the kids', 'Annie Helps', $pcpId);
 ";

    $this->_query($query);
  }

  private function addPledge() {
    $pledge = "INSERT INTO civicrm_pledge
        (contact_id, financial_type_id, contribution_page_id, amount, original_installment_amount, currency,frequency_unit, frequency_interval, frequency_day, installments, start_date, create_date, acknowledge_date, modified_date, cancel_date, end_date, status_id, is_test)
        VALUES
       (71, 1, 1, 500.00, '500', 'USD', 'month', 1, 1, 1, '2009-07-01 00:00:00', '2009-06-26 00:00:00', NULL, NULL, NULL,'2009-07-01 00:00:00', 1, 0),
       (43, 1, 1, 800.00, '200', 'USD', 'month', 3, 1, 4, '2009-07-01 00:00:00', '2009-06-23 00:00:00', '2009-06-23 00:00:00', NULL, NULL, '2009-04-01 10:11:40', 5, 0),
       (32, 1, 1, 600.00, '200', 'USD', 'month', 1, 1, 3, '2009-10-01 00:00:00', '2009-09-14 00:00:00', '2009-09-14 00:00:00', NULL, NULL, '2009-12-01 00:00:00', 5, 0);
";
    $this->_query($pledge);
  }

  private function addPledgePayment() {
    $pledgePayment = "INSERT INTO civicrm_pledge_payment
        ( pledge_id, contribution_id, scheduled_amount, actual_amount, currency, scheduled_date, reminder_date, reminder_count, status_id)
       VALUES
         (1, 10, 500.00, 500.00, 'USD','2009-07-01 00:00:00', NULL, 0, 1 ),
         (2, 11,   200.00, 200.00, 'USD','2009-07-01 00:00:00', NULL, 0,  1 ),
         (2, NULL, 200.00, NULL, 'USD', '2009-10-01 00:00:00', NULL, 0,  2 ),
         (2, NULL, 200.00, NULL, 'USD', '2009-01-01 00:00:00', NULL, 0,  2 ),
         (2, NULL, 200.00, NULL, 'USD', '2009-04-01 00:00:00', NULL, 0,  2 ),

         (3, 12,   200.00, 200.00, 'USD', '2009-10-01 00:00:00', NULL, 0, 1 ),
         (3, 13,   200.00, 200.00, 'USD', '2009-11-01 00:0:00', '2009-10-28 00:00:00', 1, 1),
         (3, NULL, 200.00, NULL, 'USD', '2009-12-01 00:00:00', NULL, 0, 2 );
        ";
    $this->_query($pledgePayment);
  }

  private function addContributionLineItem() {
    $query = " INSERT INTO civicrm_line_item (`entity_table`, `entity_id`, contribution_id, `price_field_id`, `label`, `qty`, `unit_price`, `line_total`, `participant_count`, `price_field_value_id`, `financial_type_id`)
SELECT 'civicrm_contribution', cc.id, cc.id contribution_id, cpf.id AS price_field, cpfv.label, 1, cc.total_amount, cc.total_amount line_total, 0, cpfv.id AS price_field_value, cpfv.financial_type_id
FROM civicrm_contribution cc
LEFT JOIN civicrm_price_set cps ON cps.name = 'default_contribution_amount'
LEFT JOIN civicrm_price_field cpf ON cpf.price_set_id = cps.id
LEFT JOIN civicrm_price_field_value cpfv ON cpfv.price_field_id = cpf.id
ORDER BY cc.id; ";
    $this->_query($query);
  }

  private function addAccountingEntries() {
    $components = ['contribution', 'membership', 'participant'];
    $select = 'SELECT contribution.id contribution_id, cli.id as line_item_id, contribution.contact_id, contribution.receive_date, contribution.total_amount, contribution.currency, cli.label,
      cli.financial_type_id,  cefa.financial_account_id, contribution.payment_instrument_id, contribution.check_number, contribution.trxn_id';
    $where = 'WHERE cefa.account_relationship = 1';
    $financialAccountId = CRM_Financial_BAO_EntityFinancialAccount::getInstrumentFinancialAccount(4);
    foreach ($components as $component) {
      if ($component == 'contribution') {
        $from = 'FROM `civicrm_contribution` contribution';
      }
      else {
        $from = " FROM `civicrm_{$component}` {$component}
          INNER JOIN civicrm_{$component}_payment cpp ON cpp.{$component}_id = {$component}.id
          INNER JOIN civicrm_contribution contribution on contribution.id = cpp.contribution_id";
      }
      $from .= " INNER JOIN civicrm_line_item cli ON cli.entity_id = {$component}.id and cli.entity_table = 'civicrm_{$component}'
        INNER JOIN civicrm_entity_financial_account cefa ON cefa.entity_id =  cli.financial_type_id ";
      $sql = " {$select} {$from} {$where} ";
      $result = CRM_Core_DAO::executeQuery($sql);
      $this->addFinancialItem($result, $financialAccountId);
    }
  }

  /**
   * @param $result
   * @param null $financialAccountId
   */
  private function addFinancialItem($result, $financialAccountId) {
    $defaultFinancialAccount = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_financial_account WHERE is_default = 1 AND financial_account_type_id = 1");
    while ($result->fetch()) {
      $trxnParams = [
        'trxn_date' => CRM_Utils_Date::processDate($result->receive_date),
        'total_amount' => $result->total_amount,
        'currency' => $result->currency,
        'status_id' => 1,
        'trxn_id' => $result->trxn_id,
        'contribution_id' => $result->contribution_id,
        'to_financial_account_id' => empty($financialAccountId[$result->payment_instrument_id]) ? $defaultFinancialAccount : $financialAccountId[$result->payment_instrument_id],
        'payment_instrument_id' => $result->payment_instrument_id,
        'check_number' => $result->check_number,
        'is_payment' => 1,
      ];
      $trxn = CRM_Core_BAO_FinancialTrxn::create($trxnParams);
      $financialItem = [
        'transaction_date' => CRM_Utils_Date::processDate($result->receive_date),
        'amount' => $result->total_amount,
        'currency' => $result->currency,
        'status_id' => 1,
        'entity_id' => $result->line_item_id,
        'contact_id' => $result->contact_id,
        'entity_table' => 'civicrm_line_item',
        'description' => $result->label,
        'financial_account_id' => $result->financial_account_id,
      ];
      $trxnId['id'] = $trxn->id;
      CRM_Financial_BAO_FinancialItem::create($financialItem, NULL, $trxnId);
    }
  }

  private function addLineItemParticipants() {
    $participant = new CRM_Event_DAO_Participant();
    $participant->query("INSERT INTO civicrm_line_item (`entity_table`, `entity_id`, contribution_id, `price_field_id`, `label`, `qty`, `unit_price`, `line_total`, `participant_count`, `price_field_value_id`, `financial_type_id`)
SELECT 'civicrm_participant', cp.id, cpp.contribution_id, cpfv.price_field_id, cpfv.label, 1, cpfv.amount, cpfv.amount AS line_total, 0, cpfv.id, cpfv.financial_type_id FROM civicrm_participant cp LEFT JOIN civicrm_participant_payment cpp ON cpp.participant_id = cp.id
LEFT JOIN civicrm_price_set_entity cpe ON cpe.entity_id = cp.event_id LEFT JOIN civicrm_price_field cpf ON cpf.price_set_id = cpe.price_set_id LEFT JOIN civicrm_price_field_value cpfv ON cpfv.price_field_id = cpf.id WHERE cpfv.label = cp.fee_level");
  }

  private function addMembershipPayment() {
    $maxContribution = CRM_Core_DAO::singleValueQuery("SELECT MAX(id) FROM civicrm_contribution");
    $financialTypeID = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_financial_type WHERE name = 'Member Dues'");
    $paymentInstrumentID = CRM_Core_DAO::singleValueQuery("SELECT value FROM civicrm_option_value WHERE name = 'Credit Card' AND option_group_id = (SELECT id FROM civicrm_option_group WHERE name = 'payment_instrument')");
    $sql = "INSERT INTO civicrm_contribution (contact_id,financial_type_id,payment_instrument_id, receive_date, total_amount, currency, source, contribution_status_id, trxn_id)
SELECT  cm.contact_id, $financialTypeID, $paymentInstrumentID, now(), cmt.minimum_fee, 'USD', CONCAT(cmt.name, ' Membership: Offline signup'), 1, SUBSTRING(MD5(RAND()) FROM 1 FOR 16) FROM `civicrm_membership` cm
LEFT JOIN civicrm_membership_type cmt ON cmt.id = cm.membership_type_id;";

    $this->_query($sql);

    $sql = "INSERT INTO civicrm_membership_payment (contribution_id,membership_id)
SELECT cc.id, cm.id FROM civicrm_contribution cc
LEFT JOIN civicrm_membership cm ON cm.contact_id = cc.contact_id
WHERE cc.id > $maxContribution;";

    $this->_query($sql);

    $sql = "INSERT INTO civicrm_line_item (entity_table, entity_id, contribution_id, price_field_value_id, price_field_id, label, qty, unit_price, line_total, financial_type_id)
SELECT 'civicrm_membership', cm.id, cmp.contribution_id, cpfv.id, cpfv.price_field_id, cpfv.label, 1, cpfv.amount, cpfv.amount AS unit_price, cpfv.financial_type_id FROM `civicrm_membership` cm
LEFT JOIN civicrm_membership_payment cmp ON cmp.membership_id = cm.id
LEFT JOIN civicrm_price_field_value cpfv ON cpfv.membership_type_id = cm.membership_type_id
LEFT JOIN civicrm_price_field cpf ON cpf.id = cpfv.price_field_id
LEFT JOIN civicrm_price_set cps ON cps.id = cpf.price_set_id
WHERE cps.name = 'default_membership_type_amount'";
    $this->_query($sql);

    $sql = "INSERT INTO civicrm_activity(source_record_id, activity_type_id, subject, activity_date_time, status_id, details)
SELECT id, 6, CONCAT('$ ', total_amount, ' - ', source), now(), 2, 'Membership Payment' FROM civicrm_contribution WHERE id > $maxContribution";
    $this->_query($sql);

    $sql = "INSERT INTO civicrm_activity_contact(contact_id, activity_id, record_type_id)
SELECT c.contact_id, a.id, 2
FROM   civicrm_contribution c, civicrm_activity a
WHERE  c.id > $maxContribution
AND    a.source_record_id = c.id
AND    a.details = 'Membership Payment'
";
    $this->_query($sql);
  }

  private function addParticipantPayment() {
    $maxContribution = CRM_Core_DAO::singleValueQuery("SELECT MAX(id) FROM civicrm_contribution");
    $financialTypeID = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_financial_type WHERE name = 'Event Fee'");
    $paymentInstrumentID = CRM_Core_DAO::singleValueQuery("SELECT value FROM civicrm_option_value WHERE name = 'Credit Card' AND option_group_id = (SELECT id FROM civicrm_option_group WHERE name = 'payment_instrument')");
    $sql = "INSERT INTO civicrm_contribution (contact_id, financial_type_id, payment_instrument_id, receive_date, total_amount, currency, receipt_date, source, contribution_status_id, trxn_id)
SELECT  `contact_id`, $financialTypeID, $paymentInstrumentID, now(), `fee_amount`, 'USD', now(), CONCAT(ce.title, ' : Offline registration'), 1,  SUBSTRING(MD5(RAND()) FROM 1 FOR 16) FROM `civicrm_participant` cp
LEFT JOIN civicrm_event ce ON ce.id = cp.event_id
group by `contact_id`, `fee_amount`, `title`;";

    $this->_query($sql);

    $sql = "INSERT INTO civicrm_participant_payment (contribution_id,participant_id)
SELECT cc.id, cp.id FROM civicrm_contribution cc
LEFT JOIN civicrm_participant cp ON cp.contact_id = cc.contact_id
WHERE cc.id > $maxContribution";

    $this->_query($sql);

    $sql = "INSERT INTO civicrm_activity(source_record_id, activity_type_id, subject, activity_date_time, status_id, details)
SELECT id, 6, CONCAT('$ ', total_amount, ' - ', source), now(), 2, 'Participant' FROM `civicrm_contribution` WHERE id > $maxContribution";
    $this->_query($sql);

    $sql = "INSERT INTO civicrm_activity_contact(contact_id, activity_id, record_type_id)
SELECT c.contact_id, a.id, 2
FROM   civicrm_contribution c, civicrm_activity a
WHERE  c.id > $maxContribution
AND    a.source_record_id = c.id
AND    a.details = 'Participant Payment'
";
    $this->_query($sql);
  }

  /**
   * @return string
   */
  protected static function getCivicrmDir(): string {
    return dirname(dirname(dirname(__DIR__)));
  }

}
