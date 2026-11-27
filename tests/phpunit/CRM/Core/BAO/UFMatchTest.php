<?php

use Civi\Api4\Email;

/**
 * Class CRM_Core_BAO_UFMatchTest
 * @group headless
 */
class CRM_Core_BAO_UFMatchTest extends CiviUnitTestCase {

  public function tearDown(): void {
    CRM_Core_BAO_SchemaHandler::dropIndexIfExists('civicrm_uf_match', 'UI_uf_match_uf_id_domain_id');
    CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_uf_match ADD UNIQUE INDEX UI_uf_match_uf_id_domain_id (uf_id,domain_id)');
    parent::tearDown();
  }

  /**
   * A fresh install declares the same unique index an upgraded site gets.
   */
  public function testUfIdIsUniquePerDomainOnInstall(): void {
    $generator = require \Civi::paths()->getPath('[civicrm.root]/mixin/lib/civimix-schema@5/src/SqlGenerator.php');
    $installer = new (get_class($generator))(
      ['UFMatch' => \Civi\Schema\EntityRepository::getEntity('UFMatch')],
      fn(string $entityName) => \Civi::entity($entityName)->getMeta('table'),
    );
    $this->assertStringContainsString('UNIQUE INDEX `UI_uf_match_uf_id_domain_id`(`uf_id`, `domain_id`)', $installer->getCreateTableSql('UFMatch'));
  }

  /**
   * The status check replaces a non-unique index of the same name.
   */
  public function testNonUniqueIndexIsMadeUnique(): void {
    CRM_Core_BAO_SchemaHandler::dropIndexIfExists('civicrm_uf_match', 'UI_uf_match_uf_id_domain_id');
    CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_uf_match ADD INDEX UI_uf_match_uf_id_domain_id (uf_id,domain_id)');

    $this->assertSame([], CRM_Utils_Check_Component_Cms::checkUfMatchUnique());
    $indexes = CRM_Core_BAO_SchemaHandler::getIndexes(['civicrm_uf_match'])['civicrm_uf_match'];
    $this->assertSame(1, $indexes['UI_uf_match_uf_id_domain_id']['unique']);
  }

  /**
   * The status check adds the unique index when none exists.
   */
  public function testMissingIndexIsAddedAsUnique(): void {
    CRM_Core_BAO_SchemaHandler::dropIndexIfExists('civicrm_uf_match', 'UI_uf_match_uf_id_domain_id');

    $this->assertSame([], CRM_Utils_Check_Component_Cms::checkUfMatchUnique());
    $indexes = CRM_Core_BAO_SchemaHandler::getIndexes(['civicrm_uf_match'])['civicrm_uf_match'];
    $this->assertSame(1, $indexes['UI_uf_match_uf_id_domain_id']['unique']);
  }

  /**
   * Don't crash if the uf_id doesn't exist
   */
  public function testGetUFValuesWithNonexistentUFId(): void {
    $max_id = (int) CRM_Core_DAO::singleValueQuery('SELECT MAX(uf_id) FROM civicrm_uf_match');
    $dontcrash = CRM_Core_BAO_UFMatch::getUFValues($max_id + 1);
    $this->assertNull($dontcrash);
  }

  /**
   * A user whose email another user already holds as uf_name gets one contact.
   *
   * uf_name is unique per domain, so before dev/core#6931 the match was never
   * saved and every sync created another contact. Now the user is linked to
   * its own contact without a uf_name, and the other user's contact is never
   * shared.
   *
   * @dataProvider getSyncPaths
   */
  public function testSynchronizeUFMatchLinksOneContactWhenEmailIsTaken(bool $isLogin): void {
    $email = 'taken@example.org';
    $contactID = $this->individualCreate(['email' => $email]);
    $this->createUFMatch(100, $email, $contactID);

    for ($i = 0; $i < 3; $i++) {
      $match = $this->synchronizeUser(200, $email, $isLogin);
    }

    $ownContactID = $this->getLinkedContactID(200);
    $this->assertNotNull($ownContactID);
    $this->assertNotEquals($contactID, $ownContactID);
    $this->assertEquals($ownContactID, $match->contact_id);
    $this->assertSame([$contactID, $ownContactID], $this->getContactIDsWithEmail($email));
    $this->assertNull(CRM_Core_DAO::singleValueQuery('SELECT uf_name FROM civicrm_uf_match WHERE uf_id = 200'));
  }

