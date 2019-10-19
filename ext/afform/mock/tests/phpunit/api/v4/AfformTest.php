<?php

/**
 * Afform.Get API Test Case
 * This is a generic test class implemented with PHPUnit.
 * @group headless
 */
class api_v4_AfformTest extends api_v4_AfformTestCase {
  use \Civi\Test\Api3TestTrait;
  use \Civi\Test\ContactTestTrait;

  public function getBasicDirectives() {
    return [
      ['mockPage', ['title' => '', 'description' => '', 'server_route' => 'civicrm/mock-page']],
      ['mockBareFile', ['title' => '', 'description' => '']],
      ['mockFoo', ['title' => '', 'description' => '']],
    ];
  }

  /**
   * This takes the bundled `example-page` and performs some API calls on it.
   * @dataProvider getBasicDirectives
   */
  public function testGetUpdateRevert($directiveName, $originalMetadata) {
    $get = function($arr, $key) {
      return isset($arr[$key]) ? $arr[$key] : NULL;
    };

    Civi\Api4\Afform::revert()->addWhere('name', '=', $directiveName)->execute();

    $message = 'The initial Afform.get should return default data';
    $result = Civi\Api4\Afform::get()->addWhere('name', '=', $directiveName)->execute();
    $this->assertEquals($directiveName, $result[0]['name'], $message);
    $this->assertEquals($get($originalMetadata, 'title'), $get($result[0], 'title'), $message);
    $this->assertEquals($get($originalMetadata, 'description'), $get($result[0], 'description'), $message);
    $this->assertEquals($get($originalMetadata, 'server_route'), $get($result[0], 'server_route'), $message);
    $this->assertTrue(is_array($result[0]['layout']), $message);

    $message = 'After updating with Afform.create, the revised data should be returned';
    $result = Civi\Api4\Afform::update()
      ->addWhere('name', '=', $directiveName)
      ->addValue('description', 'The temporary description')
      ->execute();
    $this->assertEquals($directiveName, $result[0]['name'], $message);
    $this->assertEquals('The temporary description', $result[0]['description'], $message);

    $message = 'After updating, the Afform.get API should return blended data';
    $result = Civi\Api4\Afform::get()->addWhere('name', '=', $directiveName)->execute();
    $this->assertEquals($directiveName, $result[0]['name'], $message);
    $this->assertEquals($get($originalMetadata, 'title'), $get($result[0], 'title'), $message);
    $this->assertEquals('The temporary description', $get($result[0], 'description'), $message);
    $this->assertEquals($get($originalMetadata, 'server_route'), $get($result[0], 'server_route'), $message);
    $this->assertTrue(is_array($result[0]['layout']), $message);

    Civi\Api4\Afform::revert()->addWhere('name', '=', $directiveName)->execute();
    $message = 'After reverting, the final Afform.get should return default data';
    $result = Civi\Api4\Afform::get()->addWhere('name', '=', $directiveName)->execute();
    $this->assertEquals($directiveName, $result[0]['name'], $message);
    $this->assertEquals($get($originalMetadata, 'title'), $get($result[0], 'title'), $message);
    $this->assertEquals($get($originalMetadata, 'description'), $get($result[0], 'description'), $message);
    $this->assertEquals($get($originalMetadata, 'server_route'), $get($result[0], 'server_route'), $message);
    $this->assertTrue(is_array($result[0]['layout']), $message);
  }

  public function getFormatExamples() {
    $es = [];

    foreach (['apple', 'banana'] as $exampleName) {
      $exampleFile = '/formatExamples/' . $exampleName . '.php';
      $example = require __DIR__ . $exampleFile;
      $formats = ['html', 'shallow', 'deep'];
      foreach ($formats as $updateFormat) {
        foreach ($formats as $readFormat) {
          $es[] = ['mockBareFile', $updateFormat, $example[$updateFormat], $readFormat, $example[$readFormat], $exampleFile];
        }
      }
    }

    return $es;
  }

