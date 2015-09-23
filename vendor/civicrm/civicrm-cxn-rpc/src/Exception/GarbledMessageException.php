<?php

/*
 * This file is part of the civicrm-cxn-rpc package.
 *
 * Copyright (c) CiviCRM LLC <info@civicrm.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this package.
 */

namespace Civi\Cxn\Rpc\Exception;

use Civi\Cxn\Rpc\Message\GarbledMessage;

class GarbledMessageException extends InvalidMessageException {

  private $garbledMessage;

  /**
   * @param GarbledMessage $garbledMessage
   */
  public function __construct($garbledMessage) {
    parent::__construct("Received garbled message");
    $this->garbledMessage = $garbledMessage;
  }

  /**
   * @return GarbledMessage
   */
  public function getGarbledMessage() {
    return $this->garbledMessage;
  }

  public function getData() {
    return $this->garbledMessage->getData();
  }

}
