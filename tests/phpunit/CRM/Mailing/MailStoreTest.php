<?php

/**
 * @group headless
 */
class CRM_Mailing_MailStoreTest extends \CiviUnitTestCase {

  protected $workDir;

  public function setUp(): void {
    $this->useTransaction(TRUE);
    parent::setUp();
    $this->workDir = tempnam(sys_get_temp_dir(), 'mailstoretest');
    @unlink($this->workDir);
  }

  public function tearDown(): void {
    parent::tearDown();
    if (is_dir($this->workDir)) {
      CRM_Utils_File::cleanDir($this->workDir);
    }
  }

  /**
   * Create an example store (maildir) using default behaviors (no hooks).
   */
  public function testMaildirBasic() {
    $this->createMaildirSettings([
      'name' => __FUNCTION__,
    ]);
    $store = CRM_Mailing_MailStore::getStore(__FUNCTION__);
    $this->assertTrue($store instanceof CRM_Mailing_MailStore_Maildir);
  }

  /**
   * Create an example store (maildir) and change the driver via hook.
   */
  public function testMaildirHook() {
    // This hook swaps out the implementation used for 'Maildir' stores.
    Civi::dispatcher()
      ->addListener('hook_civicrm_alterMailStore', function ($e) {
        if ($e->params['protocol'] === 'Maildir') {
          $e->params['factory'] = function ($mailSettings) {
            $this->assertEquals('testMaildirHook', $mailSettings['name']);
            // Make a fake object that technically meets the contract of 'MailStore'
            return new class extends CRM_Mailing_MailStore {

              public function frobnicate() {
                return 'totally';
              }

            };
          };
        }
      });

    $this->createMaildirSettings([
      'name' => __FUNCTION__,
    ]);
    $store = CRM_Mailing_MailStore::getStore(__FUNCTION__);

    // The hook gave us an unusual instance of MailStore.
    $this->assertTrue($store instanceof CRM_Mailing_MailStore);
    $this->assertFalse($store instanceof CRM_Mailing_MailStore_Maildir);
    $this->assertEquals('totally', $store->frobnicate());
  }

  /**
   * Create a "MailSettings" record for maildir store.
   * @param array $values
   *   Some values to set
   * @return array
   */
  private function createMaildirSettings($values = []):array {
    mkdir($this->workDir);
    $defaults = [
      'protocol:name' => 'Maildir',
      'name' => NULL,
      'source' => $this->workDir,
      'domain' => 'maildir.example.com',
      'username' => 'pass-my-name',
      'password' => 'pass-my-pass',
    ];
    $mailSettings = \Civi\Api4\MailSettings::create(0)
      ->setValues(array_merge($defaults, $values))
      ->execute()
      ->single();
    return $mailSettings;
  }

}
