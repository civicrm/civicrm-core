<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
