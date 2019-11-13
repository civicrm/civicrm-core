<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * Trait CRMTraits_PCP_PCPTestTrait
 *
 * Traits for testing PCP pages.
 */
trait CRMTraits_PCP_PCPTestTrait {

  /**
   * Build and return pcpBlock params.
   *
   * Create the necessary initial objects for a pcpBlock, then return the
   * params needed to create the pcpBlock.
   *
   */
  public function pcpBlockParams() {
    $contribPage = CRM_Core_DAO::createTestObject('CRM_Contribute_DAO_ContributionPage');
    $contribPageId = $contribPage->id;
    $supporterProfile = CRM_Core_DAO::createTestObject('CRM_Core_DAO_UFGroup');
    $supporterProfileId = $supporterProfile->id;

    $params = array(
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => $contribPageId,
      'supporter_profile_id' => $supporterProfileId,
      'target_entity_id' => 1,
      'is_approval_needed' => 1,
      'is_tellfriend_enabled' => 1,
      'tellfriend_limit' => 1,
      'link_text' => 'Create your own PCP',
      'is_active' => 1,
    );

    return $params;
  }

  /**
   * Build and return pcp params.
   *
   * Create the necessary initial objects for a pcp page, then return the
   * params needed to create the pcp page.
   */
  public function pcpParams() {
    $contact = CRM_Core_DAO::createTestObject('CRM_Contact_DAO_Contact');
    $contactId = $contact->id;
    $contribPage = CRM_Core_DAO::createTestObject('CRM_Contribute_DAO_ContributionPage');
    $contribPageId = $contribPage->id;

    $params = array(
      'contact_id' => $contactId,
      'status_id' => '1',
      'title' => 'My PCP',
      'intro_text' => 'Hey you, contribute now!',
      'page_text' => 'You better give more.',
      'donate_link_text' => 'Donate Now',
      'page_id' => $contribPageId,
      'is_thermometer' => 1,
      'is_honor_roll' => 1,
      'goal_amount' => 10000.00,
      'is_active' => 1,
    );

    return $params;
  }

}
