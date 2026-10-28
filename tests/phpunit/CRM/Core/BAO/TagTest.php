<?php

/**
 * Class CRM_Core_BAO_TagTest
 * @group headless
 */
class CRM_Core_BAO_TagTest extends CiviUnitTestCase {

  /**
   * Ids of the example tags, keyed by name.
   *
   * @var array
   */
  private $tagIDs = [];

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
      INSERT INTO civicrm_tag (name, label, used_for, is_tagset)
      VALUES
        ('Isaac', 'Isaac', 'civicrm_contact', 0),
        ('Abraham', 'Abraham', 'civicrm_contact', 0),
        ('Jacob', 'Jacob', 'civicrm_contact', 0),
        ('Ishmael', 'Ishmael', 'civicrm_contact', 1),
        ('Kedar', 'Kedar', 'civicrm_contact', 1),
        ('Working', 'Working', 'civicrm_activity', 1),
        ('Eating', 'Eating', 'civicrm_activity', 1);
    ");
    $this->tagIDs = CRM_Core_DAO::executeQuery('SELECT name, id FROM civicrm_tag')->fetchMap('name', 'id');

    $parents = [
      // Isaac is the son of abraham
      'Isaac' => 'Abraham',
      // Jacob is the son of Isaac
      'Jacob' => 'Isaac',
      // Ishmael is the son of abraham
      'Ishmael' => 'Abraham',
      // Kedar is the son of Ishmael
      'Kedar' => 'Ishmael',
    ];
    foreach ($parents as $child => $parent) {
      CRM_Core_DAO::executeQuery('UPDATE civicrm_tag SET parent_id = %1 WHERE id = %2', [
        1 => [$this->tagIDs[$parent], 'Positive'],
        2 => [$this->tagIDs[$child], 'Positive'],
      ]);
    }
  }

  /**
   * Test that we can generate a correct tree of tags without suppliying additional filters.
   *
   * @throws \CRM_Core_Exception
   */
  public function testGetTreeWithoutFilters(): void {
    $tag = $this->tagIDs;
    $bao = new CRM_Core_BAO_Tag();

    $tree = $bao->getTree();

    $expected = [
      $tag['Abraham'] => [
        'parent_id' => NULL,
        'name' => 'Abraham',
        'label' => 'Abraham',
        'description' => NULL,
        'is_selectable' => '1',
        'children' => [
          $tag['Isaac'] => [
            'parent_id' => (string) $tag['Abraham'],
            'name' => 'Isaac',
            'label' => 'Isaac',
            'description' => NULL,
            'is_selectable' => '1',
            'children' => [
              $tag['Jacob'] => [
                'parent_id' => (string) $tag['Isaac'],
                'name' => 'Jacob',
                'label' => 'Jacob',
                'description' => NULL,
                'is_selectable' => '1',
                'children' => [],
              ],
            ],
          ],
          $tag['Ishmael'] => [
            'parent_id' => (string) $tag['Abraham'],
            'name' => 'Ishmael',
            'label' => 'Ishmael',
            'is_selectable' => '1',
            'description' => NULL,
            'children' => [
              $tag['Kedar'] => [
                'parent_id' => (string) $tag['Ishmael'],
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
      $tag['Eating'] => [
        'parent_id' => NULL,
        'name' => 'Eating',
        'label' => 'Eating',
        'description' => NULL,
        'is_selectable' => '1',
        'children' => [],
      ],
      $tag['Working'] => [
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
    $tag = $this->tagIDs;
    $bao = new CRM_Core_BAO_Tag();

    $tree = $bao->getTree('civicrm_contact', TRUE);

    $expected = [
      $tag['Abraham'] => [
        'parent_id' => NULL,
        'name' => 'Abraham',
        'label' => 'Abraham',
        'description' => NULL,
        'is_selectable' => '1',
        'children' => [
          $tag['Isaac'] => [
            'parent_id' => (string) $tag['Abraham'],
            'name' => 'Isaac',
            'label' => 'Isaac',
            'description' => NULL,
            'is_selectable' => '1',
            'children' => [
              $tag['Jacob'] => [
                'parent_id' => (string) $tag['Isaac'],
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
