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

interface ExampleDataInterface {

  /**
   * Get list of examples (summary-info only).
   *
   * This data should be static and amenable to caching.
   */
  public function getExamples(): iterable;

  /**
   * Fill-in full details of the example.
   *
   * @param array $example
   *   We start with summary-info for this example (name, title, tags).
   *   We may expand upon the example record, filling in dynamic(ish) properties like $example['data'].
   */
  public function build(array &$example): void;

}
