<?php

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
