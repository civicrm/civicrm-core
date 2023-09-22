<?php

/**
 * Class CRM_Core_BAO_TagTest
 * @group headless
 */
class CRM_Core_BAO_TagTest extends CiviUnitTestCase {

  /**
   * Set up for class.
   *
   * @throws \CRM_Core_Exception
   */
  public function setUp(): void {
    parent::setUp();
    $this->quickCleanup(['civicrm_tag']);

    // Create an example hierarchy of tags.
    // The family tree of Abraham is used as a well known example of a hierarchy (no statement intended).
    // The order of ids is important because of: https://lab.civicrm.org/dev/core/-/issues/4049, that's why we create Isaac before Abraham.
    CRM_Core_DAO::executeQuery("INSERT INTO civicrm_tag (id,name,used_for,is_tagset) VALUES(1, 'Isaac', 'civicrm_contact', 0);");
    CRM_Core_DAO::executeQuery("INSERT INTO civicrm_tag (id,name,used_for,is_tagset) VALUES(2, 'Abraham', 'civicrm_contact', 0);");
    CRM_Core_DAO::executeQuery("INSERT INTO civicrm_tag (id,name,used_for,is_tagset) VALUES(3, 'Jacob', 'civicrm_contact', 0);");
    CRM_Core_DAO::executeQuery("INSERT INTO civicrm_tag (id,name,used_for,is_tagset) VALUES(4, 'Ishmael', 'civicrm_contact', 1);");
    CRM_Core_DAO::executeQuery("INSERT INTO civicrm_tag (id,name,used_for,is_tagset) VALUES(5, 'Kedar', 'civicrm_contact', 1);");
    CRM_Core_DAO::executeQuery("INSERT INTO civicrm_tag (id,name,used_for,is_tagset) VALUES(6, 'Working', 'civicrm_activity', 1);");
    CRM_Core_DAO::executeQuery("INSERT INTO civicrm_tag (id,name,used_for,is_tagset) VALUES(7, 'Eating', 'civicrm_activity', 1);");

    // Isaac is the son of abraham
    CRM_Core_DAO::executeQuery("UPDATE civicrm_tag SET parent_id = 2 WHERE name = 'Isaac';");

    // Jacob is the son of Isaac
    CRM_Core_DAO::executeQuery("UPDATE civicrm_tag SET parent_id = 1 WHERE name = 'Jacob';");

    // Ishmael is the son of abraham
    CRM_Core_DAO::executeQuery("UPDATE civicrm_tag SET parent_id = 2 WHERE name = 'Ishmael';");

    // Kedar is the son of Ishmael
    CRM_Core_DAO::executeQuery("UPDATE civicrm_tag SET parent_id = 4 WHERE name = 'Kedar';");
  }

  /**
   * Test that we can generate a correct tree of tags without suppliying additional filters.
   *
   * @throws \CRM_Core_Exception
   */
  public function testGetTreeWithoutFilters(): void {
    $bao = new CRM_Core_BAO_Tag();

    $tree = $bao->getTree();

    $expected = [
      2 => [
        'parent_id' => NULL,
        'name' => 'Abraham',
        'description' => NULL,
        'is_selectable' => '1',
        'children' => [
          1 => [
            'parent_id' => '2',
            'name' => 'Isaac',
            'description' => NULL,
            'is_selectable' => '1',
            'children' => [
              3 => [
                'parent_id' => '1',
                'name' => 'Jacob',
                'description' => NULL,
                'is_selectable' => '1',
                'children' => [],
              ],
            ],
          ],
          4 => [
            'parent_id' => '2',
            'name' => 'Ishmael',
            'is_selectable' => '1',
            'description' => NULL,
            'children' => [
              5 => [
                'parent_id' => '4',
                'name' => 'Kedar',
                'description' => NULL,
                'is_selectable' => '1',
                'children' => [],
              ],
            ],
          ],
        ],
      ],
      7 => [
        'parent_id' => NULL,
        'name' => 'Eating',
        'description' => NULL,
        'is_selectable' => '1',
        'children' => [],
      ],
      6 => [
        'parent_id' => NULL,
        'name' => 'Working',
        'description' => NULL,
        'is_selectable' => '1',
        'children' => [],
      ],
    ];

    $this->assertEquals($expected, $tree);
  }

  /**
   * Test that we can generate a correct tree of tags when using a usedFor filter and we're excluding hidden tags.
   *
   * @throws \CRM_Core_Exception
   */
  public function testGetTreeWithFilters(): void {
    $bao = new CRM_Core_BAO_Tag();

    $tree = $bao->getTree('civicrm_contact', TRUE);

    $expected = [
      2 => [
        'parent_id' => NULL,
        'name' => 'Abraham',
        'description' => NULL,
        'is_selectable' => '1',
        'children' => [
          1 => [
            'parent_id' => '2',
            'name' => 'Isaac',
            'description' => NULL,
            'is_selectable' => '1',
            'children' => [
              3 => [
                'parent_id' => '1',
                'name' => 'Jacob',
                'description' => NULL,
                'is_selectable' => '1',
                'children' => [],
              ],
            ],
          ],
        ],
      ],
    ];

    $this->assertEquals($expected, $tree);
  }

}
