<?php
namespace Civi\Afform;

use Civi\Test\HeadlessInterface;

/**
 * @group headless
 */
class DuplicateContactCheckTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface {

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->install(['org.civicrm.search_kit', 'org.civicrm.afform'])
      ->apply();
  }

  public function tearDown(): void {
    $this->setPermissions(NULL);
    \Civi::settings()->revert('contact_ajax_check_similar');
    parent::tearDown();
  }

  public function testSettingsWhenEnabled(): void {
    $this->setPermissions(['access CiviCRM']);
    \Civi::settings()->set('contact_ajax_check_similar', 1);

    $settings = DuplicateContactCheck::getSettings();

    $this->assertTrue($settings['checkSimilarContacts']);
    $this->assertSame(DuplicateContactCheck::MIN_LENGTH, $settings['checkSimilarMinLength']);
    $this->assertContains('first_name', $settings['checkSimilarContactFields']);
    $this->assertContains('organization_name', $settings['checkSimilarContactFields']);
    // An afform entity may be declared as `Contact` or as a specific contact type.
    $this->assertContains('Contact', $settings['contactEntityTypes']);
    $this->assertContains('Individual', $settings['contactEntityTypes']);
  }

  public function testSettingsWhenSettingDisabled(): void {
    $this->setPermissions(['access CiviCRM']);
    \Civi::settings()->set('contact_ajax_check_similar', 0);

    $this->assertSame(['checkSimilarContacts' => FALSE], DuplicateContactCheck::getSettings());
  }

  public function testSettingsWithoutPermission(): void {
    $this->setPermissions([]);
    \Civi::settings()->set('contact_ajax_check_similar', 1);

    $this->assertSame(['checkSimilarContacts' => FALSE], DuplicateContactCheck::getSettings());
  }

  private function setPermissions(?array $permissions): void {
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = $permissions;
  }

}
