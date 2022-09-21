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
    $params = array(
      'return' => 'page:1',
      'savedSearch' =>
      array(
        'id' => 1,
        'name' => 'Multi_Select_Test',
        'label' => 'Multi Select Test',
        'form_values' => NULL,
        'mapping_id' => NULL,
        'search_custom_id' => NULL,
        'api_entity' => 'Contact',
        'api_params' =>
        array(
          'version' => 4,
          'select' =>
          array(
            0 => 'display_name',
            1 => 'Foods.I_Like:label',
          ),
          'orderBy' =>
          array(),
          'where' =>
          array(
            0 =>
            array(
              0 => 'contact_type:name',
              1 => '=',
              2 => 'Individual',
            ),
            1 =>
            array(
              0 => 'Foods.I_Like:name',
              1 => 'IS NOT EMPTY',
            ),
          ),
          'groupBy' =>
          array(
            0 => 'id',
          ),
          'having' =>
          array(),
        ),
        'created_id' => 203,
        'modified_id' => 203,
        'expires_date' => NULL,
        'created_date' => '2022-08-12 13:49:17',
        'modified_date' => '2022-08-12 17:18:24',
        'description' => NULL,
        'tag_id' =>
        array(),
        'groups' =>
        array(),
        'displays' =>
        array(
          0 =>
          array(
            'id' => 1,
            'name' => 'Contacts_Table_1',
            'label' => 'Contacts Table 1',
            'saved_search_id' => 1,
            'type' => 'table',
            'settings' =>
            array(
              'actions' => TRUE,
              'limit' => 50,
              'classes' =>
              array(
                0 => 'table',
                1 => 'table-striped',
              ),
              'pager' =>
              array(),
              'placeholder' => 5,
              'sort' =>
              array(
                0 =>
                array(
                  0 => 'sort_name',
                  1 => 'ASC',
                ),
              ),
              'columns' =>
              array(
                0 =>
                array(
                  'type' => 'field',
                  'key' => 'display_name',
                  'dataType' => 'String',
                  'label' => 'Display Name',
                  'sortable' => TRUE,
                  'link' =>
                  array(
                    'path' => '',
                    'entity' => 'Contact',
                    'action' => 'view',
                    'join' => '',
                    'target' => '_blank',
                  ),
                  'title' => 'View Contact',
                ),
                1 =>
                array(
                  'type' => 'field',
                  'key' => 'Foods.I_Like:label',
                  'dataType' => 'String',
                  'label' => 'Foods: I Like',
                  'sortable' => TRUE,
                  'rewrite' => '[Foods.I_Like:label]',
                ),
              ),
            ),
            'acl_bypass' => FALSE,
          ),
        ),
      ),
      'display' =>
      array(
        'id' => 1,
        'name' => 'Contacts_Table_1',
        'label' => 'Contacts Table 1',
        'saved_search_id' => 1,
        'type' => 'table',
        'settings' =>
        array(
          'actions' => TRUE,
          'limit' => 50,
          'classes' =>
          array(
            0 => 'table',
            1 => 'table-striped',
          ),
          'pager' =>
          array(),
          'placeholder' => 5,
          'sort' =>
          array(
            0 =>
            array(
              0 => 'sort_name',
              1 => 'ASC',
            ),
          ),
          'columns' =>
          array(
            0 =>
            array(
              'type' => 'field',
              'key' => 'display_name',
              'dataType' => 'String',
              'label' => 'Display Name',
              'sortable' => TRUE,
              'link' =>
              array(
                'path' => '',
                'entity' => 'Contact',
                'action' => 'view',
                'join' => '',
                'target' => '_blank',
              ),
              'title' => 'View Contact',
            ),
            1 =>
            array(
              'type' => 'field',
              'key' => 'Foods.I_Like:label',
              'dataType' => 'String',
              'label' => 'Foods: I Like',
              'sortable' => TRUE,
              'rewrite' => '[Foods.I_Like:label]',
            ),
          ),
        ),
        'acl_bypass' => FALSE,
      ),
      'sort' =>
      array(
        0 =>
        array(
          0 => 'sort_name',
          1 => 'ASC',
        ),
      ),
      'limit' => 50,
      'seed' => 1660599799146,
      'filters' =>
      array(),
      'afform' => NULL,
      'debug' => TRUE,
      'checkPermissions' => TRUE,
    );
    $result = civicrm_api4($entity, $action, $params);
    $resultData = $result[0]['data']['Foods.I_Like:label'];
    $this->assertTrue(implode(', ', $resultData) === $result[0]['columns'][1]['val']);
  }

}
