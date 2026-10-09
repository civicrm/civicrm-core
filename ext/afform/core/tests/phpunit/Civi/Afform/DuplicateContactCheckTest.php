<?php
namespace Civi\Afform;

use Civi\Afform\Behavior\ContactDupeCheck;
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
    parent::tearDown();
  }

  public function testSettingsWhenPermitted(): void {
    $this->setPermissions(['access CiviCRM']);

    $settings = DuplicateContactCheck::getSettings();

    $this->assertTrue($settings['checkSimilarContacts']);
    $this->assertSame(DuplicateContactCheck::MIN_LENGTH, $settings['checkSimilarMinLength']);
    // Fields come from the dedupe rules, so a form can watch whichever rule it selects.
    $this->assertSame(['email'], $settings['dedupeRuleFields']['Individual.Unsupervised']);
    $this->assertEqualsCanonicalizing(
      ['first_name', 'last_name', 'email'],
      $settings['dedupeRuleFields']['Individual.Supervised']
    );
  }

  public function testSettingsWithoutPermission(): void {
    $this->setPermissions([]);

    $this->assertSame(['checkSimilarContacts' => FALSE], DuplicateContactCheck::getSettings());
  }

  public function testBehaviorOffersARuleForEachContactType(): void {
    $this->assertEqualsCanonicalizing(
      ['Individual', 'Household', 'Organization'],
      ContactDupeCheck::getEntities()
    );
    $modes = array_column(ContactDupeCheck::getModes('Individual'), 'name');
    $this->assertContains('Individual.Supervised', $modes);
    $this->assertContains('Individual.Unsupervised', $modes);
  }

  private function setPermissions(?array $permissions): void {
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = $permissions;
  }

}
