<?php

/**
 * AfformPalette API Test
 * @group headless
 */
class api_v4_AfformPaletteTest extends api_v4_AfformTestCase {

  public function testGetPalette() {
    $r = Civi\Api4\AfformPalette::get()
      ->addWhere('id', '=', 'Parent:afl-name')
      ->execute();
    $this->assertEquals(1, $r->count());

    $r = Civi\Api4\AfformPalette::get()
      ->setLimit(10)
      ->execute();
    $this->assertTrue($r->count() > 1);
  }

}