  /**
   * The contact with the email is unlinked, but another user still holds the
   * email as uf_name (it changed its email since). Link to that contact.
   */
  public function testSynchronizeUFMatchLinksUnlinkedContactWhenUFNameIsStale(): void {
    $email = 'stale@example.org';
    $unlinkedID = $this->individualCreate(['email' => $email]);
    $otherID = $this->individualCreate(['email' => 'changed@example.org'], 1);
    $this->createUFMatch(100, $email, $otherID);

    $this->synchronizeUser(200, $email, TRUE);

    $this->assertEquals($unlinkedID, $this->getLinkedContactID(200));
    $this->assertSame([$unlinkedID], $this->getContactIDsWithEmail($email));
  }

  public function testSynchronizeUFMatchLinksUnlinkedContactWithSameEmail(): void {
    $email = 'unlinked@example.org';
    $contactID = $this->individualCreate(['email' => $email]);

    $this->synchronizeUser(200, $email, TRUE);

    $this->assertEquals($contactID, $this->getLinkedContactID(200));
    $this->assertSame([$contactID], $this->getContactIDsWithEmail($email));
  }

  public function testSynchronizeUFMatchCreatesLinkedContactForNewEmail(): void {
    $email = 'new@example.org';

    $this->synchronizeUser(200, $email, TRUE);

    $contactIDs = $this->getContactIDsWithEmail($email);
    $this->assertCount(1, $contactIDs);
    $this->assertEquals($contactIDs[0], $this->getLinkedContactID(200));
  }

  /**
   * Adding a user in the CMS saves the match, then copies the user's email to
   * uf_name. Another user already holds it, so uf_name stays empty.
   */
  public function testUpdateContactEmailKeepsUFNameHeldByAnotherUser(): void {
    $email = 'taken@example.org';
    $this->createUFMatch(100, $email, $this->individualCreate(['email' => $email]));
    $this->synchronizeUser(200, $email, FALSE);
    $contactID = $this->getLinkedContactID(200);

    CRM_Core_BAO_UFMatch::updateContactEmail($contactID, $email);

    $this->assertEquals($contactID, $this->getLinkedContactID(200));
    $this->assertNull(CRM_Core_DAO::singleValueQuery('SELECT uf_name FROM civicrm_uf_match WHERE uf_id = 200'));
    $this->assertEquals($email, CRM_Core_DAO::singleValueQuery('SELECT uf_name FROM civicrm_uf_match WHERE uf_id = 100'));
  }

  /**
   * Without a contact ID the lookup has no contact filter, so it must not
   * give the email to any match or contact.
   */
  public function testUpdateContactEmailWithoutContactChangesNothing(): void {
    $email = 'nobody@example.org';
    $this->createUFMatch(100, 'changed@example.org', $this->individualCreate(['email' => 'changed@example.org']));

    CRM_Core_BAO_UFMatch::updateContactEmail(NULL, $email);

    $this->assertNull(CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_uf_match WHERE uf_name = %1', [1 => [$email, 'String']]));
    $this->assertSame([], $this->getContactIDsWithEmail($email));
  }

  /**
   * A login takes the email-only match. A form submission takes the dedupe
   * rule.
   */
  public static function getSyncPaths(): array {
    return [
      'login' => [TRUE],
      'form submission' => [FALSE],
    ];
  }

  private function createUFMatch(int $ufID, string $ufName, int $contactID): void {
    CRM_Core_BAO_UFMatch::create([
      'domain_id' => CRM_Core_Config::domainID(),
      'uf_id' => $ufID,
      'uf_name' => $ufName,
      'contact_id' => $contactID,
    ]);
  }

  private function synchronizeUser(int $ufID, string $email, bool $isLogin): CRM_Core_DAO_UFMatch {
    $user = (object) ['id' => $ufID, 'email' => $email, 'mail' => $email];
    $post = $_POST;
    $_POST = $isLogin ? [] : ['email' => $email];
    try {
      return CRM_Core_BAO_UFMatch::synchronizeUFMatch($user, $ufID, $email, 'Drupal', NULL, 'Individual', $isLogin);
    }
    finally {
      $_POST = $post;
    }
  }

  /**
   * Reads the link from the table: getContactId() caches it across tests.
   */
  private function getLinkedContactID(int $ufID): ?int {
    $contactID = CRM_Core_DAO::singleValueQuery(
      'SELECT contact_id FROM civicrm_uf_match WHERE uf_id = %1',
      [1 => [$ufID, 'Integer']]
    );
    return $contactID ? (int) $contactID : NULL;
  }

  private function getContactIDsWithEmail(string $email): array {
    return array_map('intval', Email::get(FALSE)
      ->addSelect('contact_id')
      ->addWhere('email', '=', $email)
      ->addWhere('contact_id.is_deleted', '=', FALSE)
      ->addOrderBy('contact_id')
      ->execute()
      ->column('contact_id'));
  }

}
