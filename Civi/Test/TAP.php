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

if (version_compare(\PHPUnit\Runner\Version::id(), '7.0.0', '<')) {
  class_alias('Civi\Test\TAPLegacy', 'Civi\Test\TAP');
}
elseif (version_compare(\PHPUnit\Runner\Version::id(), '9.0.0', '<')) {
  class_alias('Civi\Test\TAP7', 'Civi\Test\TAP');
}
else {
  class_alias('Civi\Test\TAP9', 'Civi\Test\TAP');
}
