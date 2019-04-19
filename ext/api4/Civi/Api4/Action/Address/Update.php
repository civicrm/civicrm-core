<?php

namespace Civi\Api4\Action\Address;

use Civi\Api4\Generic\Result;

/**
 * @inheritDoc
 */
class Update extends \Civi\Api4\Generic\DAOUpdateAction {

  /**
   * Optional param to indicate you want the street_address field parsed into individual params
   *
   * @var bool
   */
  protected $streetParsing = TRUE;

  /**
   * Optional param to indicate you want to skip geocoding (useful when importing a lot of addresses at once, the job Geocode and Parse Addresses can execute this task after the import)
   *
   * @var bool
   */
  protected $skipGeocode = FALSE;

  /**
   * When true, apply various fixes to the address before insert.
   *
   * @var bool
   */
  protected $fixAddress = TRUE;

  /**
   * @inheritDoc
   */
  public function _run(Result $result) {
    $this->values['street_parsing'] = $this->streetParsing;
    $this->values['skip_geocode'] = $this->skipGeocode;
    $this->values['fix_address'] = $this->fixAddress;
    parent::_run($result);
  }

}
