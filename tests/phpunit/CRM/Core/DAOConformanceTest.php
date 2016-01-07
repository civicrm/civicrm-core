<?php

require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * Class for testing new DAO meet required standards.
 *
 * Class CRM_Core_DAOTest
 */
class CRM_Core_DAOConformanceTest extends CiviUnitTestCase {

  /**
   * Check all fields have defined titles.
   *
   * @dataProvider getAllDAO
   */
  public function testFieldsHaveTitles($class) {
    $dao = new $class();
    $fields = $dao->fields();
    foreach ($fields as $name => $field) {
      $this->assertArrayHasKey('title', $field, "A title must be defined for $name in $class");
    }
  }

  /**
   * Get all DAO classes.
   */
  public function getAllDAO() {
    $classList = CRM_Core_DAO_AllCoreTables::getClasses();
    $return = array();
    $notYetTitledDAO = $this->getClassesWithoutTitlesYet();
    foreach ($classList as $class) {
      if (!in_array($class, $notYetTitledDAO)) {
        $return[] = array($class);
      }
    }
    return $return;
  }

  /**
   * Classes that do not yet conform to expectation they will have a title for each field.
   *
   * When we start enforcing a new standard we have to grandfather it in & these classes need titles added.
   *
   * Note that we want titles so that things like views integration can rely on using them and so the person
   * introducing the DAO is responsible for it's titles - not the person who adds it to the api later.
   */
  public function getClassesWithoutTitlesYet() {
    return array(
      'CRM_Contact_DAO_ACLContactCache',
      'CRM_Core_DAO_Managed',
      'CRM_Core_DAO_PreferencesDate',
      'CRM_Event_Cart_DAO_EventInCart',
      'CRM_PCP_DAO_PCPBlock',
      'CRM_Case_DAO_CaseActivity',
      'CRM_Core_DAO_Discount',
      'CRM_Price_DAO_PriceSetEntity',
      'CRM_Case_DAO_CaseContact',
      'CRM_Contribute_DAO_Widget',
      'CRM_Contribute_DAO_PremiumsProduct',
      'CRM_Core_DAO_Persistent',
      'CRM_Mailing_Event_DAO_TrackableURLOpen',
      'CRM_Mailing_Event_DAO_Reply',
      'CRM_Mailing_Event_DAO_Delivered',
      'CRM_Mailing_Event_DAO_Forward',
      'CRM_Mailing_Event_DAO_Bounce',
      'CRM_Mailing_Event_DAO_Opened',
      'CRM_Mailing_DAO_Spool',
      'CRM_Mailing_DAO_TrackableURL',
      'CRM_Contact_DAO_GroupContactCache',
      'CRM_Contact_DAO_SubscriptionHistory',
      'CRM_Core_DAO_Menu',
      'CRM_Core_DAO_Log',
      'CRM_Core_DAO_EntityFile',
      'CRM_PCP_DAO_PCP',
      'CRM_Queue_DAO_QueueItem',
      'CRM_Pledge_DAO_PledgeBlock',
      'CRM_Friend_DAO_Friend',
      'CRM_Dedupe_DAO_Exception',
      'CRM_Dedupe_DAO_Rule',
      'CRM_Dedupe_DAO_RuleGroup',
      'CRM_Event_Cart_DAO_Cart',
      'CRM_Campaign_DAO_CampaignGroup',
      'CRM_Financial_DAO_EntityFinancialAccount',
      'CRM_Financial_DAO_Currency',
      'CRM_Mailing_DAO_BouncePattern',
      'CRM_Mailing_DAO_BounceType',
    );
  }

}
