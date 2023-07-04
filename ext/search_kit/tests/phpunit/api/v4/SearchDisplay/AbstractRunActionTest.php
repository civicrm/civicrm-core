<?php

namespace api\v4\SearchDisplay;

use Civi\Api4\CustomGroup;
use Civi\Api4\CustomField;
use Civi\Api4\Contact;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

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
                  'dataType' => 'String',
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
                  'dataType' => 'String',
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
              'dataType' => 'String',
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
              'dataType' => 'String',
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

}
