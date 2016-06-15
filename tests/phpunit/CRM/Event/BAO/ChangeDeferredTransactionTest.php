<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * Class CRM_Event_BAO_ChangeDeferredTransactionTest
 * @group headless
 */
class CRM_Event_BAO_ChangeDeferredTransactionTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
    $this->_contactId = Contact::createIndividual();
  }

  /**
   * Add() method (add and edit modes of participant)
   */
  public function testChangeStartDate() {
    Civi::settings()->set('contribution_invoice_settings', array('deferred_revenue_enabled' => '1'));

    $this->createParticipantWithContribution(FALSE);

    $params = array(
      'id' => $this->_eventId,
      'start_date' => date('Ymd', strtotime('+2 month')),
    );
    CRM_Event_BAO_Event::create($params);

    // Check the trxns
    $sql = "SELECT ft.*
      FROM civicrm_participant_payment cpp
      INNER JOIN civicrm_participant cp ON cp.id = cpp.participant_id
      INNER JOIN civicrm_entity_financial_trxn eft ON eft.entity_id = cpp.contribution_id AND eft.entity_table = 'civicrm_contribution'
      INNER JOIN civicrm_financial_trxn ft ON ft.id = eft.financial_trxn_id
      INNER JOIN civicrm_entity_financial_account efa ON efa.financial_account_id = ft.from_financial_account_id
      LEFT JOIN civicrm_option_group og ON og.name = 'account_relationship'
      INNER JOIN civicrm_option_value ov ON ov.value = efa.account_relationship AND ov.option_group_id = og.id AND ov.name = 'Deferred Revenue Account is'
      LEFT JOIN civicrm_option_group cog ON cog.name = 'contribution_status'
      INNER JOIN civicrm_option_value cov ON cov.option_group_id = cog.id AND cov.name = 'Cancelled'
      WHERE cp.event_id = %1 AND ft.from_financial_account_id = efa.financial_account_id AND ft.status_id = cov.value";
    $sqlParams = array(
      1 => array($this->_eventId, 'Integer'),
    );
    $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
    $dao->fetch();
    $finAcc = new CRM_Financial_DAO_FinancialAccount();
    $finAcc->name = "Deferred Revenue - Event Fee";
    $finAcc->find(TRUE);
    
    $this->assertEquals($dao->from_financial_account_id, $finAcc->id, 'Financial Account is not of type Deferred');
    $this->assertEquals($dao->status_id, 3, 'Status must be of type Cancelled');

    $sql = "SELECT ft.*
      FROM civicrm_participant_payment cpp
      INNER JOIN civicrm_participant cp ON cp.id = cpp.participant_id
      INNER JOIN civicrm_entity_financial_trxn eft ON eft.entity_id = cpp.contribution_id AND eft.entity_table = 'civicrm_contribution'
      INNER JOIN civicrm_financial_trxn ft ON ft.id = eft.financial_trxn_id
      INNER JOIN civicrm_entity_financial_account efa ON efa.financial_account_id = ft.from_financial_account_id
      LEFT JOIN civicrm_option_group og ON og.name = 'account_relationship'
      INNER JOIN civicrm_option_value ov ON ov.value = efa.account_relationship AND ov.option_group_id = og.id AND ov.name = 'Deferred Revenue Account is'
      LEFT JOIN civicrm_option_group cog ON cog.name = 'contribution_status'
      INNER JOIN civicrm_option_value cov ON cov.option_group_id = cog.id AND cov.name = 'Completed'
      WHERE cp.event_id = %1 AND ft.from_financial_account_id = efa.financial_account_id AND ft.status_id = cov.value
      ORDER BY ft.id DESC LIMIT 1";
    $sqlParams = array(
      1 => array($this->_eventId, 'Integer'),
    );
    $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
    $dao->fetch();
    
    $this->assertEquals($dao->from_financial_account_id, $finAcc->id, 'Financial Account is not of type Deferred');
    $this->assertEquals($dao->status_id, 1, 'Status must be of type Completed');
    $this->assertEquals(date('Ymd', strtotime($dao->trxn_date)), date('Ymd', strtotime('+2 month')), 'Trxn dates do not match');
  }

}
