<?php

namespace Civi\Api4\Action\Afform;

use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\AfformSubmission;

/**
 * Class Submit
 * @package Civi\Api4\Action\Afform
 */
class SubmitDraft extends AbstractProcessor {

  /**
   * Submitted values
   * @var array
   * @required
   */
  protected $values;

  protected function processForm() {
    $cid = \CRM_Core_Session::getLoggedInContactID();
    if (!$cid) {
      throw new UnauthorizedException('Only authenticated users may save a draft.');
    }
    AfformSubmission::replace(FALSE)
      ->addWhere('contact_id', '=', $cid)
      ->addWhere('status_id:name', '=', 'Draft')
      ->addWhere('afform_name', '=', $this->_afform['name'])
      ->addRecord(['data' => $this->getValues()])
      ->execute();
    return [];
  }

  protected function loadEntities() {
    // Not needed when saving a draft
  }

}
