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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * $Id$
 *
 */


namespace api\v4\Action;

use api\v4\UnitTestCase;
use api\v4\Traits\TableDropperTrait;

abstract class BaseCustomValueTest extends UnitTestCase {

  use \api\v4\Traits\OptionCleanupTrait {
    setUp as setUpOptionCleanup;
  }
  use TableDropperTrait;

  /**
   * Set up baseline for testing
   */
  public function setUp() {
    $this->setUpOptionCleanup();
    $cleanup_params = [
      'tablesToTruncate' => [
        'civicrm_custom_group',
        'civicrm_custom_field',
      ],
    ];

    $this->dropByPrefix('civicrm_value_my');
    $this->cleanup($cleanup_params);
  }

}