  /**
   * In this test, we update the layout and in one format and then read it back
   * in another format.
   *
   * @param string $directiveName
   * @param string $updateFormat
   *   The format with which to write the data.
   *   'html' or 'array'
   * @param mixed $updateLayout
   *   The new value to set
   * @param string $readFormat
   *   The format with which to read the data.
   *   'html' or 'array'
   * @param mixed $readLayout
   *   The value that we expect to read.
   * @param string $exampleName
   *   (For debug messages) A symbolic name of the example data-set being tested.
   * @dataProvider getFormatExamples
   */
  public function testUpdateAndGetFormat($directiveName, $updateFormat, $updateLayout, $readFormat, $readLayout, $exampleName) {
    Civi\Api4\Afform::revert()->addWhere('name', '=', $directiveName)->execute();

    Civi\Api4\Afform::update()
      ->addWhere('name', '=', $directiveName)
      ->setLayoutFormat($updateFormat)
      ->setValues(['layout' => $updateLayout])
      ->execute();

    $result = Civi\Api4\Afform::get()
      ->addWhere('name', '=', $directiveName)
      ->setLayoutFormat($readFormat)
      ->execute();

    $this->assertEquals($readLayout, $result[0]['layout'], "Based on \"$exampleName\", writing content as \"$updateFormat\" and reading back as \"$readFormat\".");

    Civi\Api4\Afform::revert()->addWhere('name', '=', $directiveName)->execute();
  }

  public function testAutoRequires() {
    $directiveName = 'mockPage';
    $this->createLoggedInUser();

    // The default mockPage has 1 explicit requirement + 2 automatic requirements.
    Civi\Api4\Afform::revert()->addWhere('name', '=', $directiveName)->execute();
    $angModule = Civi::service('angular')->getModule($directiveName);
    $this->assertEquals(['afCore', 'extraMock', 'mockBareFile', 'mockFoo'], $angModule['requires']);
    $storedRequires = Civi\Api4\Afform::get()->addWhere('name', '=', $directiveName)->addSelect('requires')->execute();
    $this->assertEquals(['extraMock'], $storedRequires[0]['requires']);

    // Knock down to 1 explicit + 1 automatic.
    Civi\Api4\Afform::update()
      ->addWhere('name', '=', $directiveName)
      ->setLayoutFormat('html')
      ->setValues(['layout' => '<div>The bare file says "<span mock-bare-file/>"</div>'])
      ->execute();
    $angModule = Civi::service('angular')->getModule($directiveName);
    $this->assertEquals(['afCore', 'extraMock', 'mockBareFile'], $angModule['requires']);
    $storedRequires = Civi\Api4\Afform::get()->addWhere('name', '=', $directiveName)->addSelect('requires')->execute();
    $this->assertEquals(['extraMock'], $storedRequires[0]['requires']);

    // Remove the last explict and implicit requirements.
    Civi\Api4\Afform::update()
      ->addWhere('name', '=', $directiveName)
      ->setLayoutFormat('html')
      ->setValues([
        'layout' => '<div>The file has nothing! <strong>NOTHING!</strong> <em>JUST RANTING!</em></div>',
        'requires' => [],
      ])
      ->execute();
    $angModule = Civi::service('angular')->getModule($directiveName);
    $this->assertEquals(['afCore'], $angModule['requires']);
    $storedRequires = Civi\Api4\Afform::get()->addWhere('name', '=', $directiveName)->addSelect('requires')->execute();
    $this->assertEquals([], $storedRequires[0]['requires']);

    Civi\Api4\Afform::revert()->addWhere('name', '=', $directiveName)->execute();
    $angModule = Civi::service('angular')->getModule($directiveName);
    $this->assertEquals(['afCore', 'extraMock', 'mockBareFile', 'mockFoo'], $angModule['requires']);
  }

}
