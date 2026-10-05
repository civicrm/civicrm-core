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
 * Test CRM_Event_Page_Tab.
 *
 * @package CiviCRM
 * @group headless
 */
class CRM_Event_Page_TabTest extends CiviUnitTestCase {

  /**
   * Request parameters as they were before the test started.
   *
   * @var array
   */
  private array $originalRequest;

  public function setUp(): void {
    parent::setUp();
    $this->useTransaction();
    $this->originalRequest = [$_REQUEST, $_GET];
  }

  public function tearDown(): void {
    [$_REQUEST, $_GET] = $this->originalRequest;
    CRM_Core_Session::singleton()->resetScope('CRM_Event_Page_Tab');
    parent::tearDown();
  }

  /**
   * Test the action the participant route resolves to for various url parameters.
   *
   * @dataProvider urlParameterProvider
   *
   * @throws \CRM_Core_Exception
   */
  public function testPreProcessResolvesAction(array $urlParameters, int $expectedAction): void {
    $urlParameters['cid'] = $this->individualCreate();
    $_REQUEST = $_GET = $urlParameters + ['q' => 'civicrm/contact/view/participant'];
    // retrieve() writes the action into the session & reads it back before
    // falling back to the default, so a leftover value would mask the url.
    CRM_Core_Session::singleton()->resetScope('CRM_Event_Page_Tab');

    $page = new CRM_Event_Page_Tab();
    $page->preProcess();
    $this->assertEquals($expectedAction, $page->getTemplateVars('action'));
  }

  public static function urlParameterProvider(): array {
    return [
      // Fee block ajax on the 'Register participants' search task.
      'search_task_fee_block' => [
        'url_parameters' => ['context' => 'search', 'action' => 'add', 'snippet' => 4],
        'expected_action' => CRM_Core_Action::ADD,
      ],
      // Pager on the participant listing - dev/core#5758. This must not flip to
      // 'add' or the listing is replaced by an empty registration form.
      'search_listing' => [
        'url_parameters' => ['context' => 'search'],
        'expected_action' => CRM_Core_Action::BROWSE,
      ],
      'search_edit' => [
        'url_parameters' => ['context' => 'search', 'action' => 'update'],
        'expected_action' => CRM_Core_Action::UPDATE,
      ],
      'search_view' => [
        'url_parameters' => ['context' => 'search', 'action' => 'view'],
        'expected_action' => CRM_Core_Action::VIEW,
      ],
      'standalone_add' => [
        'url_parameters' => ['context' => 'standalone', 'action' => 'add'],
        'expected_action' => CRM_Core_Action::ADD,
      ],
    ];
  }

}
