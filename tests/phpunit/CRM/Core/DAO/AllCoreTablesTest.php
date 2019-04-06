<?php

/**
 * Class CRM_Core_DAO_AllCoreTablesTest
 * @group headless
 */
class CRM_Core_DAO_AllCoreTablesTest extends CiviUnitTestCase {

  public function testGetTableForClass() {
    $this->assertEquals('civicrm_email', CRM_Core_DAO_AllCoreTables::getTableForClass('CRM_Core_DAO_Email'));
    $this->assertEquals('civicrm_email', CRM_Core_DAO_AllCoreTables::getTableForClass('CRM_Core_BAO_Email'));
  }

  /**
   * Ensure that hook_civicrm_entityTypes runs and correctly handles the
   * 'fields_callback' option.
   */
  public function testHook() {
    // 1. First, check the baseline fields()...
    $fields = CRM_Core_DAO_Email::fields();
    $this->assertFalse(isset($fields['location_type_id']['foo']));

    $exports = CRM_Core_DAO_Email::export();
    $this->assertFalse(isset($exports['contact_id']));

    // 2. Now, let's hook into it...
    $this->hookClass->setHook('civicrm_entityTypes', array($this, '_hook_civicrm_entityTypes'));
    unset(Civi::$statics['CRM_Core_DAO_Email']);
    CRM_Core_DAO_AllCoreTables::init(1);

    // 3. And see if the data has changed...
    $fields = CRM_Core_DAO_Email::fields();
    $this->assertEquals('bar', $fields['location_type_id']['foo']);

    $exports = CRM_Core_DAO_Email::export();
    $this->assertTrue(is_array($exports['contact_id']));
  }

  /**
   * Implements hook_civicrm_entityTypes().
   *
   * @see CRM_Utils_Hook::entityTypes()
   */
  public function _hook_civicrm_entityTypes(&$entityTypes) {
    $entityTypes['CRM_Core_DAO_Email']['fields_callback'][] = function ($class, &$fields) {
      $fields['location_type_id']['foo'] = 'bar';
      $fields['contact_id']['export'] = TRUE;
    };
  }

  protected function tearDown() {
    CRM_Utils_Hook::singleton()->reset();
    CRM_Core_DAO_AllCoreTables::init(1);
    parent::tearDown();
  }

  /**
   * Test CRM_Core_DAO_AllCoreTables::indices() function.
   *
   * Ensure indices are listed correctly with and without localization
   */
  public function testIndices() {
    // civicrm_group UI_title is localizable
    // Check indices without localization
    $indices = CRM_Core_DAO_AllCoreTables::indices(FALSE);
    $this->assertEquals($indices['civicrm_group']['UI_title']['name'], 'UI_title');
    $this->assertEquals($indices['civicrm_group']['UI_title']['sig'], 'civicrm_group::1::title');

    // Not sure how we should be setting the locales, but this works for testing purposes
    $domain = new CRM_Core_DAO_Domain();
    $domain->find(TRUE);
    $domain->locales = implode(CRM_Core_DAO::VALUE_SEPARATOR, array('en_UK', 'fr_FR'));
    $domain->save();

    // Check indices with localization
    $indices = CRM_Core_DAO_AllCoreTables::indices(TRUE);
    $this->assertEquals($indices['civicrm_group']['UI_title_en_UK']['name'], 'UI_title_en_UK');
    $this->assertEquals($indices['civicrm_group']['UI_title_en_UK']['sig'], 'civicrm_group::1::title_en_UK');

    $this->assertEquals($indices['civicrm_group']['UI_title_fr_FR']['name'], 'UI_title_fr_FR');
    $this->assertEquals($indices['civicrm_group']['UI_title_fr_FR']['sig'], 'civicrm_group::1::title_fr_FR');
  }

