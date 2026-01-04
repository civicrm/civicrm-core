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

namespace Civi\Api4\Result;

/**
 * Specialized APIv4 Result object for SearchDisplay::run
 *
 * @package Civi\Api4\Result
 */
class SearchDisplayRunResult extends \Civi\Api4\Generic\Result {

  /**
   * Editable columns
   * @var array|null
   */
  public $editable = [];

  /**
   * Contextual labels for use in page title
   * @var array
   */
  public $labels = [];

  /**
   * Rendered toolbar buttons
   * @var array|null
   */
  public $toolbar = NULL;

}
