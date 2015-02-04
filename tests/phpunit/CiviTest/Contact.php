<?php

/**
 * Class Contact
 */
class Contact extends CiviUnitTestCase {
  /**
   * Helper function to create.
   * a contact
   *
   * @param array $params
   *
   * @return int
   *   $contactID id of created contact
   */
  public static function create($params) {
    require_once "CRM/Contact/BAO/Contact.php";
    $contactID = CRM_Contact_BAO_Contact::createProfileContact($params, CRM_Core_DAO::$_nullArray);
    return $contactID;
  }

  /**
   * Helper function to create
   * a contact of type Individual
   *
   * @param array $params
   * @return int
   *   $contactID id of created Individual
   */
  public static function createIndividual($params = NULL) {
    //compose the params, when not passed
    if (!$params) {
      $first_name = 'John';
      $last_name = 'Doe';
      $contact_source = 'Testing purpose';
      $params = array(
        'first_name' => $first_name,
        'last_name' => $last_name,
        'contact_source' => $contact_source,
      );
    }
    return self::create($params);
  }

  /**
   * Helper function to create
   * a contact of type Household
   *
   * @param array $params
   * @return int
   *   id of created Household
   */
  public static function createHousehold($params = NULL) {
    //compose the params, when not passed
    if (!$params) {
      $household_name = "John Doe's home";
      $params = array(
        'household_name' => $household_name,
        'contact_type' => 'Household',
      );
    }
    require_once "CRM/Contact/BAO/Contact.php";
    $household = CRM_Contact_BAO_Contact::create($params);
    return $household->id;
  }

  /**
   * Helper function to create
   * a contact of type Organisation
   *
   * @param array $params
   * @return int
   *   id of created Organisation
   */
  public static function createOrganisation($params = NULL) {
    //compose the params, when not passed
    if (!$params) {
      $organization_name = "My Organization";
      $params = array(
        'organization_name' => $organization_name,
        'contact_type' => 'Organization',
      );
    }
    require_once "CRM/Contact/BAO/Contact.php";
    $organization = CRM_Contact_BAO_Contact::create($params);
    return $organization->id;
  }

  /**
   * Helper function to delete a contact.
   *
   * @param int $contactID
   *   Id of the contact to delete.
   * @return bool
   *   true if contact deleted, false otherwise
   */
  public static function delete($contactID) {
    require_once 'CRM/Contact/BAO/Contact.php';
    return CRM_Contact_BAO_Contact::deleteContact($contactID);
  }

}
