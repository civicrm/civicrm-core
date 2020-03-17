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


namespace Civi\Api4\Event\Subscriber;

use Civi\Api4\Generic\AbstractAction;

class ContributionPreSaveSubscriber extends Generic\PreSaveSubscriber {

  public function modify(&$record, AbstractAction $request) {
    // Required by Contribution BAO
    $record['skipCleanMoney'] = TRUE;
  }

  public function applies(AbstractAction $request) {
    return $request->getEntityName() === 'Contribution';
  }

}
