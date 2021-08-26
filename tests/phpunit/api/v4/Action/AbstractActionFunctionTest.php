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
 */

namespace api\v4\Action;

use api\v4\UnitTestCase;

/**
 * @group headless
 */
class AbstractActionFunctionTest extends UnitTestCase {

  public function testUndefinedParamException(): void {
    $this->expectException('API_Exception');
    $this->expectExceptionMessage('Unknown api parameter: getLanguage');
    \Civi\Api4\System::flush(FALSE)->getLanguage();
  }

}
