<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\Test;

/**
 * Trait for writing tests interacting with QuickForm Pages.
 */
trait PageTrait {
  /**
   * @var \Civi\Test\PageWrapper
   */
  private PageWrapper $page;

  public function getTestPage(string $pageName, array $urlParameters = []): PageWrapper {
    $this->page = new PageWrapper($pageName, $urlParameters);
    return $this->page;
  }

  /**
   * Assert that the page output contains the given string.
   *
   * @param string $string
   */
  protected function assertOutputContainsString(string $string): void {
    $this->assertStringContainsString($string, $this->page->getOutput());
  }

  /**
   * Assert a variable the page assigned to the template.
   *
   * @param string $name
   * @param mixed $expected
   */
  protected function assertPageTemplateVariable(string $name, $expected): void {
    $this->assertSame($expected, $this->page->getTemplateVariable($name));
  }

  /**
   * Get a variable the page assigned to the template.
   *
   * @param string $name
   *
   * @return mixed
   */
  protected function getPageTemplateVariable(string $name): mixed {
    return $this->page->getTemplateVariable($name);
  }

  /**
   * Assert that the page set a status message containing the given string.
   *
   * @param string $string
   */
  protected function assertPageStatusContainsString(string $string): void {
    $statusText = implode("\n", array_column($this->page->getStatusMessages(), 'text'));
    $this->assertStringContainsString($string, $statusText);
  }

  /**
   * Assert that the page output does not contain the given string.
   *
   * @param string $string
   */
  protected function assertOutputNotContainsString(string $string): void {
    $this->assertStringNotContainsString($string, $this->page->getOutput());
  }

}
