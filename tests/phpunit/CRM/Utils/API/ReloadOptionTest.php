<?php

/**
 * Test that the API accepts the 'reload' option.
 *
 * To do this, each of our test cases will perform a 'create' call and use hook_civicrm_post
 * to munge the database. If the reload option is present, then the return value should reflect
 * the final SQL content (after calling hook_civicrm_post). If the reload option is missing,
 * then the return should reflect the inputted (unmodified) data.
 * @group headless
 */
class CRM_Utils_API_ReloadOptionTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
    CRM_Utils_Hook_UnitTests::singleton()->setHook('civicrm_post', array($this, 'onPost'));
  }

  /**
   * If reload option is missing, then 'create' returns the inputted nick_name -- despite the
   * fact that the hook manipulated the actual DB content.
   */
  public function testNoReload() {
    $result = $this->callAPISuccess('contact', 'create', array(
      'contact_type' => 'Individual',
      'first_name' => 'First',
      'last_name' => 'Last',
      'nick_name' => 'Firstie',
    ));
    $this->assertEquals('First', $result['values'][$result['id']]['first_name']);
    // munged by hook, but we haven't realized it
    $this->assertEquals('Firstie', $result['values'][$result['id']]['nick_name']);
  }

  /**
   * When the reload option is unrecognized, generate an error
   */
  public function testReloadInvalid() {
    $this->callAPIFailure('contact', 'create', array(
      'contact_type' => 'Individual',
      'first_name' => 'First',
      'last_name' => 'Last',
      'nick_name' => 'Firstie',
      'options' => array(
        'reload' => 'invalid',
      ),
    ));
  }

  /**
   * If reload option is set, then 'create' returns the final nick_name -- even if it
   * differs from the inputted nick_name.
   */
  public function testReloadDefault() {
    $result = $this->callAPISuccess('contact', 'create', array(
      'contact_type' => 'Individual',
      'first_name' => 'First',
      'last_name' => 'Last',
      'nick_name' => 'Firstie',
      'options' => array(
        'reload' => 1,
      ),
    ));
    $this->assertEquals('First', $result['values'][$result['id']]['first_name']);
    $this->assertEquals('munged', $result['values'][$result['id']]['nick_name']);
  }

  /**
   * When the reload option is combined with chaining, the reload should munge
   * the chain results.
   */
  public function testReloadNoChainInterference() {
    $result = $this->callAPISuccess('contact', 'create', array(
      'contact_type' => 'Individual',
      'first_name' => 'First',
      'last_name' => 'Last',
      'nick_name' => 'Firstie',
      'api.Email.create' => array(
        'email' => 'test@example.com',
      ),
      'options' => array(
        'reload' => 1,
      ),
    ));
    $this->assertEquals('First', $result['values'][$result['id']]['first_name']);
    $this->assertEquals('munged', $result['values'][$result['id']]['nick_name']);
    $this->assertAPISuccess($result['values'][$result['id']]['api.Email.create']);
  }

  /**
   * When the reload option is combined with chaining, the reload should munge
   * the chain results, even if sequential=1.
   */
  public function testReloadNoChainInterferenceSequential() {
    $result = $this->callAPISuccess('contact', 'create', array(
      'sequential' => 1,
      'contact_type' => 'Individual',
      'first_name' => 'First',
      'last_name' => 'Last',
      'nick_name' => 'Firstie',
      'api.Email.create' => array(
        'email' => 'test@example.com',
      ),
      'options' => array(
        'reload' => 1,
      ),
    ));
    $this->assertEquals('First', $result['values'][0]['first_name']);
    $this->assertEquals('munged', $result['values'][0]['nick_name']);
    $this->assertAPISuccess($result['values'][0]['api.Email.create']);
  }

}
