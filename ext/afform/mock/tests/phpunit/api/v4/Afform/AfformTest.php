<?php
namespace api\v4\Afform;

use Civi\Api4\Afform;
use Civi\Api4\Dashboard;
use Civi\Test\TransactionalInterface;

/**
 * Afform.Get API Test Case
 * This is a generic test class implemented with PHPUnit.
 * @group headless
 */
class AfformTest extends AfformTestCase implements TransactionalInterface {

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

  public static function getBasicDirectives() {
    $directives = [
      ['mockPage', ['title' => '', 'description' => '', 'server_route' => 'civicrm/mock-page', 'permission' => ['access Foobar'], 'placement' => ['dashboard_dashlet'], 'submit_enabled' => TRUE]],
      ['mockBareFile', ['title' => '', 'description' => '', 'permission' => ['access CiviCRM'], 'placement' => [], 'submit_enabled' => TRUE]],
      ['mockFoo', ['title' => '', 'description' => '', 'permission' => ['access CiviCRM']], 'submit_enabled' => TRUE],
      ['mock-weird-name', ['title' => 'Weird Name', 'description' => '', 'permission' => ['access CiviCRM']], 'submit_enabled' => TRUE],
    ];
    // Provide a meaningful index for test data set
    return array_column($directives, NULL, 0);
  }

  /**
   * This takes the bundled `example-page` and performs some API calls on it.
   *
   * @param string $formName
   *   The symbolic name of the form.
   * @param array $originalMetadata
   * @dataProvider getBasicDirectives
   */
  public function testGetUpdateRevert($formName, $originalMetadata): void {
    $get = function($arr, $key) {
      return $arr[$key] ?? NULL;
    };

    $checkDashlet = function($afform) use ($formName) {
      $dashlet = Dashboard::get(FALSE)
        ->addWhere('name', '=', $formName)
        ->execute();
      if (in_array('dashboard_dashlet', $afform['placement'] ?? [], TRUE)) {
        $this->assertCount(1, $dashlet);
      }
      else {
        $this->assertCount(0, $dashlet);
      }
    };

    Afform::revert()->addWhere('name', '=', $formName)->execute();

    $message = 'The initial Afform.get should return default data';
    $result = Afform::get()
      ->addSelect('*', 'has_base', 'has_local', 'base_module')
      ->addWhere('name', '=', $formName)->execute();
    $this->assertEquals($formName, $result[0]['name'], $message);
    $this->assertEquals($get($originalMetadata, 'title'), $get($result[0], 'title'), $message);
    $this->assertEquals($get($originalMetadata, 'description'), $get($result[0], 'description'), $message);
    $this->assertEquals($get($originalMetadata, 'server_route'), $get($result[0], 'server_route'), $message);
    $this->assertEquals($get($originalMetadata, 'placement') ?? [], $get($result[0], 'placement'), $message);
    $this->assertEquals($get($originalMetadata, 'permission'), $get($result[0], 'permission'), $message);
    $this->assertTrue(is_array($result[0]['layout']), $message);
    $this->assertEquals(TRUE, $get($result[0], 'has_base'), $message);
    $this->assertEquals(FALSE, $get($result[0], 'has_local'), $message);
    $this->assertEquals('org.civicrm.afform-mock', $get($result[0], 'base_module'), $message);
    $checkDashlet($originalMetadata);

    $message = 'After updating with Afform.create, the revised data should be returned';
    $result = Afform::update()
      ->addWhere('name', '=', $formName)
      ->addValue('description', 'The temporary description')
      ->addValue('permission', ['access foo', 'access bar'])
      ->addValue('placement', empty($originalMetadata['placement']) ? ['dashboard_dashlet'] : [])
      ->execute();
    $this->assertEquals($formName, $result[0]['name'], $message);
    $this->assertEquals('The temporary description', $result[0]['description'], $message);

    $message = 'After updating, the Afform.get API should return blended data';
    $result = Afform::get()
      ->addSelect('*', 'has_base', 'has_local', 'base_module')
      ->addWhere('name', '=', $formName)->execute();
    $this->assertEquals($formName, $result[0]['name'], $message);
    $this->assertEquals($get($originalMetadata, 'title'), $get($result[0], 'title'), $message);
    $this->assertEquals('The temporary description', $get($result[0], 'description'), $message);
    $this->assertNotEquals($get($originalMetadata, 'placement'), $get($result[0], 'placement'), $message);
    $this->assertEquals($get($originalMetadata, 'server_route'), $get($result[0], 'server_route'), $message);
    $this->assertEquals(['access foo', 'access bar'], $get($result[0], 'permission'), $message);
    $this->assertTrue(is_array($result[0]['layout']), $message);
    $this->assertEquals(TRUE, $get($result[0], 'has_base'), $message);
    $this->assertEquals(TRUE, $get($result[0], 'has_local'), $message);
    $this->assertEquals('org.civicrm.afform-mock', $get($result[0], 'base_module'), $message);
    $checkDashlet($result[0]);

    Afform::revert()->addWhere('name', '=', $formName)->execute();
    $message = 'After reverting, the final Afform.get should return default data';
    $result = Afform::get()
      ->addSelect('*', 'has_base', 'has_local', 'base_module')
      ->addWhere('name', '=', $formName)->execute();
    $this->assertEquals($formName, $result[0]['name'], $message);
    $this->assertEquals($get($originalMetadata, 'title'), $get($result[0], 'title'), $message);
    $this->assertEquals($get($originalMetadata, 'description'), $get($result[0], 'description'), $message);
    $this->assertEquals($get($originalMetadata, 'server_route'), $get($result[0], 'server_route'), $message);
    $this->assertEquals($get($originalMetadata, 'permission'), $get($result[0], 'permission'), $message);
    $this->assertEquals($get($originalMetadata, 'placement') ?? [], $get($result[0], 'placement'), $message);
    $this->assertTrue(is_array($result[0]['layout']), $message);
    $this->assertEquals(TRUE, $get($result[0], 'has_base'), $message);
    $this->assertEquals(FALSE, $get($result[0], 'has_local'), $message);
    $this->assertEquals('org.civicrm.afform-mock', $get($result[0], 'base_module'), $message);

    $checkDashlet($originalMetadata);
  }

