<?php

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
   * Don't crash if the uf_id doesn't exist
   */
  public function testGetUFValuesWithNonexistentUFId(): void {
    $max_id = (int) CRM_Core_DAO::singleValueQuery('SELECT MAX(uf_id) FROM civicrm_uf_match');
    $dontcrash = CRM_Core_BAO_UFMatch::getUFValues($max_id + 1);
    $this->assertNull($dontcrash);
  }

}
