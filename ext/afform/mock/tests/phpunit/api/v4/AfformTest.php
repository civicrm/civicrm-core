<?php

/**
 * Afform.Get API Test Case
 * This is a generic test class implemented with PHPUnit.
 * @group headless
 */
class api_v4_AfformTest extends api_v4_AfformTestCase {
  use \Civi\Test\Api3TestTrait;
  use \Civi\Test\ContactTestTrait;

  /**
   * DOMDocument outputs some tags a little different than they were input.
   * It's not really a problem but can trip up tests.
   *
   * @param array|string $markup
   * @return array|string
   */
  private function fudgeMarkup($markup) {
    if (is_array($markup)) {
      foreach ($markup as $idx => $item) {
        $markup[$idx] = $this->fudgeMarkup($item);
      }
      return $markup;
    }
    else {
      return str_replace([' />', '/>'], ['/>', ' />'], $markup);
    }
  }

  public function getBasicDirectives() {
    return [
      ['mockPage', ['title' => '', 'description' => '', 'server_route' => 'civicrm/mock-page', 'permission' => 'access Foobar']],
      ['mockBareFile', ['title' => '', 'description' => '', 'permission' => 'access CiviCRM']],
      ['mockFoo', ['title' => '', 'description' => '', 'permission' => 'access CiviCRM']],
      ['mock-weird-name', ['title' => 'Weird Name', 'description' => '', 'permission' => 'access CiviCRM']],
    ];
  }

  /**
   * This takes the bundled `example-page` and performs some API calls on it.
   *
   * @param string $formName
   *   The symbolic name of the form.
   * @param array $originalMetadata
   * @dataProvider getBasicDirectives
   */
  public function testGetUpdateRevert($formName, $originalMetadata) {
    $get = function($arr, $key) {
      return isset($arr[$key]) ? $arr[$key] : NULL;
    };

    Civi\Api4\Afform::revert()->addWhere('name', '=', $formName)->execute();

    $message = 'The initial Afform.get should return default data';
    $result = Civi\Api4\Afform::get()->addWhere('name', '=', $formName)->execute();
    $this->assertEquals($formName, $result[0]['name'], $message);
    $this->assertEquals($get($originalMetadata, 'title'), $get($result[0], 'title'), $message);
    $this->assertEquals($get($originalMetadata, 'description'), $get($result[0], 'description'), $message);
    $this->assertEquals($get($originalMetadata, 'server_route'), $get($result[0], 'server_route'), $message);
    $this->assertEquals($get($originalMetadata, 'permission'), $get($result[0], 'permission'), $message);
    $this->assertTrue(is_array($result[0]['layout']), $message);
    $this->assertEquals(TRUE, $get($result[0], 'has_base'), $message);
    $this->assertEquals(FALSE, $get($result[0], 'has_local'), $message);

    $message = 'After updating with Afform.create, the revised data should be returned';
    $result = Civi\Api4\Afform::update()
      ->addWhere('name', '=', $formName)
      ->addValue('description', 'The temporary description')
      ->addValue('permission', 'access foo')
      ->execute();
    $this->assertEquals($formName, $result[0]['name'], $message);
    $this->assertEquals('The temporary description', $result[0]['description'], $message);

    $message = 'After updating, the Afform.get API should return blended data';
    $result = Civi\Api4\Afform::get()->addWhere('name', '=', $formName)->execute();
    $this->assertEquals($formName, $result[0]['name'], $message);
    $this->assertEquals($get($originalMetadata, 'title'), $get($result[0], 'title'), $message);
    $this->assertEquals('The temporary description', $get($result[0], 'description'), $message);
    $this->assertEquals($get($originalMetadata, 'server_route'), $get($result[0], 'server_route'), $message);
    $this->assertEquals('access foo', $get($result[0], 'permission'), $message);
    $this->assertTrue(is_array($result[0]['layout']), $message);
    $this->assertEquals(TRUE, $get($result[0], 'has_base'), $message);
    $this->assertEquals(TRUE, $get($result[0], 'has_local'), $message);

    Civi\Api4\Afform::revert()->addWhere('name', '=', $formName)->execute();
    $message = 'After reverting, the final Afform.get should return default data';
    $result = Civi\Api4\Afform::get()->addWhere('name', '=', $formName)->execute();
    $this->assertEquals($formName, $result[0]['name'], $message);
    $this->assertEquals($get($originalMetadata, 'title'), $get($result[0], 'title'), $message);
    $this->assertEquals($get($originalMetadata, 'description'), $get($result[0], 'description'), $message);
    $this->assertEquals($get($originalMetadata, 'server_route'), $get($result[0], 'server_route'), $message);
    $this->assertEquals($get($originalMetadata, 'permission'), $get($result[0], 'permission'), $message);
    $this->assertTrue(is_array($result[0]['layout']), $message);
    $this->assertEquals(TRUE, $get($result[0], 'has_base'), $message);
    $this->assertEquals(FALSE, $get($result[0], 'has_local'), $message);
  }

