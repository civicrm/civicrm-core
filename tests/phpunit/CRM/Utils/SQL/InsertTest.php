<?php

/**
 * Class CRM_Utils_SQL_SelectTest
 * @group headless
 */
class CRM_Utils_SQL_InsertTest extends CiviUnitTestCase {

  public function testRow_twice() {
    $insert = CRM_Utils_SQL_Insert::into('foo')
      ->row(['first' => '1', 'second' => '2'])
      ->row(['second' => '2b', 'first' => '1b']);
    $expected = '
      INSERT INTO foo (`first`,`second`) VALUES
      ("1","2"),
      ("1b","2b")
    ';
    $this->assertLike($expected, $insert->toSQL());
  }

  public function testRows() {
    $insert = CRM_Utils_SQL_Insert::into('foo')
      ->row(['first' => '1', 'second' => '2'])
      ->rows([
        ['second' => '2b', 'first' => '1b'],
        ['first' => '1c', 'second' => '2c'],
      ])
      ->row(['second' => '2d', 'first' => '1d'])
      ->row(['first' => NULL, 'second' => '2e']);
    $expected = '
      INSERT INTO foo (`first`,`second`) VALUES
      ("1","2"),
      ("1b","2b"),
      ("1c","2c"),
      ("1d","2d"),
      (NULL,"2e")
    ';
    $this->assertLike($expected, $insert->toSQL());
  }

}
