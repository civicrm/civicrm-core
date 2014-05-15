<?php
class ContributionPage extends PHPUnit_Framework_Testcase {
  /**
   * Helper function to create
   * a Contribution Page
   *
   * @param null $id
   *
   * @return mixed $contributionPage id of created Contribution Page
   */
  static function create($id = NULL) {
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
      'thankyou_title' => 'Thanks for Your Support!',
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
   * Helper function to delete a Contribution Page
   *
   * @param  int $contributionPageId - id of the Contribution Page
   * to be deleted
   * @return boolean true if Contribution Page deleted, false otherwise
   */
  static function delete($contributionPageId) {
    require_once "CRM/Contribute/DAO/ContributionPage.php";
    $cp = new CRM_Contribute_DAO_ContributionPage();
    $cp->id = $contributionPageId;
    if ($cp->find(TRUE)) {
      $result = $cp->delete();
    }
    return $result;
  }
}
