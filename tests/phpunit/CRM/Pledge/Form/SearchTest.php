<?php

/**
 *  Include dataProvider for tests
 * @group headless
 */
class CRM_Pledge_Form_SearchTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
    $this->individualID = $this->individualCreate();
    $this->pledgeCreate(array('contact_id' => $this->individualID));
  }

  public function tearDown() {
    parent::tearDown();
    $tablesToTruncate = array(
      'civicrm_activity',
      'civicrm_activity_contact',
      'civicrm_pledge',
    );
    $this->quickCleanup($tablesToTruncate);
  }

  /**
   *  Test submitted the search form.
   */
  public function testSearch() {
    $form = new CRM_Pledge_Form_Search();
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $form->controller = new CRM_Pledge_Controller_Search();
    $form->preProcess();
    $form->postProcess();
    $qfKey = $form->controller->_key;
    $date = date('Y-m-d') . ' 00:00:00';
    $rows = $form->controller->get('rows');
    $this->assertEquals(array(
      'contact_id' => '3',
      'sort_name' => 'Anderson, Anthony',
      'display_name' => 'Mr. Anthony Anderson II',
      'pledge_id' => '1',
      'pledge_amount' => '100.00',
      'pledge_create_date' => $date,
      'pledge_next_pay_date' => $date,
      'pledge_next_pay_amount' => '20.00',
      'pledge_status_id' => '2',
      'pledge_status' => 'Pending',
      'pledge_is_test' => '0',
      'pledge_financial_type' => 'Donation',
      'pledge_currency' => 'USD',
      'campaign' => NULL,
      'campaign_id' => NULL,
      'pledge_status_name' => 'Pending',
      'checkbox' => 'mark_x_1',
      'action' => '<span><a href="/index.php?q=civicrm/contact/view/pledge&amp;reset=1&amp;id=1&amp;cid=3&amp;action=view&amp;context=search&amp;selectedChild=pledge&amp;key=' . $qfKey . '" class="action-item crm-hover-button" title=' . "'" . 'View Pledge' . "'" . ' >View</a><a href="/index.php?q=civicrm/contact/view/pledge&amp;reset=1&amp;action=update&amp;id=1&amp;cid=3&amp;context=search&amp;key=' . $qfKey . '" class="action-item crm-hover-button" title=' . "'" . 'Edit Pledge' . "'" . ' >Edit</a></span><span class=' . "'" . 'btn-slide crm-hover-button' . "'" . '>more<ul class=' . "'" . 'panel' . "'" . '><li><a href="/index.php?q=civicrm/contact/view/pledge&amp;reset=1&amp;action=detach&amp;id=1&amp;cid=3&amp;context=search&amp;key=' . $qfKey . '" class="action-item crm-hover-button" title=' . "'" . 'Cancel Pledge' . "'" . ' onclick = "return confirm(' . "'" . 'Cancelling this pledge will also cancel any scheduled (and not completed) pledge payments. This action cannot be undone. Do you want to continue?' . "'" . ');">Cancel</a></li><li><a href="/index.php?q=civicrm/contact/view/pledge&amp;reset=1&amp;action=delete&amp;id=1&amp;cid=3&amp;context=search&amp;key=' . $qfKey . '" class="action-item crm-hover-button small-popup" title=' . "'" . 'Delete Pledge' . "'" . ' >Delete</a></li></ul></span>',
      'contact_type' => '<a href="/index.php?q=civicrm/profile/view&amp;reset=1&amp;gid=7&amp;id=3&amp;snippet=4" class="crm-summary-link"><div class="icon crm-icon Individual-icon"></div></a>',
    ), $rows[0]);
  }

}
