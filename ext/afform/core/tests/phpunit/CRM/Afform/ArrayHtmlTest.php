<?php

/**
 * @group headless
 */
class CRM_Afform_ArrayHtmlTest extends \PHPUnit\Framework\TestCase {

  /**
   * dev/core#6938 - style/script content must not get wrapped in CDATA when round-tripped in shallow mode.
   */
  public function testStyleSurvivesShallowRoundTrip(): void {
    $html = '<af-form><style>.a{color:red}</style></af-form>';
    $converter = new CRM_Afform_ArrayHtml(FALSE);
    for ($i = 0; $i < 3; $i++) {
      $html = $converter->convertArraysToHtml($converter->convertHtmlToArray($html));
      $this->assertStringNotContainsString('CDATA', $html);
      $this->assertStringContainsString('<style>.a{color:red}</style>', $html);
    }
  }

}
