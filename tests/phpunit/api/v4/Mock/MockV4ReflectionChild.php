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


namespace api\v4\Mock;

/**
 * @inheritDoc
 */
class MockV4ReflectionChild extends MockV4ReflectionBase {
  /**
   * @var array
   * @inheritDoc
   *
   * In the child class, foo has been barred.
   */
  public $foo = ['bar' => 1];

}