  public function getFormatExamples() {
    $ex = [];
    $formats = ['html', 'shallow', 'deep'];
    foreach (glob(__DIR__ . '/formatExamples/*.php') as $exampleFile) {
      $example = require $exampleFile;
      if (isset($example['deep'])) {
        foreach ($formats as $updateFormat) {
          foreach ($formats as $readFormat) {
            $ex[] = ['mockBareFile', $updateFormat, $example[$updateFormat], $readFormat, $example[$readFormat], $exampleFile];
          }
        }
      }
    }
    return $ex;
  }

  /**
   * In this test, we update the layout and in one format and then read it back
   * in another format.
   *
   * @param string $formName
   *   The symbolic name of the form.
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
  public function testUpdateAndGetFormat($formName, $updateFormat, $updateLayout, $readFormat, $readLayout, $exampleName) {
    Civi\Api4\Afform::revert()->addWhere('name', '=', $formName)->execute();

    Civi\Api4\Afform::update()
      ->addWhere('name', '=', $formName)
      ->setLayoutFormat($updateFormat)
      ->setValues(['layout' => $updateLayout])
      ->execute();

    $result = Civi\Api4\Afform::get()
      ->addWhere('name', '=', $formName)
      ->setLayoutFormat($readFormat)
      ->execute();

    $this->assertEquals($readLayout, $this->fudgeMarkup($result[0]['layout']), "Based on \"$exampleName\", writing content as \"$updateFormat\" and reading back as \"$readFormat\".");

    Civi\Api4\Afform::revert()->addWhere('name', '=', $formName)->execute();
  }

  public function getWhitespaceExamples() {
    $ex = [];
    foreach (glob(__DIR__ . '/formatExamples/*.php') as $exampleFile) {
      $example = require $exampleFile;
      if (isset($example['pretty'])) {
        $ex[] = ['mockBareFile', $example, $exampleFile];
      }
    }
    return $ex;
  }

  /**
   * This tests that a non-pretty html string will have its whitespace stripped & reformatted
   * when using the "formatWhitespace" option.
   *
   * @dataProvider getWhitespaceExamples
   */
  public function testWhitespaceFormat($directiveName, $example, $exampleName) {
    Civi\Api4\Afform::save()
      ->addRecord(['name' => $directiveName, 'layout' => $example['html']])
      ->setLayoutFormat('html')
      ->execute();

    $result = Civi\Api4\Afform::get()
      ->addWhere('name', '=', $directiveName)
      ->setLayoutFormat('shallow')
      ->setFormatWhitespace(TRUE)
      ->execute()
      ->first();

    $this->assertEquals($example['stripped'] ?? $example['shallow'], $this->fudgeMarkup($result['layout']));

    Civi\Api4\Afform::save()
      ->addRecord(['name' => $directiveName, 'layout' => $result['layout']])
      ->setLayoutFormat('shallow')
      ->setFormatWhitespace(TRUE)
      ->execute();

    $result = Civi\Api4\Afform::get()
      ->addWhere('name', '=', $directiveName)
      ->setLayoutFormat('html')
      ->execute()
      ->first();

    $this->assertEquals($example['pretty'], $this->fudgeMarkup($result['layout']));
  }

  public function testAutoRequires() {
    $formName = 'mockPage';
    $this->createLoggedInUser();

    // The default mockPage has 1 explicit requirement + 2 automatic requirements.
    Civi\Api4\Afform::revert()->addWhere('name', '=', $formName)->execute();
    $angModule = Civi::service('angular')->getModule($formName);
    $this->assertEquals(['afCore', 'mockBespoke', 'mockBareFile', 'mockFoo'], $angModule['requires']);
    $storedRequires = Civi\Api4\Afform::get()->addWhere('name', '=', $formName)->addSelect('requires')->execute();
    $this->assertEquals(['mockBespoke'], $storedRequires[0]['requires']);

    // Knock down to 1 explicit + 1 automatic.
    Civi\Api4\Afform::update()
      ->addWhere('name', '=', $formName)
      ->setLayoutFormat('html')
      ->setValues(['layout' => '<div>The bare file says "<span mock-bare-file/>"</div>'])
      ->execute();
    $angModule = Civi::service('angular')->getModule($formName);
    $this->assertEquals(['afCore', 'mockBespoke', 'mockBareFile'], $angModule['requires']);
    $storedRequires = Civi\Api4\Afform::get()->addWhere('name', '=', $formName)->addSelect('requires')->execute();
    $this->assertEquals(['mockBespoke'], $storedRequires[0]['requires']);

    // Remove the last explict and implicit requirements.
    Civi\Api4\Afform::update()
      ->addWhere('name', '=', $formName)
      ->setLayoutFormat('html')
      ->setValues([
        'layout' => '<div>The file has nothing! <strong>NOTHING!</strong> <em>JUST RANTING!</em></div>',
        'requires' => [],
      ])
      ->execute();
    $angModule = Civi::service('angular')->getModule($formName);
    $this->assertEquals(['afCore'], $angModule['requires']);
    $storedRequires = Civi\Api4\Afform::get()->addWhere('name', '=', $formName)->addSelect('requires')->execute();
    $this->assertEquals([], $storedRequires[0]['requires']);

    Civi\Api4\Afform::revert()->addWhere('name', '=', $formName)->execute();
    $angModule = Civi::service('angular')->getModule($formName);
    $this->assertEquals(['afCore', 'mockBespoke', 'mockBareFile', 'mockFoo'], $angModule['requires']);
  }

}
