<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
 */

/**
 * This class generates data for the schema located in Contact.sql
 *
 * each public method generates data for the concerned table.
 * so for example the addContactDomain method generates and adds
 * data to the contact_domain table
 *
 * Data generation is a bit tricky since the data generated
 * randomly in one table could be used as a FKEY in another
 * table.
 *
 * In order to ensure that a randomly generated FKEY matches
 * a field in the referened table, the field in the referenced
 * table is always generated linearly.
 *
 *
 *
 *
 * Some numbers
 *
 * Domain ID's - 1 to NUM_DOMAIN
 *
 * Context - 3/domain
 *
 * Contact - 1 to NUM_CONTACT
 *           75% - Individual
 *           15% - Household
 *           10% - Organization
 *
 *           Contact to Domain distribution should be equal.
 *
 *
 * Contact Individual = 1 to 0.75*NUM_CONTACT
 *
 * Contact Household = 0.75*NUM_CONTACT to 0.9*NUM_CONTACT
 *
 * Contact Organization = 0.9*NUM_CONTACT to NUM_CONTACT
 *
 * Contact Location = 15% for Households, 10% for Organizations, (75-(15*4))% for Individuals.
 *                     (Assumption is that each household contains 4 individuals)
 *
 */

/**
 *
 * Note: implication of using of mt_srand(1) in constructor
 * The data generated will be done in a consistent manner
 * so as to give the same data during each run (but this
 * would involve populating the entire db at one go - since
 * mt_srand(1) is in the constructor, if one needs to be able
 * to get consistent random numbers then the mt_srand(1) shld
 * be in each function that adds data to each table.
 *
 */

/*
 * Note as of 2019-07-15 this file does not appear to be called
 * from anywhere and seems to have issues running on more recent
 * php versions.
 * @todo look to remove this file completely.
 */


require_once '../civicrm.config.php';

// autoload
require_once 'CRM/Core/ClassLoader.php';
CRM_Core_ClassLoader::singleton()->register();

/**
 * Class CRM_GCD
 */
class CRM_GCD {

  /**
   * constants
   */
  const DATA_FILENAME = "sample_data.xml";
  const NUM_DOMAIN = 1;
  const NUM_CONTACT = 5000;
  const NUM_CONTRIBUTION = 2000;
  const NUM_MEMBERSHIP = 2000;
  const NUM_PARTICIPANT = 2000;
  const INDIVIDUAL_PERCENT = 75;
  const HOUSEHOLD_PERCENT = 15;
  const ORGANIZATION_PERCENT = 10;
  const NUM_INDIVIDUAL_PER_HOUSEHOLD = 4;
  const NUM_ACTIVITY = 150;

  // relationship types from the table crm_relationship_type
  const CHILD_OF = 1;
  const SPOUSE_OF = 2;
  const SIBLING_OF = 3;
  const HEAD_OF_HOUSEHOLD = 6;
  const MEMBER_OF_HOUSEHOLD = 7;


  // location types from the table crm_location_type
  const HOME = 1;
  const WORK = 2;
  const MAIN = 3;
  const OTHER = 4;
  const ADD_TO_DB = TRUE;
  //const ADD_TO_DB=FALSE;
  const DEBUG_LEVEL = 1;

  /***
   * private members
   */

  /**
   * enum's from database
   * @var array
   */
  private $preferredCommunicationMethod = array('1', '2', '3', '4', '5');
  private $contactType = array('Individual', 'Household', 'Organization');
  private $phoneType = array('1', '2', '3', '4');

  /**
   * customizable enums (foreign keys)
   * @var array
   */
  private $prefix = array(1 => 'Mrs', 2 => 'Ms', 3 => 'Mr', 4 => 'Dr');
  private $suffix = array(1 => 'Jr', 2 => 'Sr');
  private $gender = array(1 => 'Female', 2 => 'Male');
  private $greetingType = array(1 => 'Dear [first]', 2 => 'Dear [prefix] [first] [last]', 3 => 'Dear [prefix] [last]');

  /**
   * store domain id's
   * @var array
   */
  private $domain = array();

  /**
   * store contact id's
   * @var array
   */
  private $contact = array();
  private $individual = array();
  private $household = array();
  private $organization = array();


  /**
   * store names, firstnames, street 1, street2
   * @var array
   */
  private $firstName = array();
  private $lastName = array();
  private $streetName = array();
  private $supplementalAddress1 = array();
  private $city = array();
  private $state = array();
  private $country = array();
  private $addressDirection = array();
  private $streetType = array();
  private $emailDomain = array();
  private $emailTLD = array();
  private $organizationName = array();
  private $organizationField = array();
  private $organizationType = array();
  private $group = array();
  private $note = array();
  private $activity_type = array();
  private $module = array();
  private $callback = array();
  private $party_registration = array();
  private $degree = array();
  private $school = array();

  /**
   * stores the strict individual id and household id to individual id mapping
   * @var array
   */
  private $strictIndividual = array();
  private $householdIndividual = array();

  /**
   * sample data in xml format
   * @var int
   */
  private $sampleData = NULL;

  /**
   * private vars
   * @var int
   */
  private $numIndividual = 0;
  private $numHousehold = 0;
  private $numOrganization = 0;
  private $numStrictIndividual = 0;

  private $CSC = array(
    // united states
    1228 => array(
      // california
      1004 => array('San Francisco', 'Los Angeles', 'Palo Alto'),
      // new york
      1031 => array('New York', 'Albany'),
    ),
    // india
    1101 => array(
      // maharashtra
      1113 => array('Mumbai', 'Pune', 'Nasik'),
      // karnataka
      1114 => array('Bangalore', 'Mangalore', 'Udipi'),
    ),
    // poland
    1172 => array(
      // mazowieckie
      1115 => array('Warszawa', 'Płock'),
      // pomorskie
      1116 => array('Gdańsk', 'Gdynia'),
    ),
  );
  /**
   * @var array
   */
  private $groupMembershipStatus = array('Added', 'Removed', 'Pending');
  private $subscriptionHistoryMethod = array('Admin', 'Email');

  /**
   * private methods
   *
   * @param int $size
   * @return string
   */

