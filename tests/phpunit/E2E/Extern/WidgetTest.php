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
 * Verify Contribution Page Widget endpoint works correctly.
 * @group e2e
 */
class E2E_Extern_WidgetTest extends CiviEndToEndTestCase {

  use \Civi\Test\Api3TestTrait;

  /**
   * @var string
   */
  public $url;

  /**
   * Return widget Javascript.
   */
  public function testWidget() {
    if (CIVICRM_UF !== 'Drupal8') {
      $endpoints['traditional'] = CRM_Core_Resources::singleton()->getUrl('civicrm', 'extern/widget.php');
    }
    $endpoints['normal'] = CRM_Utils_System::url('civicrm/contribute/widget', NULL, TRUE, NULL, FALSE, TRUE);
    foreach ($endpoints as $key => $url) {
      $this->url = $url;
      $contributionPage = $this->contributionPageCreate();
      $widgetParams = [
        'is_active' => 1,
        'title' => $contributionPage['values'][$contributionPage['id']]['title'],
        'contribution_page_id' => $contributionPage['id'],
        'button_title' => 'Contribute!',
        'color_title' => '#2786c2',
        'color_button' => '#ffffff',
        'color_bar' => '#2786c2',
        'color_main_text' => '#ffffff',
        'color_main' => '#96c0e7',
        'color_main_bg' => '#b7e2ff',
        'color_bg' => '#96c0e7',
        'color_about_link' => '#556c82',
        'color_homepage_link' => '#ffffff',
      ];
      $widget = new \CRM_Contribute_DAO_Widget();
      $widget->copyValues($widgetParams);
      $widget->save();
      $widget->find(TRUE);
      $query = ['cpageId' => $contributionPage['id'], 'widgetId' => $widget->id, 'format' => 3];
      $client = CRM_Utils_HttpClient::singleton();
      list($status, $data) = $client->post($this->url, $query);
      $this->assertEquals(CRM_Utils_HttpClient::STATUS_OK, $status);
      $check = substr(trim(substr($data, strpos($data, '{'))), 0, -1);
      $decodedData = json_decode($check, TRUE);
      $expected = [
        'currencySymbol' => '$',
        'is_error' => FALSE,
        'is_active' => TRUE,
        'title' => 'Test Contribution Page',
        'logo' => NULL,
        'button_title' => 'Contribute!',
        'about' => NULL,
        'num_donors' => '0 Donors',
        'money_raised' => 'Raised $ 0.00 of $ 10,000.00',
        'money_raised_amount' => '$ 0.00',
        'campaign_start' => 'Campaign is ongoing',
        'money_target' => 10000,
        'money_raised_percentage' => '0%',
        'money_target_display' => '$ 10,000.00',
        'money_low' => 0,
        'home_url' => '<a href=' . "'" . CRM_Core_Config::singleton()->userFrameworkBaseURL . "'" . ' class=\'crm-home-url\' style=\'color:#ffffff\'>Learn more.</a>',
        'homepage_link' => NULL,
        'colors' => [
          'title' => '#2786c2',
          'button' => '#ffffff',
          'bar' => '#2786c2',
          'main_text' => '#ffffff',
          'main' => '#96c0e7',
          'main_bg' => '#b7e2ff',
          'bg' => '#96c0e7',
          'about_link' => '#556c82',
        ],
      ];
      $this->assertEquals($expected, $decodedData, 'Data not matched for endpoint ' . $key);
    }
  }

  /**
   * Create contribution page.
   *
   * @param array $params
   *
   * @return array
   *   Array of contribution page
   */
  public function contributionPageCreate($params = []) {
    $this->_pageParams = array_merge([
      'title' => 'Test Contribution Page',
      'financial_type_id' => 1,
      'currency' => 'USD',
      'financial_account_id' => 1,
      'is_active' => 1,
      'is_allow_other_amount' => 1,
      'min_amount' => 10,
      'max_amount' => 1000,
      'goal_amount' => '10000',
    ], $params);
    return $this->callAPISuccess('contribution_page', 'create', $this->_pageParams);
  }

}