  public static function getFormatExamples() {
    $ex = [];
    $formats = ['html', 'shallow', 'deep'];
    foreach (glob(__DIR__ . '/../formatExamples/*.php') as $exampleFile) {
      $example = require $exampleFile;
      if (isset($example['deep'])) {
        foreach ($formats as $updateFormat) {
          foreach ($formats as $readFormat) {
            $key = basename($exampleFile, '.php') . '-' . $updateFormat . '-' . $readFormat;
            $ex[$key] = ['mockBareFile', $updateFormat, $example[$updateFormat], $readFormat, $example[$readFormat], $exampleFile];
          }
        }
      }
    }
    return $ex;
  }

  /**
   * In this test, we receive a layout
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
  public function testBasicConvert($formName, $updateFormat, $updateLayout, $readFormat, $readLayout, $exampleName): void {
    $actual = Afform::convert()->setLayout($updateLayout)
      ->setFrom($updateFormat)
      ->setTo($readFormat)
      ->execute();

    $cb = function($m) {
      return '<' . rtrim($m[1]) . '/>';
    };
    $norm = function($layout) use ($cb, &$norm) {
      if (is_string($layout)) {
        return preg_replace_callback(';<((br|img)[^>]*)/>;', $cb, $layout);
      }
      elseif (is_array($layout)) {
        foreach ($layout as &$item) {
          $item = $norm($item);
        }
      }
    };

    $this->assertEquals($norm($readLayout), $norm($actual->single()['layout']), "Based on \"$exampleName\", writing content as \"$updateFormat\" and reading back as \"$readFormat\".");
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
  public function testUpdateAndGetFormat($formName, $updateFormat, $updateLayout, $readFormat, $readLayout, $exampleName): void {
    Afform::revert()->addWhere('name', '=', $formName)->execute();

    Afform::update()
      ->addWhere('name', '=', $formName)
      ->setLayoutFormat($updateFormat)
      ->setValues(['layout' => $updateLayout])
      ->execute();

    $result = Afform::get()
      ->addWhere('name', '=', $formName)
      ->setLayoutFormat($readFormat)
      ->execute();

    $this->assertEquals($readLayout, $this->fudgeMarkup($result[0]['layout']), "Based on \"$exampleName\", writing content as \"$updateFormat\" and reading back as \"$readFormat\".");

    Afform::revert()->addWhere('name', '=', $formName)->execute();
  }

  public static function getWhitespaceExamples() {
    $ex = [];
    foreach (glob(__DIR__ . '/../formatExamples/*.php') as $exampleFile) {
      $example = require $exampleFile;
      if (isset($example['pretty'])) {
        $ex[basename($exampleFile, '.php')] = ['mockBareFile', $example, $exampleFile];
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
  public function testWhitespaceFormat($directiveName, $example, $exampleName): void {
    Afform::save()
      ->addRecord(['name' => $directiveName, 'layout' => $example['html']])
      ->setLayoutFormat('html')
      ->execute();

    $result = Afform::get()
      ->addWhere('name', '=', $directiveName)
      ->setLayoutFormat('shallow')
      ->setFormatWhitespace(TRUE)
      ->execute()
      ->first();

    $this->assertEquals($example['stripped'] ?? $example['shallow'], $this->fudgeMarkup($result['layout']));

    Afform::save()
      ->addRecord(['name' => $directiveName, 'layout' => $result['layout']])
      ->setLayoutFormat('shallow')
      ->setFormatWhitespace(TRUE)
      ->execute();

    $result = Afform::get()
      ->addWhere('name', '=', $directiveName)
      ->setLayoutFormat('html')
      ->execute()
      ->first();

    $this->assertEquals($example['pretty'], $this->fudgeMarkup($result['layout']));
  }

  public function testAutoRequires(): void {
    $formName = 'mockPage';
    $this->createLoggedInUser();

    // The default mockPage has 1 explicit requirement + 2 automatic requirements.
    Afform::revert()->addWhere('name', '=', $formName)->execute();
    $angModule = \Civi::service('angular')->getModule($formName);
    sort($angModule['requires']);
    $storedRequires = Afform::get()->addWhere('name', '=', $formName)->addSelect('requires')->execute();
    $this->assertEquals(['afCore', 'mockBareFile', 'mockBespoke', 'mockFoo'], $angModule['requires']);
    $this->assertEquals(['mockBespoke'], $storedRequires[0]['requires']);

    // Knock down to 1 explicit + 1 automatic.
    Afform::update()
      ->addWhere('name', '=', $formName)
      ->setLayoutFormat('html')
      ->setValues(['layout' => '<div>The bare file says "<mock-bare-file/>"</div>'])
      ->execute();
    $angModule = \Civi::service('angular')->getModule($formName);
    sort($angModule['requires']);
    $storedRequires = Afform::get()->addWhere('name', '=', $formName)->addSelect('requires')->execute();
    $this->assertEquals(['afCore', 'mockBareFile', 'mockBespoke'], $angModule['requires']);
    $this->assertEquals(['mockBespoke'], $storedRequires[0]['requires']);

    // Remove the last explict and implicit requirements.
    Afform::update()
      ->addWhere('name', '=', $formName)
      ->setLayoutFormat('html')
      ->setValues([
        'layout' => '<div>The file has nothing! <strong>NOTHING!</strong> <em>JUST RANTING!</em></div>',
        'requires' => [],
      ])
      ->execute();
    $angModule = \Civi::service('angular')->getModule($formName);
    $this->assertEquals(['afCore'], $angModule['requires']);
    $storedRequires = Afform::get()->addWhere('name', '=', $formName)->addSelect('requires')->execute();
    $this->assertEquals([], $storedRequires[0]['requires']);

    Afform::revert()->addWhere('name', '=', $formName)->execute();
    $angModule = \Civi::service('angular')->getModule($formName);
    sort($angModule['requires']);
    $this->assertEquals(['afCore', 'mockBareFile', 'mockBespoke', 'mockFoo'], $angModule['requires']);
  }

}
