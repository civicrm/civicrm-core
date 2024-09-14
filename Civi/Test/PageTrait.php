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

use Civi\Civi\Test\PageWrapper;

/**
 * Trait for writing tests interacting with QuickForm Pages.
 */
trait PageTrait {
  /**
   * @var \Civi\Civi\Test\PageWrapper
   */
  private PageWrapper $page;

  public function getTestPage(string $pageName, array $urlParameters = []): PageWrapper {
    $this->page = new PageWrapper($pageName, $urlParameters);
    return $this->page;
  }

  /**
   * Assert that the status set does not contain the given string..
   *
   * @param string $string
   */
  protected function assertOutputNotContainsString(string $string): void {
    $this->assertStringNotContainsString($string, $this->page->getOutput());
  }

}
