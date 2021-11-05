<?php
namespace api\v4\SearchDisplay;

use Civi\Api4\Contact;
use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;
use Civi\Api4\File;
use Civi\Test\HeadlessInterface;

/**
 * @group headless
 */
class SearchRunWithCustomFieldTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface {

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * Delete all created custom groups.
   *
   * @throws \API_Exception
   */
  public function tearDown(): void {
    CustomGroup::delete(FALSE)->addWhere('id', '>', 0)->execute();
    parent::tearDown();
  }

  /**
   * Test running a searchDisplay with various filters.
   */
  public function testRunWithImageField() {
    CustomGroup::create(FALSE)
      ->addValue('name', 'TestSearchFields')
      ->addValue('extends', 'Individual')
      ->execute()
      ->first();

    CustomField::create(FALSE)
      ->addValue('label', 'MyFile')
      ->addValue('custom_group_id.name', 'TestSearchFields')
      ->addValue('html_type', 'File')
      ->addValue('data_type', 'File')
      ->execute();

    $lastName = uniqid(__FUNCTION__);

    $file = File::create()
      ->addValue('mime_type', 'image/png')
      ->addValue('uri', "tmp/$lastName.png")
      ->execute()->first();

    $sampleData = [
      ['first_name' => 'Zero', 'last_name' => $lastName, 'TestSearchFields.MyFile' => $file['id']],
      ['first_name' => 'One', 'middle_name' => 'None', 'last_name' => $lastName],
    ];
    Contact::save(FALSE)->setRecords($sampleData)->execute();

    $params = [
      'checkPermissions' => FALSE,
      'return' => 'page:1',
      'savedSearch' => [
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'select' => ['id', 'first_name', 'last_name', 'TestSearchFields.MyFile'],
          'where' => [['last_name', '=', $lastName]],
        ],
      ],
      'display' => [
        'type' => 'table',
        'label' => '',
        'settings' => [
          'limit' => 20,
          'pager' => TRUE,
          'columns' => [
            [
              'key' => 'id',
              'label' => 'Contact ID',
              'type' => 'field',
            ],
            [
              'key' => 'TestSearchFields.MyFile',
              'label' => 'Type',
              'type' => 'image',
              'empty_value' => 'http://example.com/image',
            ],
          ],
          'sort' => [
            ['id', 'ASC'],
          ],
        ],
      ],
    ];

    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertStringContainsString('id=' . $file['id'], $result[0]['data']['TestSearchFields.MyFile']);
    $this->assertStringContainsString('id=' . $file['id'], $result[0]['columns'][1]['img']['src']);
    $this->assertEmpty($result[1]['data']['TestSearchFields.MyFile']);
    // Placeholder image
    $this->assertStringContainsString('example.com', $result[1]['columns'][1]['img']['src']);
  }

}
