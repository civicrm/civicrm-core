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

namespace Civi\Api4\Action\System;

/**
 * Reset paths using doSiteMove().
 *
 * @deprecated
 */
class ResetPaths extends \Civi\Api4\Generic\AbstractAction {

  public function _run(\Civi\Api4\Generic\Result $result) {
    \CRM_Core_Error::deprecatedWarning('System.ResetPaths and underlying doSiteMove is deprecated and due to be removed around 6.7');
    \CRM_Core_BAO_ConfigSetting::doSiteMove();
  }

}