  /**
   * Check CRM_Core_DAO_AllCoreTables::multilingualize()
   */
  public function testMultilingualize() {
    // in civicrm_group, title is localizable, name is not
    $originalIndices = array(
      'test_index1' => array(
        'name' => 'test_index1',
        'field' => array(
          'name',
        ),
        'localizable' => 0,
      ),
      'test_index2' => array(
        'name' => 'test_index2',
        'field' => array(
          'title',
        ),
        'localizable' => 1,
      ),
      'test_index3' => array(
        'name' => 'test_index3',
        'field' => array(
          'name(3)',
        ),
        'localizable' => 0,
      ),
      'test_index4' => array(
        'name' => 'test_index4',
        'field' => array(
          'title(4)',
        ),
        'localizable' => 1,
      ),
      'test_index5' => array(
        'name' => 'test_index5',
        'field' => array(
          'title(4)',
          'name(3)',
        ),
        'localizable' => 1,
      ),
    );

    $expectedIndices = array(
      'test_index1' => array(
        'name' => 'test_index1',
        'field' => array(
          'name',
        ),
        'localizable' => 0,
        'sig' => 'civicrm_group::0::name',
      ),
      'test_index2_en_UK' => array(
        'name' => 'test_index2_en_UK',
        'field' => array(
          'title_en_UK',
        ),
        'localizable' => 1,
        'sig' => 'civicrm_group::0::title_en_UK',
      ),
      'test_index2_fr_FR' => array(
        'name' => 'test_index2_fr_FR',
        'field' => array(
          'title_fr_FR',
        ),
        'localizable' => 1,
        'sig' => 'civicrm_group::0::title_fr_FR',
      ),
      'test_index3' => array(
        'name' => 'test_index3',
        'field' => array(
          'name(3)',
        ),
        'localizable' => 0,
        'sig' => 'civicrm_group::0::name(3)',
      ),
      'test_index4_en_UK' => array(
        'name' => 'test_index4_en_UK',
        'field' => array(
          'title_en_UK(4)',
        ),
        'localizable' => 1,
        'sig' => 'civicrm_group::0::title_en_UK(4)',
      ),
      'test_index4_fr_FR' => array(
        'name' => 'test_index4_fr_FR',
        'field' => array(
          'title_fr_FR(4)',
        ),
        'localizable' => 1,
        'sig' => 'civicrm_group::0::title_fr_FR(4)',
      ),
      'test_index5_en_UK' => array(
        'name' => 'test_index5_en_UK',
        'field' => array(
          'title_en_UK(4)',
          'name(3)',
        ),
        'localizable' => 1,
        'sig' => 'civicrm_group::0::title_en_UK(4)::name(3)',
      ),
      'test_index5_fr_FR' => array(
        'name' => 'test_index5_fr_FR',
        'field' => array(
          'title_fr_FR(4)',
          'name(3)',
        ),
        'localizable' => 1,
        'sig' => 'civicrm_group::0::title_fr_FR(4)::name(3)',
      ),
    );

    // Not sure how we should be setting the locales, but this works for testing purposes
    $domain = new CRM_Core_DAO_Domain();
    $domain->find(TRUE);
    $domain->locales = implode(CRM_Core_DAO::VALUE_SEPARATOR, array('en_UK', 'fr_FR'));
    $domain->save();

    // needs a real DAO so use Group
    $newIndices = CRM_Core_DAO_AllCoreTables::multilingualize('CRM_Contact_DAO_Group', $originalIndices);
    $this->assertEquals($newIndices, $expectedIndices);
  }

  /**
   * Test CRM_Core_DAO_AllCoreTables::isCoreTable
   */
  public function testIsCoreTable() {
    $this->assertTrue(CRM_Core_DAO_AllCoreTables::isCoreTable('civicrm_contact'), 'civicrm_contact should be a core table');
    $this->assertFalse(CRM_Core_DAO_AllCoreTables::isCoreTable('civicrm_invalid_table'), 'civicrm_invalid_table should NOT be a core table');
  }

}
