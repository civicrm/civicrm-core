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
    CRM_Core_DAO::executeQuery("
      INSERT INTO civicrm_tag (id, name, label, used_for, is_tagset)
      VALUES
        (1, 'Isaac', 'Isaac', 'civicrm_contact', 0),
        (2, 'Abraham', 'Abraham', 'civicrm_contact', 0),
        (3, 'Jacob', 'Jacob', 'civicrm_contact', 0),
        (4, 'Ishmael', 'Ishmael', 'civicrm_contact', 1),
        (5, 'Kedar', 'Kedar', 'civicrm_contact', 1),
        (6, 'Working', 'Working', 'civicrm_activity', 1),
        (7, 'Eating', 'Eating', 'civicrm_activity', 1);
    ");

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
        'label' => 'Abraham',
        'description' => NULL,
        'is_selectable' => '1',
        'children' => [
          1 => [
            'parent_id' => '2',
            'name' => 'Isaac',
            'label' => 'Isaac',
            'description' => NULL,
            'is_selectable' => '1',
            'children' => [
              3 => [
                'parent_id' => '1',
                'name' => 'Jacob',
                'label' => 'Jacob',
                'description' => NULL,
                'is_selectable' => '1',
                'children' => [],
              ],
            ],
          ],
          4 => [
            'parent_id' => '2',
            'name' => 'Ishmael',
            'label' => 'Ishmael',
            'is_selectable' => '1',
            'description' => NULL,
            'children' => [
              5 => [
                'parent_id' => '4',
                'name' => 'Kedar',
                'label' => 'Kedar',
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
        'label' => 'Eating',
        'description' => NULL,
        'is_selectable' => '1',
        'children' => [],
      ],
      6 => [
        'parent_id' => NULL,
        'name' => 'Working',
        'label' => 'Working',
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
        'label' => 'Abraham',
        'description' => NULL,
        'is_selectable' => '1',
        'children' => [
          1 => [
            'parent_id' => '2',
            'name' => 'Isaac',
            'label' => 'Isaac',
            'description' => NULL,
            'is_selectable' => '1',
            'children' => [
              3 => [
                'parent_id' => '1',
                'name' => 'Jacob',
                'label' => 'Jacob',
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