  /**
   * Get a randomly generated string.
   *
   * @param int $size
   */
  private function _getRandomString($size = 32) {
    $string = "";

    // get an ascii code for each character
    for ($i = 0; $i < $size; $i++) {
      $random_int = mt_rand(65, 122);
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
  private function _getRandomChar() {
    return chr(mt_rand(65, 90));
  }

  /**
   * @return int
   */
  private function getRandomBoolean() {
    return mt_rand(0, 1);
  }

  /**
   * @param $array1
   *
   * @return mixed
   */
  private function _getRandomElement(&$array1) {
    return $array1[mt_rand(1, count($array1)) - 1];
  }

  /**
   * @param $array1
   *
   * @return int
   */
  private function _getRandomIndex(&$array1) {
    return mt_rand(1, count($array1));
  }

  // country state city combo

  /**
   * @return array
   */
  private function _getRandomCSC() {
    $array1 = array();

    // $c = array_rand($this->CSC);
    $c = 1228;

    // the state array now
    $s = array_rand($this->CSC[$c]);

    // the city
    $ci = array_rand($this->CSC[$c][$s]);
    $city = $this->CSC[$c][$s][$ci];

    $array1[] = $c;
    $array1[] = $s;
    $array1[] = $city;

    return $array1;
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
   * @param  int $startDate Start Date in Unix timestamp
   * @param  int $endDate   End Date in Unix timestamp
   * @access private
   *
   * @return string randomly generated date in the format "Ymd"
   *
   */
  private function _getRandomDate($startDate = 0, $endDate = 0) {

    // number of seconds per year
    // $numSecond = 31536000;
    // number of seconds for 2 year
    $numSecond = 63072000;

    $dateFormat = "YmdHis";
    $today = time();

    // both are defined
    if ($startDate && $endDate) {
      return date($dateFormat, mt_rand($startDate, $endDate));
    }

    // only startDate is defined
    if ($startDate) {
      // $nextYear = mktime(0, 0, 0, date("m", $startDate),   date("d", $startDate),   date("Y")+1);
      return date($dateFormat, mt_rand($startDate, $startDate + $numSecond));
    }

    // only endDate is defined
    if ($startDate) {
      return date($dateFormat, mt_rand($endDate - $numSecond, $endDate));
    }

    // none are defined
    return date($dateFormat, mt_rand($today - $numSecond, $today));
  }

  // insert data into db's

  /**
   * @param $dao
   */
  private function _insert(&$dao) {
    if (self::ADD_TO_DB) {
      if (!$dao->insert()) {
        echo "ERROR INSERT: " . mysqli_error($dao->getConnection()->connection) . "\n";
        print_r($dao);
        exit(1);
      }
    }
  }

  // update data into db's

  /**
   * @param $dao
   */
  private function _update($dao) {
    if (self::ADD_TO_DB) {
      if (!$dao->update()) {
        echo "ERROR UPDATE: " . mysqli_error($dao->getConnection()->connection) . "\n";
        print_r($dao);
        exit(1);
      }
    }
  }

  /**
   * Insert a note
   *
   *   Helper function which randomly populates "note" and
   *   "date_modified" and inserts it.
   *
   * @param  $note
   * @access private
   *
   */
  private function _insertNote($note) {
    $note->note = $this->_getRandomElement($this->note);
    $note->modified_date = $this->_getRandomDate();
    $this->_insert($note);
  }

  /**
   *
   * Start of public functions
   *
   */
  public function __construct() {
    // initialize all the vars
    $this->numIndividual = self::INDIVIDUAL_PERCENT * self::NUM_CONTACT / 100;
    $this->numHousehold = self::HOUSEHOLD_PERCENT * self::NUM_CONTACT / 100;
    $this->numOrganization = self::ORGANIZATION_PERCENT * self::NUM_CONTACT / 100;
    $this->numStrictIndividual = $this->numIndividual - ($this->numHousehold * self::NUM_INDIVIDUAL_PER_HOUSEHOLD);
  }

  public function parseDataFile() {

    $sampleData = simplexml_load_file(self::DATA_FILENAME);

    // first names
    foreach ($sampleData->first_names->first_name as $first_name) {
      $this->firstName[] = trim($first_name);
    }

    // last names
    foreach ($sampleData->last_names->last_name as $last_name) {
      $this->lastName[] = trim($last_name);
    }

    //  street names
    foreach ($sampleData->street_names->street_name as $street_name) {
      $this->streetName[] = trim($street_name);
    }

    //  supplemental address 1
    foreach ($sampleData->supplemental_addresses_1->supplemental_address_1 as $supplemental_address_1) {
      $this->supplementalAddress1[] = trim($supplemental_address_1);
    }

    //  cities
    foreach ($sampleData->cities->city as $city) {
      $this->city[] = trim($city);
    }

    //  address directions
    foreach ($sampleData->address_directions->address_direction as $address_direction) {
      $this->addressDirection[] = trim($address_direction);
    }

    // street types
    foreach ($sampleData->street_types->street_type as $street_type) {
      $this->streetType[] = trim($street_type);
    }

    // email domains
    foreach ($sampleData->email_domains->email_domain as $email_domain) {
      $this->emailDomain[] = trim($email_domain);
    }

    // email top level domain
    foreach ($sampleData->email_tlds->email_tld as $email_tld) {
      $this->emailTLD[] = trim($email_tld);
    }

    // organization name
    foreach ($sampleData->organization_names->organization_name as $organization_name) {
      $this->organization_name[] = trim($organization_name);
    }

    // organization field
    foreach ($sampleData->organization_fields->organization_field as $organization_field) {
      $this->organizationField[] = trim($organization_field);
    }

    // organization type
    foreach ($sampleData->organization_types->organization_type as $organization_type) {
      $this->organizationType[] = trim($organization_type);
    }

    // group
    foreach ($sampleData->groups->group as $group) {
      $this->group[] = trim($group);
    }

    // notes
    foreach ($sampleData->notes->note as $note) {
      $this->note[] = trim($note);
    }

    // activity type
    foreach ($sampleData->activity_types->activity_type as $activity_type) {
      $this->activity_type[] = trim($activity_type);
    }

    // module
    foreach ($sampleData->modules->module as $module) {
      $this->module[] = trim($module);
    }

    // callback
    foreach ($sampleData->callbacks->callback as $callback) {
      $this->callback[] = trim($callback);
    }

    // custom data - party registration
    foreach ($sampleData->party_registrations->party_registration as $party_registration) {
      $this->party_registration[] = trim($party_registration);
    }

    // custom data - degrees
    foreach ($sampleData->degrees->degree as $degree) {
      $this->degree[] = trim($degree);
    }

    // custom data - schools
    foreach ($sampleData->schools->school as $school) {
      $this->school[] = trim($school);
    }

    // custom data - issue
    foreach ($sampleData->issue->status as $status) {
      $this->issue[] = trim($status);
    }

    // custom data - gotv
    require_once 'CRM/Core/BAO/CustomOption.php';
    foreach ($sampleData->gotv->status as $status) {
      $this->gotv[] = CRM_Core_DAO::VALUE_SEPARATOR . trim($status) . CRM_Core_DAO::VALUE_SEPARATOR;
    }

    // custom data - marital_status
    foreach ($sampleData->marital_status->status as $status) {
      $this->marital_status[] = trim($status);
    }
  }

  /**
   * @param $id
   *
   * @return string
   */
  public function getContactType($id) {
    if (in_array($id, $this->individual)) {
      return 'Individual';
    }
    if (in_array($id, $this->household)) {
      return 'Household';
    }
    if (in_array($id, $this->organization)) {
      return 'Organization';
    }
  }

  public function initDB() {
    $config = CRM_Core_Config::singleton();
  }

  /**
   *
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
   *
   */
  public function initID() {

    // may use this function in future if needed to get
    // a consistent pattern of random numbers.

    // get the domain and contact id arrays
    $this->domain = range(1, self::NUM_DOMAIN);
    shuffle($this->domain);
    $this->contact = range(2, self::NUM_CONTACT + 1);
    shuffle($this->contact);

    // get the individual, household  and organizaton contacts
    $offset = 0;
    $this->individual = array_slice($this->contact, $offset, $this->numIndividual);
    $offset += $this->numIndividual;
    $this->household = array_slice($this->contact, $offset, $this->numHousehold);
    $offset += $this->numHousehold;
    $this->organization = array_slice($this->contact, $offset, $this->numOrganization);

    // get the strict individual contacts (i.e individual contacts not belonging to any household)
    $this->strictIndividual = array_slice($this->individual, 0, $this->numStrictIndividual);

    // get the household to individual mapping array
    $this->householdIndividual = array_diff($this->individual, $this->strictIndividual);
    $this->householdIndividual = array_chunk($this->householdIndividual, self::NUM_INDIVIDUAL_PER_HOUSEHOLD);
    $this->householdIndividual = array_combine($this->household, $this->householdIndividual);
  }

  /**
   *
   * addDomain()
   *
   * This method adds NUM_DOMAIN domains and then adds NUM_REVISION
   * revisions for each domain with the latest revision being the last one..
   *
   */
  public function addDomain() {

    /* Add a location for domain 1 */

    // FIXME FOR NEW LOCATION BLOCK STRUCTURE
    // $this->_addLocation(self::MAIN, 1, true);

    $domain = new CRM_Core_DAO_Domain();
    for ($id = 2; $id <= self::NUM_DOMAIN; $id++) {
      // domain name is pretty simple. it is "Domain $id"
      $domain->name = "Domain $id";
      $domain->description = "Description $id";
      $domain->contact_name = $this->randomName();

      // insert domain
      $this->_insert($domain);
      // FIXME FOR NEW LOCATION BLOCK STRUCTURE
      // $this->_addLocation(self::MAIN, $id, true);
    }
  }

  /**
   * @return string
   */
  public function randomName() {
    $prefix      = $this->_getRandomIndex($this->prefix);
    $first_name  = ucfirst($this->_getRandomElement($this->firstName));
    $middle_name = ucfirst($this->_getRandomChar());
    $last_name   = ucfirst($this->_getRandomElement($this->lastName));
    $suffix      = $this->_getRandomIndex($this->suffix);

    return $this->prefix[$prefix] . " $first_name $middle_name $last_name " . $this->suffix[$suffix];
  }

  /**
   *
   * addContact()
   *
   * This method adds data to the contact table
   *
   * id - from $contact
   * contact_type 'Individual' 'Household' 'Organization'
   * preferred_communication (random 1 to 3)
   *
   */
  public function addContact() {

    // add contacts
    $contact = new CRM_Contact_DAO_Contact();

    for ($id = 1; $id <= self::NUM_CONTACT; $id++) {
      $contact->contact_type = $this->getContactType($id + 1);
      $contact->do_not_phone = mt_rand(0, 1);
      $contact->do_not_email = mt_rand(0, 1);
      $contact->do_not_post = mt_rand(0, 1);
      $contact->do_not_trade = mt_rand(0, 1);
      $contact->preferred_communication_method = $this->_getRandomElement($this->preferredCommunicationMethod);
      $this->_insert($contact);
    }
  }

  /**
   *
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
   * greeting_type - randomly select from the enum values
   * custom_greeting - "custom greeting $contact_uuid'
   *
   */
  public function addIndividual() {

    $contact = new CRM_Contact_DAO_Contact();

    for ($id = 1; $id <= $this->numIndividual; $id++) {
      $contact->first_name = ucfirst($this->_getRandomElement($this->firstName));
      $contact->middle_name = ucfirst($this->_getRandomChar());
      $contact->last_name = ucfirst($this->_getRandomElement($this->lastName));
      $contact->prefix_id = $this->_getRandomIndex($this->prefix);
      $contact->suffix_id = $this->_getRandomIndex($this->suffix);
      $contact->greeting_type_id = $this->_getRandomIndex($this->greetingType);
      $contact->gender_id = $this->_getRandomIndex($this->gender);
      $contact->birth_date = date("Ymd", mt_rand(0, time()));
      $contact->is_deceased = mt_rand(0, 1);

      $contact->id = $this->individual[($id - 1)];

      // also update the sort name for the contact id.
      $contact->display_name = trim($this->prefix[$contact->prefix_id] . " $contact->first_name $contact->middle_name $contact->last_name " . $this->suffix[$contact->suffix_id]);
      $contact->sort_name = $contact->last_name . ', ' . $contact->first_name;
      $contact->hash = crc32($contact->sort_name);
      $this->_update($contact);
    }
  }

  /**
   *
   * addHousehold()
   *
   * This method adds household's data to the contact table
   *
   * The following fields are generated and added.
   *
   * contact_uuid - household_individual
   * contact_rid - latest one
   * household_name 'household $contact_uuid primary contact $primary_contact_uuid'
   * nick_name 'nick $contact_uuid'
   * primary_contact_uuid = $household_individual[$contact_uuid][0];
   *
   */
  public function addHousehold() {

    $contact = new CRM_Contact_DAO_Contact();
    for ($id = 1; $id <= $this->numHousehold; $id++) {
      $cid = $this->household[($id - 1)];
      $contact->primary_contact_id = $this->householdIndividual[$cid][0];

      // get the last name of the primary contact id
      $individual = new CRM_Contact_DAO_Contact();
      $individual->id = $contact->primary_contact_id;
      $individual->find(TRUE);
      $firstName = $individual->first_name;
      $lastName = $individual->last_name;

      // need to name the household and nick name appropriately
      $contact->household_name = "$firstName $lastName" . "'s home";
      $contact->nick_name = "$lastName" . "'s home";

      $contact->id = $this->household[($id - 1)];
      // need to update the sort name for the main contact table
      $contact->display_name = $contact->sort_name = $contact->household_name;
      $contact->hash = crc32($contact->sort_name);
      $this->_update($contact);
    }
  }

  /**
   *
   * addOrganization()
   *
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
  public function addOrganization() {

    $contact = new CRM_Contact_DAO_Contact();

    for ($id = 1; $id <= $this->numOrganization; $id++) {
      $contact->id = $this->organization[($id - 1)];
      $name = $this->_getRandomElement($this->organization_name) . " " . $this->_getRandomElement($this->organization_field) . " " . $this->_getRandomElement($this->organization_type);
      $contact->organization_name = $name;
      $contact->primary_contact_id = $this->_getRandomElement($this->strict_individual);

      // need to update the sort name for the main contact table
      $contact->display_name = $contact->sort_name = $contact->organization_name;
      $contact->hash = crc32($contact->sort_name);
      $this->_update($contact);
    }
  }

  /**
   *
   * addRelationship()
   *
   * This method adds data to the contact_relationship table
   *
   * it adds the following fields
   *
   */
  public function addRelationship() {

    $relationship = new CRM_Contact_DAO_Relationship();

    // all active for now.
    $relationship->is_active = 1;

    foreach ($this->householdIndividual as $household_id => $household_member) {
      // add child_of relationship
      // 2 for each child
      $relationship->relationship_type_id = self::CHILD_OF;
      $relationship->contact_id_a = $household_member[2];
      $relationship->contact_id_b = $household_member[0];
      $this->_insert($relationship);
      $relationship->contact_id_a = $household_member[3];
      $relationship->contact_id_b = $household_member[0];
      $this->_insert($relationship);
      $relationship->contact_id_a = $household_member[2];
      $relationship->contact_id_b = $household_member[1];
      $this->_insert($relationship);
      $relationship->contact_id_a = $household_member[3];
      $relationship->contact_id_b = $household_member[1];
      $this->_insert($relationship);

      // add spouse_of relationship 1 for both the spouses
      $relationship->relationship_type_id = self::SPOUSE_OF;
      $relationship->contact_id_a = $household_member[1];
      $relationship->contact_id_b = $household_member[0];
      $this->_insert($relationship);

      // add sibling_of relationship 1 for both the siblings
      $relationship->relationship_type_id = self::SIBLING_OF;
      $relationship->contact_id_a = $household_member[3];
      $relationship->contact_id_b = $household_member[2];
      $this->_insert($relationship);

      // add head_of_household relationship 1 for head of house
      $relationship->relationship_type_id = self::HEAD_OF_HOUSEHOLD;
      $relationship->contact_id_a = $household_member[0];
      $relationship->contact_id_b = $household_id;
      $this->_insert($relationship);

      // add member_of_household relationship 3 for all other members
      $relationship->relationship_type_id = self::MEMBER_OF_HOUSEHOLD;
      $relationship->contact_id_a = $household_member[1];
      $this->_insert($relationship);
      $relationship->contact_id_a = $household_member[2];
      $this->_insert($relationship);
      $relationship->contact_id_a = $household_member[3];
      $this->_insert($relationship);
    }
  }

  /**
   *
   * addLocation()
   *
   * This method adds data to the location table
   *
   */
  public function addLocation() {
    // strict individuals
    foreach ($this->strictIndividual as $contactId) {
      $this->_addLocation(self::HOME, $contactId);
    }

    //household
    foreach ($this->household as $contactId) {
      $this->_addLocation(self::HOME, $contactId);
    }

    //organization
    foreach ($this->organization as $contactId) {
      $this->_addLocation(self::MAIN, $contactId);
    }

    // some individuals.
    $someIndividual = array_diff($this->individual, $this->strictIndividual);
    $someIndividual = array_slice($someIndividual, 0, (int) (75 * ($this->numIndividual - $this->numStrictIndividual) / 100));
    foreach ($someIndividual as $contactId) {
      $this->_addLocation(self::HOME, $contactId, FALSE, TRUE);
    }
  }

  /**
   * @param $locationTypeId
   * @param $contactId
   * @param bool $domain
   * @param bool $isPrimary
   */
  private function _addLocation($locationTypeId, $contactId, $domain = FALSE, $isPrimary = TRUE) {
    $this->_addAddress($locationTypeId, $contactId, $isPrimary);

    // add two phones for each location
    $this->_addPhone($locationTypeId, $contactId, '1', $isPrimary);
    $this->_addPhone($locationTypeId, $contactId, '2', FALSE);

    // need to get sort name to generate email id
    $contact = new CRM_Contact_DAO_Contact();
    $contact->id = $contactId;
    $contact->find(TRUE);
    // get the sort name of the contact
    $sortName = $contact->sort_name;
    if (!empty($sortName)) {
      // add 2 email for each location
      for ($emailId = 1; $emailId <= 2; $emailId++) {
        $this->_addEmail($locationTypeId, $contactId, $sortName, ($emailId == 1) && $isPrimary);
      }
    }
  }

  /**
   * @param $locationTypeId
   * @param $contactId
   * @param bool $isPrimary
   * @param null $locationBlockID
   * @param int $offset
   */
  private function _addAddress($locationTypeId, $contactId, $isPrimary = FALSE, $locationBlockID = NULL, $offset = 1) {
    $addressDAO = new CRM_Core_DAO_Address();

    // add addresses now currently we are adding only 1 address for each location
    $addressDAO->location_type_id = $locationTypeId;
    $addressDAO->contact_id = $contactId;
    $addressDAO->is_primary = $isPrimary;

    $addressDAO->street_number = mt_rand(1, 1000);
    $addressDAO->street_number_suffix = ucfirst($this->_getRandomChar());
    $addressDAO->street_number_predirectional = $this->_getRandomElement($this->addressDirection);
    $addressDAO->street_name = ucwords($this->_getRandomElement($this->streetName));
    $addressDAO->street_type = $this->_getRandomElement($this->streetType);
    $addressDAO->street_number_postdirectional = $this->_getRandomElement($this->addressDirection);
    $addressDAO->street_address = $addressDAO->street_number_predirectional . " " . $addressDAO->street_number . $addressDAO->street_number_suffix . " " . $addressDAO->street_name . " " . $addressDAO->street_type . " " . $addressDAO->street_number_postdirectional;
    $addressDAO->supplemental_address_1 = ucwords($this->_getRandomElement($this->supplementalAddress1));

    // some more random skips
    // hack add lat / long for US based addresses
    list($addressDAO->country_id, $addressDAO->state_province_id, $addressDAO->city,
      $addressDAO->postal_code, $addressDAO->geo_code_1, $addressDAO->geo_code_2
    ) = self::getZipCodeInfo();

    //$addressDAO->county_id = 1;

    $this->_insert($addressDAO);
  }

  /**
   * @param $sortName
   *
   * @return mixed
   */
  private function _sortNameToEmail($sortName) {
    $email = preg_replace("([^a-zA-Z0-9_-]*)", "", $sortName);
    return $email;
  }

  /**
   * @param $locationTypeId
   * @param $contactId
   * @param $phoneType
   * @param bool $isPrimary
   * @param null $locationBlockID
   * @param int $offset
   */
  private function _addPhone($locationTypeId, $contactId, $phoneType, $isPrimary = FALSE, $locationBlockID = NULL, $offset = 1) {
    if ($contactId % 3) {
      $phone = new CRM_Core_DAO_Phone();
      $phone->location_type_id = $locationTypeId;
      $phone->contact_id = $contactId;
      $phone->is_primary = $isPrimary;
      $phone->phone = mt_rand(11111111, 99999999);
      $phone->phone_type_id = $phoneType;
      $this->_insert($phone);
    }
  }

  /**
   * @param $locationTypeId
   * @param $contactId
   * @param $sortName
   * @param bool $isPrimary
   * @param null $locationBlockID
   * @param int $offset
   */
  private function _addEmail($locationTypeId, $contactId, $sortName, $isPrimary = FALSE, $locationBlockID = NULL, $offset = 1) {
    if ($contactId % 2) {
      $email = new CRM_Core_DAO_Email();
      $email->location_type_id = $locationTypeId;
      $email->contact_id = $contactId;
      $email->is_primary = $isPrimary;

      $emailName    = $this->_sortNameToEmail($sortName);
      $emailDomain  = $this->_getRandomElement($this->emailDomain);
      $tld          = $this->_getRandomElement($this->emailTLD);
      $email->email = strtolower($emailName . "@" . $emailDomain . "." . $tld);
      $this->_insert($email);
    }
  }

  /**
   *
   * addTagEntity()
   *
   * This method populates the crm_entity_tag table
   *
   */
  public function addEntityTag() {

    $entity_tag = new CRM_Core_DAO_EntityTag();

    // add categories 1,2,3 for Organizations.
    for ($i = 0; $i < $this->numOrganization; $i += 2) {
      $org_id = $this->organization[$i];
      // echo "org_id = $org_id\n";
      $entity_tag->contact_id = $this->organization[$i];
      $entity_tag->tag_id = mt_rand(1, 3);
      $this->_insert($entity_tag);
    }

    // add categories 4,5 for Individuals.
    for ($i = 0; $i < $this->numIndividual; $i += 2) {
      $entity_tag->contact_id = $this->individual[$i];
      if (($entity_tag->contact_id) % 3) {
        $entity_tag->tag_id = mt_rand(4, 5);
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
   *
   * addGroup()
   *
   * This method populates the crm_entity_tag table
   *
   */
  public function addGroup() {
    // add the 3 groups first
    $numGroup = count($this->group);
    require_once 'CRM/Contact/BAO/Group.php';
    for ($i = 0; $i < $numGroup; $i++) {
      $group             = new CRM_Contact_BAO_Group();
      $group->name       = $this->group[$i];
      $group->title      = $this->group[$i];
      $group->group_type = "12";
      $group->visibility = 'Public Pages';
      $group->is_active  = 1;
      $group->save();
      $group->buildClause();
      $group->save();
    }

    // 60 are for newsletter
    for ($i = 0; $i < 60; $i++) {
      $groupContact = new CRM_Contact_DAO_GroupContact();
      // newsletter subscribers
      $groupContact->group_id = 2;
      $groupContact->contact_id = $this->individual[$i];
      // membership status
      $groupContact->status = $this->_getRandomElement($this->groupMembershipStatus);

      $subscriptionHistory = new CRM_Contact_DAO_SubscriptionHistory();
      $subscriptionHistory->contact_id = $groupContact->contact_id;

      $subscriptionHistory->group_id = $groupContact->group_id;
      $subscriptionHistory->status = $groupContact->status;
      // method
      $subscriptionHistory->method = $this->_getRandomElement($this->subscriptionHistoryMethod);
      $subscriptionHistory->date = $this->_getRandomDate();
      if ($groupContact->status != 'Pending') {
        $this->_insert($groupContact);
      }
      $this->_insert($subscriptionHistory);
    }

    // 15 volunteers
    for ($i = 0; $i < 15; $i++) {
      $groupContact = new CRM_Contact_DAO_GroupContact();
      // Volunteers
      $groupContact->group_id = 3;
      $groupContact->contact_id = $this->individual[$i + 60];
      // membership status
      $groupContact->status = $this->_getRandomElement($this->groupMembershipStatus);

      $subscriptionHistory = new CRM_Contact_DAO_SubscriptionHistory();
      $subscriptionHistory->contact_id = $groupContact->contact_id;
      $subscriptionHistory->group_id = $groupContact->group_id;
      $subscriptionHistory->status = $groupContact->status;
      // method
      $subscriptionHistory->method = $this->_getRandomElement($this->subscriptionHistoryMethod);
      $subscriptionHistory->date = $this->_getRandomDate();

      if ($groupContact->status != 'Pending') {
        $this->_insert($groupContact);
      }
      $this->_insert($subscriptionHistory);
    }

    // 8 advisory board group
    for ($i = 0; $i < 8; $i++) {
      $groupContact = new CRM_Contact_DAO_GroupContact();
      // advisory board group
      $groupContact->group_id = 4;
      $groupContact->contact_id = $this->individual[$i * 7];
      // membership status
      $groupContact->status = $this->_getRandomElement($this->groupMembershipStatus);

      $subscriptionHistory = new CRM_Contact_DAO_SubscriptionHistory();
      $subscriptionHistory->contact_id = $groupContact->contact_id;
      $subscriptionHistory->group_id = $groupContact->group_id;
      $subscriptionHistory->status = $groupContact->status;
      // method
      $subscriptionHistory->method = $this->_getRandomElement($this->subscriptionHistoryMethod);
      $subscriptionHistory->date = $this->_getRandomDate();

      if ($groupContact->status != 'Pending') {
        $this->_insert($groupContact);
      }
      $this->_insert($subscriptionHistory);
    }

    //In this function when we add groups that time we are cache the contact fields
    //But at the end of setup we are appending sample custom data, so for consistency
    //reset the cache.
    Civi::cache('fields')->flush();
    CRM_Core_BAO_Cache::resetCaches();
  }

  /**
   *
   * addNote()
   *
   * This method populates the crm_note table
   *
   */
  public function addNote() {

    $note               = new CRM_Core_DAO_Note();
    $note->entity_table = 'civicrm_contact';
    $note->contact_id   = 1;

    for ($i = 0; $i < self::NUM_CONTACT; $i++) {
      $note->entity_id = $this->contact[$i];
      if ($this->contact[$i] % 5 || $this->contact[$i] % 3 || $this->contact[$i] % 2) {
        $this->_insertNote($note);
      }
    }
  }

  /**
   *
   * addActivity()
   *
   * This method populates the crm_activity_history table
   *
   */
  public function addActivity() {
    $contactDAO = new CRM_Contact_DAO_Contact();
    $contactDAO->contact_type = 'Individual';
    $contactDAO->selectAdd();
    $contactDAO->selectAdd('id');
    $contactDAO->orderBy('sort_name');
    $contactDAO->find();

    $count = 0;
    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');

    while ($contactDAO->fetch()) {
      if ($count++ > 2) {
        break;
      }
      for ($i = 0; $i < self::NUM_ACTIVITY; $i++) {
        $activityDAO = new CRM_Activity_DAO_Activity();
        $activityDAO->source_contact_id = $contactDAO->id;
        $activityTypeID = mt_rand(7, 10);

        $activityDAO->activity_type_id = $activityTypeID;
        $activityDAO->subject = "Subject for $activity[$activityTypeID]";
        $activityDAO->activity_date_time = $this->_getRandomDate();
        $activityDAO->duration = mt_rand(1, 6);
        $activityDAO->status_id = 2;
        $this->_insert($activityDAO);

        $activityContactDAO = new CRM_Activity_DAO_ActivityContact();
        $activityContactDAO->activity_id = $activityDAO->id;
        $activityContactDAO->contact_id = mt_rand(1, 101);
        $activityContactDAO->record_type_id = CRM_Utils_Array::key('Activity Source', $activityContacts);
        $this->_insert($activityContactDAO);

        if (in_array($activityTypeID, array(
          6, 9,
        ))) {
          $activityTargetDAO = new CRM_Activity_DAO_ActivityContact();
          $activityTargetDAO->activity_id = $activityDAO->id;
          $activityTargetDAO->contact_id = mt_rand(1, 101);
          $activityTargetDAO->record_type_id = CRM_Utils_Array::key('Activity Targets', $activityContacts);
          $this->_insert($activityTargetDAO);
        }

        if ($activityTypeID == 7) {
          $activityAssignmentDAO = new CRM_Activity_DAO_ActivityContact();
          $activityAssignmentDAO->activity_id = $activityDAO->id;
          $activityAssignmentDAO->contact_id = mt_rand(1, 101);
          $activityAssignmentDAO->record_type_id = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
          $this->_insert($activityAssignmentDAO);
        }
      }
    }
  }

  /**
   * @return array
   */
  public static function getZipCodeInfo() {
    $stateID = mt_rand(1000, 5132);
    $offset = mt_rand(1, 4132);

    $query = "SELECT id, country_id from civicrm_state_province LIMIT $offset, 1";
    $dao = new CRM_Core_DAO();
    $dao->query($query);
    while ($dao->fetch()) {
      return array($dao->country_id, $dao->id);
    }

    return array();
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

    // close CURL resource, and free up system resources
    curl_close($ch);

    $preg = "/'(<\?xml.+?)',/s";
    preg_match($preg, $outstr, $matches);
    if ($matches[1]) {
      $xml = simplexml_load_string($matches[1]);
      $attributes = $xml->center->attributes();
      if (!empty($attributes)) {
        return array((float ) $attributes['lat'], (float ) $attributes['lng']);
      }
    }
    return array(NULL, NULL);
  }

  public function addMembershipType() {
    $organizationDAO = new CRM_Contact_DAO_Contact();
    $organizationDAO->id = 5;
    $organizationDAO->find(TRUE);
    $contact_id = $organizationDAO->contact_id;

    $membershipType = "INSERT INTO civicrm_membership_type
        (name, description, member_of_contact_id, financial_type_id, minimum_fee, duration_unit, duration_interval, period_type, fixed_period_start_day, fixed_period_rollover_day, relationship_type_id, relationship_direction, visibility, weight, is_active)
        VALUES
        ('General', 'Regular annual membership.', " . $contact_id . ", 3, 100, 'year', 1, 'rolling',null, null, 7, 'b_a', 'Public', 1, 1),
        ('Student', 'Discount membership for full-time students.', " . $contact_id . ", 1, 50, 'year', 1, 'rolling', null, null, 7, 'b_a', 'Public', 2, 1),
        ('Lifetime', 'Lifetime membership.', " . $contact_id . ", 2, 1200, 'lifetime', 1, 'rolling', null, null, 7, 'b_a', 'Admin', 3, 1);
        ";
    CRM_Core_DAO::executeQuery($membershipType);
  }

  public function addMembership() {
    $contact = new CRM_Contact_DAO_Contact();
    $contact->query("SELECT id FROM civicrm_contact where contact_type = 'Individual'");
    while ($contact->fetch()) {
      $contacts[] = $contact->id;
    }
    shuffle($contacts);

    $randomContacts = array_slice($contacts, 0, 350);

    $sources             = array('Payment', 'Donation', 'Check');
    $membershipTypes     = array(2, 1);
    $membershipTypeNames = array('Student', 'General');
    $statuses            = array(3, 4);

    $membership = "
 INSERT INTO civicrm_membership
        (contact_id, membership_type_id, join_date, start_date, end_date, source, status_id)
VALUES
 ";
    $activity = "
INSERT INTO civicrm_activity
        (source_contact_id, source_record_id, activity_type_id, subject, activity_date_time, duration, location, phone_id, phone_number, details, priority_id,parent_id, is_test, status_id)
VALUES
";

    foreach ($randomContacts as $count => $dontCare) {
      $source = self::_getRandomElement($sources);
      $acititySourceId = $count + 1;
      if ((($count + 1) % 11 == 0)) {
        // lifetime membership, status can be anything
        $startDate = date('Y-m-d', mktime(0, 0, 0, date('m'), (date('d') - $count), date('Y')));
        $membership .= "( {$randomContacts[$count]}, 3, '{$startDate}', '{$startDate}', null, '{$source}', 1)";
        $activity .= "( {$randomContacts[$count]}, {$acititySourceId}, 7, 'Lifetime', '{$startDate} 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 )";
      }
      elseif (($count + 1) % 5 == 0) {
        // Grace or expired, memberhsip type is random of 1 & 2
        $randId         = array_rand($membershipTypes);
        $membershipType = self::_getRandomElement($membershipTypes);
        $startDate      = date('Y-m-d', mktime(0, 0, 0,
            date('m'),
            (date('d') - ($count * ($randId + 1) * ($randId + 1) * ($randId + 1))),
            (date('Y') - ($randId + 1))
          ));
        $partOfDate = explode('-', $startDate);
        $endDate = date('Y-m-d', mktime(0, 0, 0,
            $partOfDate[1],
            ($partOfDate[2] - 1),
            ($partOfDate[0] + ($randId + 1))
          ));
        $membership .= "( {$randomContacts[$count]}, {$membershipType}, '{$startDate}', '{$startDate}', '{$endDate}', '{$source}', {$statuses[$randId]})";
        $activity .= "( {$randomContacts[$count]}, {$acititySourceId}, 7, '{$membershipTypeNames[$randId]}', '{$startDate} 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 )";
      }
      elseif (($count + 1) % 2 == 0) {
        // membership type 2
        $startDate = date('Y-m-d', mktime(0, 0, 0, date('m'), (date('d') - $count), date('Y')));
        $endDate = date('Y-m-d', mktime(0, 0, 0, date('m'), (date('d') - $count), (date('Y') + 1)));
        $membership .= "( {$randomContacts[$count]}, 2, '{$startDate}', '{$startDate}', '{$endDate}', '{$source}', 1)";
        $activity .= "( {$randomContacts[$count]}, {$acititySourceId}, 7, 'Student', '{$startDate} 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 )";
      }
      else {
        // membership type 1
        $startDate = date('Y-m-d', mktime(0, 0, 0, date('m'), (date('d') - $count), date('Y')));
        $endDate = date('Y-m-d', mktime(0, 0, 0, date('m'), (date('d') - $count), (date('Y') + 2)));
        $membership .= "( {$randomContacts[$count]}, 1, '{$startDate}', '{$startDate}', '{$endDate}', '{$source}', 1)";
        $activity .= "( {$randomContacts[$count]}, {$acititySourceId}, 7, 'General', '{$startDate} 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2 )";
      }

      if ($count != 349) {
        $membership .= ",";
        $activity .= ",";
      }
    }

    CRM_Core_DAO::executeQuery($membership);

    CRM_Core_DAO::executeQuery($activity);
  }

  /**
   * @param $date
   *
   * @return string
   */
  public static function repairDate($date) {
    $dropArray = array('-' => '', ':' => '', ' ' => '');
    return strtr($date, $dropArray);
  }

  public function addMembershipLog() {
    $membership = new CRM_Member_DAO_Membership();
    $membership->query("SELECT id FROM civicrm_membership");
    while ($membership->fetch()) {
      $ids[] = $membership->id;
    }
    require_once 'CRM/Member/DAO/MembershipLog.php';
    foreach ($ids as $id) {
      $membership     = new CRM_Member_DAO_Membership();
      $membership->id = $id;
      $membershipLog  = new CRM_Member_DAO_MembershipLog();
      if ($membership->find(TRUE)) {
        $membershipLog->membership_id = $membership->id;
        $membershipLog->status_id = $membership->status_id;
        $membershipLog->start_date = self::repairDate($membership->start_date);
        $membershipLog->end_date = self::repairDate($membership->end_date);
        $membershipLog->modified_id = $membership->contact_id;
        $membershipLog->modified_date = date("Ymd");
        $membershipLog->save();
      }
      $membershipLog = NULL;
    }
  }

  public function createEvent() {
    $event = "INSERT INTO civicrm_address ( contact_id, location_type_id, is_primary, is_billing, street_address, street_number, street_number_suffix, street_number_predirectional, street_name, street_type, street_number_postdirectional, street_unit, supplemental_address_1, supplemental_address_2, supplemental_address_3, city, county_id, state_province_id, postal_code_suffix, postal_code, usps_adc, country_id, geo_code_1, geo_code_2, timezone)
      VALUES
      ( NULL, 1, 1, 1, 'S 14S El Camino Way E', 14, 'S', NULL, 'El Camino', 'Way', NULL, NULL, NULL, NULL, NULL, 'Collinsville', NULL, 1006, NULL, '6022', NULL, 1228, 41.8328, -72.9253, NULL),
      ( NULL, 1, 1, 1, 'E 11B Woodbridge Path SW', 11, 'B', NULL, 'Woodbridge', 'Path', NULL, NULL, NULL, NULL, NULL, 'Dayton', NULL, 1034, NULL, '45417', NULL, 1228, 39.7531, -84.2471, NULL),
      ( NULL, 1, 1, 1, 'E 581O Lincoln Dr SW', 581, 'O', NULL, 'Lincoln', 'Dr', NULL, NULL, NULL, NULL, NULL, 'Santa Fe', NULL, 1030, NULL, '87594', NULL, 1228, 35.5212, -105.982, NULL)
      ";
    CRM_Core_DAO::executeQuery($event);

    $sql       = "SELECT id from civicrm_address where street_address = 'S 14S El Camino Way E'";
    $eventAdd1 = CRM_Core_DAO::singleValueQuery($sql);
    $sql       = "SELECT id from civicrm_address where street_address = 'E 11B Woodbridge Path SW'";
    $eventAdd2 = CRM_Core_DAO::singleValueQuery($sql);
    $sql       = "SELECT id from civicrm_address where street_address = 'E 581O Lincoln Dr SW'";
    $eventAdd3 = CRM_Core_DAO::singleValueQuery($sql);

    $event = "INSERT INTO civicrm_email (contact_id, location_type_id, email, is_primary, is_billing, on_hold, hold_date, reset_date)
       VALUES
       (NULL, 1, 'development@example.org', 0, 0, 0, NULL, NULL),
       (NULL, 1, 'tournaments@example.org', 0, 0, 0, NULL, NULL),
       (NULL, 1, 'celebration@example.org', 0, 0, 0, NULL, NULL)
       ";
    CRM_Core_DAO::executeQuery($event);

    $sql         = "SELECT id from civicrm_email where email = 'development@example.org'";
    $eventEmail1 = CRM_Core_DAO::singleValueQuery($sql);
    $sql         = "SELECT id from civicrm_email where email = 'tournaments@example.org'";
    $eventEmail2 = CRM_Core_DAO::singleValueQuery($sql);
    $sql         = "SELECT id from civicrm_email where email = 'celebration@example.org'";
    $eventEmail3 = CRM_Core_DAO::singleValueQuery($sql);

    $event = "INSERT INTO civicrm_phone (contact_id, location_type_id, is_primary, is_billing, mobile_provider_id, phone, phone_type_id)
       VALUES
       (NULL, 1, 0, 0, NULL,'204 222-1000', '1'),
       (NULL, 1, 0, 0, NULL,'204 223-1000', '1'),
       (NULL, 1, 0, 0, NULL,'303 323-1000', '1')
       ";
    CRM_Core_DAO::executeQuery($event);

    $sql         = "SELECT id from civicrm_phone where phone = '204 222-1000'";
    $eventPhone1 = CRM_Core_DAO::singleValueQuery($sql);
    $sql         = "SELECT id from civicrm_phone where phone = '204 223-1000'";
    $eventPhone2 = CRM_Core_DAO::singleValueQuery($sql);
    $sql         = "SELECT id from civicrm_phone where phone = '303 323-1000'";
    $eventPhone3 = CRM_Core_DAO::singleValueQuery($sql);

    $event = "INSERT INTO civicrm_loc_block ( address_id, email_id, phone_id, address_2_id, email_2_id, phone_2_id)
       VALUES
      ( $eventAdd1, $eventEmail1, $eventPhone1, NULL,NULL,NULL),
      ( $eventAdd2, $eventEmail2, $eventPhone2, NULL,NULL,NULL),
      ( $eventAdd3, $eventEmail3, $eventPhone3, NULL,NULL,NULL)
       ";

    CRM_Core_DAO::executeQuery($event);

    $sql       = "SELECT id from civicrm_loc_block where phone_id = $eventPhone1 AND email_id = $eventEmail1 AND address_id = $eventAdd1";
    $eventLok1 = CRM_Core_DAO::singleValueQuery($sql);
    $sql       = "SELECT id from civicrm_loc_block where phone_id = $eventPhone2 AND email_id = $eventEmail2 AND address_id = $eventAdd2";
    $eventLok2 = CRM_Core_DAO::singleValueQuery($sql);
    $sql       = "SELECT id from civicrm_loc_block where phone_id = $eventPhone3 AND email_id = $eventEmail3 AND address_id = $eventAdd3";
    $eventLok3 = CRM_Core_DAO::singleValueQuery($sql);

    //create event fees
    $optionGroup = "INSERT INTO civicrm_option_group ( name, is_reserved, is_active)
      VALUES
      ( 'civicrm_event.amount.1', 0, 1),
      ( 'civicrm_event.amount.2', 0, 1),
      ( 'civicrm_event.amount.3', 0, 1)
";
    CRM_Core_DAO::executeQuery($optionGroup);

    $sql = "SELECT max(id) from civicrm_option_group where name = 'civicrm_event.amount.1'";
    $page1 = CRM_Core_DAO::singleValueQuery($sql);

    $sql = "SELECT max(id) from civicrm_option_group where name = 'civicrm_event.amount.2'";
    $page2 = CRM_Core_DAO::singleValueQuery($sql);

    $sql = "SELECT max(id) from civicrm_option_group where name = 'civicrm_event.amount.3'";
    $page3 = CRM_Core_DAO::singleValueQuery($sql);

    $optionValue = "INSERT INTO civicrm_option_value (option_group_id, label, value, is_default, weight, is_optgroup, is_reserved, is_active)
      VALUES
      ($page1, 'Single', '50', 0, 1, 0, 0, 1),
      ($page1, 'Couple', '100', 0, 2, 0, 0, 1),
      ($page1, 'Family', '200', 0, 3, 0, 0, 1),
      ($page2, 'Bass', '25', 0, 1, 0, 0, 1),
      ($page2, 'Tenor', '40', 0, 2, 0, 0, 1),
      ($page2, 'Soprano', '50', 0, 3, 0, 0, 1),
      ($page3, 'Tiny-tots (ages 5-8)', '800', 0, 1, 0, 0, 1),
      ($page3, 'Junior Stars (ages 9-12)', '1000', 0, 2, 0, 0, 1),
      ($page3, 'Super Stars (ages 13-18)', '1500', 0, 3, 0, 0, 1)";

    CRM_Core_DAO::executeQuery($optionValue);

    $sql = "SELECT max(id) FROM civicrm_option_value WHERE civicrm_option_value.option_group_id = $page1 AND civicrm_option_value.weight=2";
    $defaultFee1 = CRM_Core_DAO::singleValueQuery($sql);

    $sql = "SELECT max(id) FROM civicrm_option_value WHERE civicrm_option_value.option_group_id = $page2 AND civicrm_option_value.weight=2";
    $defaultFee2 = CRM_Core_DAO::singleValueQuery($sql);

    $sql = "SELECT max(id) FROM civicrm_option_value WHERE civicrm_option_value.option_group_id = $page3 AND civicrm_option_value.weight=2";
    $defaultFee3 = CRM_Core_DAO::singleValueQuery($sql);

    $event = "INSERT INTO civicrm_event
        ( title, summary, description, event_type_id, participant_listing_id, is_public, start_date, end_date, is_online_registration, registration_link_text, max_participants, event_full_text, is_monetary, financial_type_id, is_map, is_active, fee_label, is_show_location, loc_block_id,intro_text, footer_text, confirm_title, confirm_text, confirm_footer_text, is_email_confirm, confirm_email_text, confirm_from_name, confirm_from_email, cc_confirm, bcc_confirm, default_fee_id, thankyou_title, thankyou_text, thankyou_footer_text, is_pay_later, pay_later_text, pay_later_receipt, is_multiple_registrations, allow_same_participant_emails )
        VALUES
        ( 'Fall Fundraiser Dinner', 'Kick up your heels at our Fall Fundraiser Dinner/Dance at Glen Echo Park! Come by yourself or bring a partner, friend or the entire family!', 'This event benefits our teen programs. Admission includes a full 3 course meal and wine or soft drinks. Grab your dancing shoes, bring the kids and come join the party!', 3, 1, 1, '2010-11-21 17:00:00', '2010-11-21 23:00:00', 1, 'Register Now', 100, 'Sorry! The Fall Fundraiser Dinner is full. Please call Jane at 204 222-1000 ext 33 if you want to be added to the waiting list.', 1, 4, 1, 1, 'Dinner Contribution', 1 ,$eventLok1,'Fill in the information below to join as at this wonderful dinner event.', NULL, 'Confirm Your Registration Information', 'Review the information below carefully.', NULL, 1, 'Contact the Development Department if you need to make any changes to your registration.', 'Fundraising Dept.', 'development@example.org', NULL, NULL, {$defaultFee1}, 'Thanks for Registering!', '<p>Thank you for your support. Your contribution will help us build even better tools.</p><p>Please tell your friends and colleagues about this wonderful event.</p>', '<p><a href=https://civicrm.org>Back to CiviCRM Home Page</a></p>', 1, 'I will send payment by check', 'Send a check payable to Our Organization within 3 business days to hold your reservation. Checks should be sent to: 100 Main St., Suite 3, San Francisco CA 94110', 0, 0 ),
        ( 'Summer Solstice Festival Day Concert', 'Festival Day is coming! Join us and help support your parks.', 'We will gather at noon, learn a song all together,  and then join in a joyous procession to the pavilion. We will be one of many groups performing at this wonderful concert which benefits our city parks.', 5, 1, 1, '2011-06-01 12:00:00', '2011-06-01 17:00:00', 1, 'Register Now', 50, 'We have all the singers we can handle. Come to the pavilion anyway and join in from the audience.', 1, 2, NULL, 1, 'Festival Fee', 1, $eventLok2, 'Complete the form below and click Continue to register online for the festival. Or you can register by calling us at 204 222-1000 ext 22.', '', 'Confirm Your Registration Information', '', '', 1, 'This email confirms your registration. If you have questions or need to change your registration - please do not hesitate to call us.', 'Event Dept.', 'events@example.org', '', NULL, {$defaultFee2}, 'Thanks for Your Joining In!', '<p>Thank you for your support. Your participation will help build new parks.</p><p>Please tell your friends and colleagues about the concert.</p>', '<p><a href=https://civicrm.org>Back to CiviCRM Home Page</a></p>', 0, NULL, NULL, 1, 0 ),
        ( 'Rain-forest Cup Youth Soccer Tournament', 'Sign up your team to participate in this fun tournament which benefits several Rain-forest protection groups in the Amazon basin.', 'This is a FYSA Sanctioned Tournament, which is open to all USSF/FIFA affiliated organizations for boys and girls in age groups: U9-U10 (6v6), U11-U12 (8v8), and U13-U17 (Full Sided).', 3, 1, 1, '2011-12-27 07:00:00', '2011-12-29 17:00:00', 1, 'Register Now', 500, 'Sorry! All available team slots for this tournament have been filled. Contact Jill Futbol for information about the waiting list and next years event.', 1, 4, NULL, 1, 'Tournament Fees',1, $eventLok3, 'Complete the form below to register your team for this year''s tournament.', '<em>A Soccer Youth Event</em>', 'Review and Confirm Your Registration Information', '', '<em>A Soccer Youth Event</em>', 1, 'Contact our Tournament Director for eligibility details.', 'Tournament Director', 'tournament@example.org', '', NULL, {$defaultFee3}, 'Thanks for Your Support!', '<p>Thank you for your support. Your participation will help save thousands of acres of rainforest.</p>', '<p><a href=https://civicrm.org>Back to CiviCRM Home Page</a></p>', 0, NULL, NULL, 0, 0 )
         ";
    CRM_Core_DAO::executeQuery($event);
  }

  public function addParticipant() {
    // add participant
    $participant = new CRM_Event_DAO_Participant();

    for ($id = 1; $id <= self::NUM_PARTICIPANT; $id++) {
      $participant->contact_id = mt_rand(1, self::NUM_CONTACT);
      $participant->event_id = mt_rand(1, 3);
      $participant->status_id = mt_rand(1, 5);
      $participant->role_id = mt_rand(1, 4);
      $participant->register_date = $this->_getRandomDate();
      $participant->source = "Credit Card";

      if ($participant->event_id == 1) {
        $fee_level = "Single";
        $fee_amount = 50;
      }
      elseif ($participant->event_id == 2) {
        $fee_level = "Soprano";
        $fee_amount = 50;
      }
      else {
        $fee_level = "Tiny-tots (ages 5-8)";
        $fee_amount = 800;
      }
      $participant->fee_level = $fee_level;
      $participant->fee_amount = $fee_amount;
      $participant->is_test = 0;

      $this->_insert($participant);
    }
  }

  public function addPCP() {
    $query = "
INSERT INTO `civicrm_pcp`
    (contact_id, status_id, title, intro_text, page_text, donate_link_text, contribution_page_id, is_thermometer, is_honor_roll, goal_amount, referer, is_active)
VALUES
    ({$this->individual[3]}, 2, 'My Personal Civi Fundraiser', 'I''m on a mission to get all my friends and family to help support my favorite open-source civic sector CRM.', '<p>Friends and family - please help build much needed infrastructure for the civic sector by supporting my personal campaign!</p>\r\n<p><a href=\"https://civicrm.org\">You can learn more about CiviCRM here</a>.</p>\r\n<p>Then click the <strong>Contribute Now</strong> button to go to our easy-to-use online contribution form.</p>', 'Contribute Now', 1, 1, 1, 5000.00, NULL, 1);
";
    CRM_Core_DAO::executeQuery($query);
  }

  public function addContribution() {
    // add contributions
    $contribution = new CRM_Contribute_DAO_Contribution();

    for ($id = 1; $id <= self::NUM_CONTRIBUTION; $id++) {
      $contribution->contact_id = mt_rand(1, self::NUM_CONTACT);
      $contribution->financial_type_id   = mt_rand(1, 4);
      $contribution->contribution_page_id = mt_rand(1, 3);
      $contribution->payment_instrument_id = mt_rand(1, 5);
      $contribution->receive_date = $this->_getRandomDate();
      $contribution->total_amount = mt_rand(10, 99);
      $contribution->contribution_status_id = mt_rand(1, 6);
      $contribution->trxn_id = "#" . md5($contribution->receive_date);
      $this->_insert($contribution);
    }
  }

  public function addSoftContribution() {
    $pcpRollNickNAme = array('Jones Family', 'Annie and the kids', 'Anonymous', 'Adam Family');

    $pcpPersonalNote = array('Helping Hands', 'Annie Helps', 'Anonymous', 'Adam Helps');

    $softContribution = new CRM_Contribute_DAO_ContributionSoft();

    $sql = "SELECT DISTINCT(contact_id), id, total_amount from civicrm_contribution LIMIT 200";

    $contriInfo = CRM_Core_DAO::executeQuery($sql);

    $prevContactID = NULL;

    while ($contriInfo->fetch()) {
      if ($prevContactID) {
        $softContribution->contribution_id = $contriInfo->id;
        $softContribution->contact_id = $prevContactID;
        $softContribution->amount = $contriInfo->total_amount;
        $softContribution->pcp_id = 1;
        $softContribution->pcp_display_in_roll = 1;
        $softContribution->pcp_roll_nickname = $this->_getRandomElement($pcpRollNickNAme);
        $softContribution->pcp_personal_note = $this->_getRandomElement($pcpPersonalNote);
        $this->_insert($softContribution);
      }
      $prevContactID = $contriInfo->contact_id;
    }
  }

  public function addPledge() {
    $pledge = "INSERT INTO civicrm_pledge
        (contact_id, financial_type_id, contribution_page_id, amount, frequency_unit, frequency_interval, frequency_day, installments, start_date, create_date, acknowledge_date, modified_date, cancel_date, end_date, honor_contact_id, honor_type_id, status_id, is_test)
        VALUES
       (71, 1, 1, 500.00, 'month', 1, 1, 1, '2010-07-01 21:19:02', '2010-06-26 00:00:00', NULL, NULL, NULL,'2010-07-01 00:00:00', NULL, NULL, 1, 0),
       (43, 1, 1, 800.00, 'month', 3, 1, 4, '2010-07-01 10:11:09', '2010-06-23 10:11:14', '2010-06-23 10:11:18', NULL, NULL, '2010-04-01 10:11:40', NULL, NULL, 5, 0),
       (32, 1, 1, 600.00, 'month', 1, 1, 3, '2010-06-01 10:12:35', '2010-05-14 10:12:44', '2010-05-14 10:12:52', NULL, NULL, '2010-08-01 10:13:11', NULL, NULL, 5, 0);
";
    CRM_Core_DAO::executeQuery($pledge);
  }

  public function addPledgePayment() {
    $pledgePayment = "INSERT INTO civicrm_pledge_payment
        ( pledge_id, contribution_id, scheduled_amount, scheduled_date, reminder_date, reminder_count, status_id)
       VALUES
         (1, 10, 500.00, '2010-07-01 13:03:45', null, 0, 1),
         (2, 11, 200.00, '2010-07-01 10:59:35', null, 0, 1),
         (2, null, 200.00, '2010-10-01 10:59:35',null, 0, 2),
         (2, null, 200.00, '2010-01-01 10:59:35',null, 0, 2),
         (2, null, 200.00, '2010-04-01 10:59:35',null, 0, 2),
         (3, 12, 200.00, '2010-06-01 11:00:12', null, 0, 1),
         (3, 13, 200.00, '2010-07-01 10:59:35', '2010-06-28 10:59:41', 1, 1),
         (3, null, 200.00, '2010-08-01 11:00:12', null, 0, 2);
        ";
    CRM_Core_DAO::executeQuery($pledgePayment);
  }

  public function addMembershipPayment() {
    $amount = array('50', '100', '1200');

    $contribution = new CRM_Contribute_DAO_Contribution();
    for ($id = 1; $id <= 200; $id++) {
      $contribution->contact_id = mt_rand(1, self::NUM_CONTACT);
      $contribution->financial_type_id   = mt_rand(1, 4);
      $contribution->payment_instrument_id = mt_rand(1, 5);
      $contribution->receive_date = $this->_getRandomDate();
      $contribution->total_amount = $this->_getRandomElement($amount);
      $contribution->contribution_status_id = mt_rand(1, 6);
      $contribution->trxn_id = "#" . md5($contribution->receive_date);
      $this->_insert($contribution);
    }
    for ($i = 0; $i < 3; $i++) {
      $contributionsArray = $membershipArray = array();
      $contributionSQL = "
            SELECT  id
                FROM    civicrm_contribution
                WHERE   contribution_page_id IS NULL AND
                        total_amount = {$amount[$i]} limit 0, 50 ";

      $contributionDAO = CRM_Core_DAO::executeQuery($contributionSQL);

      while ($contributionDAO->fetch()) {
        $contributionsArray[] = $contributionDAO->id;
      }
      $j = $i + 1;
      $membershipSQL = "
            SELECT  id
                FROM  civicrm_membership
                WHERE civicrm_membership.membership_type_id = {$j} limit 0, 50";
      $membershipDAO = CRM_Core_DAO::executeQuery($membershipSQL);

      while ($membershipDAO->fetch()) {
        $membershipArray[] = $membershipDAO->id;
      }

      $payemntOBJ = new CRM_Member_DAO_MembershipPayment();
      foreach ($membershipArray as $key => $membershipid) {
        $payemntOBJ->contribution_id = $contributionsArray[$key];
        $payemntOBJ->membership_id = $membershipid;
        $this->_insert($payemntOBJ);
      }
    }
  }

}

/**
 * @param null $str
 *
 * @return bool
 */
function user_access($str = NULL) {
  return TRUE;
}

/**
 * @return array
 */
function module_list() {
  return array();
}

echo ("Starting data generation on " . date("F dS h:i:s A") . "\n");
$obj1 = new CRM_GCD();
$obj1->initID();
$obj1->parseDataFile();
$obj1->initDB();
$obj1->addDomain();
$obj1->addContact();
$obj1->addIndividual();
$obj1->addHousehold();
$obj1->addOrganization();
$obj1->addRelationship();
$obj1->addLocation();
$obj1->addEntityTag();
$obj1->addGroup();
$obj1->addNote();
$obj1->addActivity();
$obj1->addMembership();
$obj1->addMembershipLog();
$obj1->createEvent();
$obj1->addParticipant();
$obj1->addContribution();
$obj1->addPCP();
$obj1->addSoftContribution();
$obj1->addPledge();
$obj1->addPledgePayment();
$obj1->addMembershipPayment();
echo ("Ending data generation on " . date("F dS h:i:s A") . "\n");
