<?php

/**
 * Class ContributionPage
 */
class ContributionPage extends PHPUnit_Framework_Testcase {
  /**
   * Helper function to create.
   * a Contribution Page
   *
   * @param int $id
   *
   * @return int
   *   id of created Contribution Page
   */
  public static function create($id = NULL) {
    require_once "CRM/Contribute/BAO/ContributionPage.php";
    $params = array(
      'title' => 'Help Test CiviCRM!',
      'intro_text' => 'Created for Test Coverage Online Contribution Page',
      'financial_type_id' => 1,
      'payment_processor_id' => $id,
      'is_monetary' => 1,
      'is_allow_other_amount' => 1,
      'min_amount' => 10,
      'max_amount' => 10000,
      'goal_amount' => 100000,
      'thankyou_title' => 'Thank you for your support!',
      'thankyou_text' => 'Thank you for your support.',
      'is_email_receipt' => 1,
      'receipt_from_name' => 'From TEST',
      'receipt_from_email' => 'donations@civicrm.org',
      'cc_receipt' => 'receipt@example.com',
      'bcc_receipt' => 'bcc@example.com',
      'is_active' => 1,
    );

    $contributionPage = CRM_Contribute_BAO_ContributionPage::create($params);
    return $contributionPage->id;
  }

  /**
   * Helper function to delete a Contribution Page.
   *
   * @param int $contributionPageId
   *   Id of the Contribution Page.
   *   to be deleted
   * @return bool
   *   true if Contribution Page deleted, false otherwise
   */
  public static function delete($contributionPageId) {
    require_once "CRM/Contribute/DAO/ContributionPage.php";
    $cp = new CRM_Contribute_DAO_ContributionPage();
    $cp->id = $contributionPageId;
    if ($cp->find(TRUE)) {
      $result = $cp->delete();
    }
    return $result;
  }

}
