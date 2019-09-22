<?php

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

    $this->dropByPrefix('civicrm_value_mycontact');
    $this->cleanup($cleanup_params);
  }

}
