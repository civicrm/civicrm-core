<?php

namespace api\v4\SearchDisplay;

use Civi\Api4\CustomGroup;
use Civi\Api4\CustomField;
use Civi\Api4\Contact;
use Civi\Api4\Individual;
use Civi\Api4\Organization;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;
use Civi\Api4\Mailing;

/**
 * @group headless
 */
class AbstractRunActionTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, TransactionalInterface {

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function testReplaceTokens() {
    CustomGroup::create(FALSE)
      ->addValue('title', 'Foods')
      ->addValue('name', 'Foods')
      ->execute();

    CustomField::create(FALSE)
      ->addValue('custom_group_id.name', 'Foods')
      ->addValue('label', 'I Like')
      ->addValue('serialize:name', \CRM_Core_DAO::SERIALIZE_SEPARATOR_BOOKEND)
      ->addValue('html_type', 'Autocomplete-Select')
      ->addValue('data_type', 'String')
      ->addValue('option_values', ['Pie', 'Cake', 'Anything you make'])
      ->execute();

    Contact::create(FALSE)
      ->addValue('contact_type', 'Individual')
      ->addValue('first_name', 'Lee')
      ->addValue('last_name', 'Morse')
      ->addValue('Foods.I_Like', [0, 1, 2])
      ->execute();

    $entity = 'SearchDisplay';
    $action = 'run';
    $params = [
      'return' => 'page:1',
      'savedSearch' => [
        'id' => 1,
        'name' => 'Multi_Select_Test',
        'label' => 'Multi Select Test',
        'form_values' => NULL,
        'mapping_id' => NULL,
        'search_custom_id' => NULL,
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'select' => [
            'display_name',
            'Foods.I_Like:label',
          ],
          'orderBy' => [],
          'where' => [
            [
              'contact_type:name',
              '=',
              'Individual',
            ],
            [
              'Foods.I_Like:name',
              'IS NOT EMPTY',
            ],
          ],
          'groupBy' => [
            'id',
          ],
          'having' => [],
        ],
        'created_id' => 203,
        'modified_id' => 203,
        'expires_date' => NULL,
        'created_date' => '2022-08-12 13:49:17',
        'modified_date' => '2022-08-12 17:18:24',
        'description' => NULL,
        'tag_id' => [],
        'groups' => [],
        'displays' => [
          [
            'id' => 1,
            'name' => 'Contacts_Table_1',
            'label' => 'Contacts Table 1',
            'saved_search_id' => 1,
            'type' => 'table',
            'settings' => [
              'actions' => TRUE,
              'limit' => 50,
              'classes' => [
                'table',
                'table-striped',
              ],
              'pager' => [],
              'placeholder' => 5,
              'sort' => [
                [
                  'sort_name',
                  'ASC',
                ],
              ],
              'columns' => [
                [
                  'type' => 'field',
                  'key' => 'display_name',
                  'label' => 'Display Name',
                  'sortable' => TRUE,
                  'link' =>
                  [
                    'path' => '',
                    'entity' => 'Contact',
                    'action' => 'view',
                    'join' => '',
                    'target' => '_blank',
                  ],
                  'title' => 'View Contact',
                ],
                [
                  'type' => 'field',
                  'key' => 'Foods.I_Like:label',
                  'label' => 'Foods: I Like',
                  'sortable' => TRUE,
                  'rewrite' => '[Foods.I_Like:label]',
                ],
              ],
            ],
            'acl_bypass' => FALSE,
          ],
        ],
      ],
      'display' => [
        'id' => 1,
        'name' => 'Contacts_Table_1',
        'label' => 'Contacts Table 1',
        'saved_search_id' => 1,
        'type' => 'table',
        'settings' => [
          'actions' => TRUE,
          'limit' => 50,
          'classes' => [
            'table',
            'table-striped',
          ],
          'pager' => [],
          'placeholder' => 5,
          'sort' => [
            [
              'sort_name',
              'ASC',
            ],
          ],
          'columns' => [
            [
              'type' => 'field',
              'key' => 'display_name',
              'label' => 'Display Name',
              'sortable' => TRUE,
              'link' =>
              [
                'path' => '',
                'entity' => 'Contact',
                'action' => 'view',
                'join' => '',
                'target' => '_blank',
              ],
              'title' => 'View Contact',
            ],
            [
              'type' => 'field',
              'key' => 'Foods.I_Like:label',
              'label' => 'Foods: I Like',
              'sortable' => TRUE,
              'rewrite' => '[Foods.I_Like:label]',
            ],
          ],
        ],
        'acl_bypass' => FALSE,
      ],
      'sort' => [
        [
          'sort_name',
          'ASC',
        ],
      ],
      'limit' => 50,
      'seed' => 1660599799146,
      'filters' => [],
      'afform' => NULL,
      'debug' => TRUE,
      'checkPermissions' => TRUE,
    ];
    $result = civicrm_api4($entity, $action, $params);
    $resultData = $result[0]['data']['Foods.I_Like:label'];
    $this->assertTrue(implode(', ', $resultData) === $result[0]['columns'][1]['val']);
  }

  public function testDomainConditional(): void {
    Mailing::create()->setValues([
      'title' => 'Test Mailing' . __FUNCTION__,
      'body_html' => 'Test content',
    ])->execute();
    $entity = 'SearchDisplay';
    $action = 'run';
    $params = [
      'return' => 'page:1',
      'savedSearch' => [
        'id' => 2,
        'name' => 'Test_Mailing',
        'label' => 'Test Mailing',
        'form_values' => NULL,
        'mapping_id' => NULL,
        'search_custom_id' => NULL,
        'api_entity' => 'Mailing',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'name',
            'domain_id:label',
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [],
          'join' => [],
          'having' => [],
        ],
        'created_id' => 203,
        'modified_id' => 203,
        'expires_date' => NULL,
        'created_date' => '2022-08-12 13:49:17',
        'modified_date' => '2022-08-12 17:18:24',
        'description' => NULL,
        'tag_id' => [],
        'groups' => [],
        'displays' => [
          [
            'id' => 2,
            'name' => 'Test_Mailing_Table_1',
            'label' => 'Test Mailing Table 1',
            'saved_search_id' => 2,
            'type' => 'table',
            'settings' => [
              'description' => NULL,
              'sort' => [],
              'limit' => 50,
              'pager' => [],
              'placeholder' => 5,
              'columns' => [
                [
                  'type' => 'field',
                  'key' => 'id',
                  'label' => 'Mailing ID',
                  'sortable' => TRUE,
                ],
                [
                  'type' => 'field',
                  'key' => 'name',
                  'label' => 'Mailing Name',
                  'sortable' => TRUE,
                ],
                [
                  'type' => 'field',
                  'key' => 'domain_id:label',
                  'label' => 'Domain',
                  'sortable' => TRUE,
                ],
                [
                  'text' => '',
                  'style' => 'default',
                  'size' => 'btn-xs',
                  'icon' => 'fa-bars',
                  'links' => [
                    [
                      'entity' => 'Mailing',
                      'action' => 'view',
                      'join' => '',
                      'target' => 'crm-popup',
                      'icon' => 'fa-external-link',
                      'text' => 'View Mailing',
                      'style' => 'default',
                      'path' => '',
                      'task' => '',
                      'condition' => [
                        'domain_id:label',
                        '=',
                        'current_domain',
                      ],
                    ],
                    [
                      'entity' => 'Mailing',
                      'action' => 'update',
                      'join' => '',
                      'target' => 'crm-popup',
                      'icon' => 'fa-pencil',
                      'text' => 'Update Mailing',
                      'style' => 'default',
                      'path' => '',
                      'task' => '',
                      'condition' => [],
                    ],
                    [
                      'entity' => 'Mailing',
                      'action' => 'preview',
                      'join' => '',
                      'target' => 'crm-popup',
                      'icon' => 'fa-eye',
                      'text' => 'Preview Mailing',
                      'style' => 'default',
                      'path' => '',
                      'task' => '',
                      'condition' => [],
                    ],
                  ],
                  'type' => 'menu',
                  'alignment' => 'text-right',
                ],
              ],
              'actions' => TRUE,
              'classes' => [
                'table',
                'table-striped',
              ],
            ],
            'acl_bypass' => FALSE,
          ],
        ],
      ],
      'display' => [
        'id' => 2,
        'name' => 'Test_Mailing_Table_1',
        'label' => 'Test Mailing Table 1',
        'saved_search_id' => 2,
        'type' => 'table',
        'settings' => [
          'description' => NULL,
          'sort' => [],
          'limit' => 50,
          'pager' => [],
          'placeholder' => 5,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'id',
              'label' => 'Mailing ID',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'name',
              'label' => 'Mailing Name',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'domain_id:label',
              'label' => 'Domain',
              'sortable' => TRUE,
            ],
            [
              'text' => '',
              'style' => 'default',
              'size' => 'btn-xs',
              'icon' => 'fa-bars',
              'links' => [
                [
                  'entity' => 'Mailing',
                  'action' => 'view',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-external-link',
                  'text' => 'View Mailing',
                  'style' => 'default',
                  'path' => '',
                  'task' => '',
                  'condition' => [
                    'domain_id:label',
                    '=',
                    'current_domain',
                  ],
                ],
                [
                  'entity' => 'Mailing',
                  'action' => 'update',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-pencil',
                  'text' => 'Update Mailing',
                  'style' => 'default',
                  'path' => '',
                  'task' => '',
                  'condition' => [],
                ],
                [
                  'entity' => 'Mailing',
                  'action' => 'preview',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-eye',
                  'text' => 'Preview Mailing',
                  'style' => 'default',
                  'path' => '',
                  'task' => '',
                  'condition' => [],
                ],
              ],
              'type' => 'menu',
              'alignment' => 'text-right',
            ],
          ],
          'actions' => TRUE,
          'classes' => [
            'table',
            'table-striped',
          ],
        ],
        'acl_bypass' => FALSE,
      ],
      'limit' => 50,
      'seed' => 1660599799146,
      'filters' => [],
      'afform' => NULL,
      'debug' => TRUE,
      'checkPermissions' => TRUE,
    ];
    $result = civicrm_api4($entity, $action, $params);
    $this->assertCount(3, $result[0]['columns'][3]['links']);
  }

  public function testGroupedDisplayForcesGroupByIntoSelectAndSort(): void {
    Individual::create(FALSE)->addValue('first_name', 'Ann')->addValue('last_name', 'Adams')->execute();
    Individual::create(FALSE)->addValue('first_name', 'Bob')->addValue('last_name', 'Baker')->execute();
    Organization::create(FALSE)->addValue('organization_name', 'Acme Corp')->execute();
    Organization::create(FALSE)->addValue('organization_name', 'Beta Inc')->execute();

    $display = [
      'id' => 3,
      'name' => 'Test_Grouped_1',
      'label' => 'Test Grouped 1',
      'saved_search_id' => 3,
      'type' => 'list',
      'settings' => [
        'sort' => [
          ['contact_type', 'ASC'],
          ['display_name', 'ASC'],
        ],
        'limit' => 50,
        'group_by' => 'contact_type',
        // contact_type is deliberately not a column - the group header shows it instead.
        'columns' => [
          [
            'type' => 'field',
            'key' => 'display_name',
            'label' => 'Display Name',
            'sortable' => TRUE,
          ],
        ],
      ],
      'acl_bypass' => FALSE,
    ];

    $params = [
      'return' => 'page:1',
      'savedSearch' => [
        'id' => 3,
        'name' => 'Test_Grouped',
        'label' => 'Test Grouped',
        'form_values' => NULL,
        'mapping_id' => NULL,
        'search_custom_id' => NULL,
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'select' => [
            'display_name',
          ],
          'orderBy' => [],
          'where' => [
            ['contact_type', 'IN', ['Individual', 'Organization']],
          ],
          'groupBy' => [],
          'having' => [],
        ],
        'created_id' => 203,
        'modified_id' => 203,
        'expires_date' => NULL,
        'created_date' => '2022-08-12 13:49:17',
        'modified_date' => '2022-08-12 17:18:24',
        'description' => NULL,
        'tag_id' => [],
        'groups' => [],
        'displays' => [$display],
      ],
      'display' => $display,
      // Simulate an interactive column-header sort that matches a real column
      // (display_name) but not the group_by field (contact_type is deliberately
      // not a column) - this is exactly the request shape that, before the
      // getOrderByFromSort() fix, silently dropped group_by from the ORDER BY
      // and scrambled the bands.
      'sort' => [
        ['display_name', 'ASC'],
      ],
      'limit' => 50,
      'seed' => 1,
      'filters' => [],
      'afform' => NULL,
      'debug' => TRUE,
      'checkPermissions' => TRUE,
    ];

    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertGreaterThanOrEqual(4, count($result));

    // group_by must be added to the select even though it's not a column -
    // AbstractRunAction::augmentSelectClause() forces it, same as tree's parent_field.
    $this->assertArrayHasKey('contact_type', $result[0]['data']);

    // Rows must be banded into contiguous runs by contact_type. If group_by ever
    // gets dropped from the ORDER BY again, this comes back interleaved instead.
    $seenTypes = [];
    $previousType = NULL;
    foreach ($result as $row) {
      $type = $row['data']['contact_type'];
      if ($type !== $previousType) {
        $this->assertNotContains($type, $seenTypes, 'contact_type values must not repeat in separate bands');
        $seenTypes[] = $type;
        $previousType = $type;
      }
    }
    $this->assertEquals(['Individual', 'Organization'], $seenTypes);
  }

}
