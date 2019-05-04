<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * Afform.Get API Test Case
 * This is a generic test class implemented with PHPUnit.
 * @group headless
 */
class api_v4_AfformTest extends api_v4_AfformTestCase {

  public function getBasicDirectives() {
    return [
      ['afformExamplepage', ['title' => '', 'description' => '', 'server_route' => 'civicrm/example-page']],
      ['fakelibBareFile', ['title' => '', 'description' => '']],
      ['fakelibFoo', ['title' => '', 'description' => '']],
    ];
  }

  /**
   * This takes the bundled `examplepage` and performs some API calls on it.
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

    $asHtml = '<strong>New text!</strong>';
    $asArray = ['#tag' => 'strong', '#children' => ['New text!']];

    $es[] = ['fakelibBareFile', 'html', $asHtml, 'array', $asArray];
    $es[] = ['fakelibBareFile', 'array', $asArray, 'html', $asHtml];
    $es[] = ['fakelibBareFile', 'html', $asHtml, 'html', $asHtml];
    $es[] = ['fakelibBareFile', 'array', $asArray, 'array', $asArray];

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
   * @dataProvider getFormatExamples
   */
  public function testUpdateAndGetFormat($directiveName, $updateFormat, $updateLayout, $readFormat, $readLayout) {
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

    $this->assertEquals($readLayout, $result[0]['layout']);

    Civi\Api4\Afform::revert()->addWhere('name', '=', $directiveName)->execute();
  }

}
