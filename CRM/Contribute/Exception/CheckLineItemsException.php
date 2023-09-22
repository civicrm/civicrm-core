<?php

/**
 * Class CRM_Contribute_Exception_CheckLineItemsException
 */
class CRM_Contribute_Exception_CheckLineItemsException extends CRM_Core_Exception {
  const LINE_ITEM_DIFFERRING_TOTAL_EXCEPTON_MSG = "Line item total doesn't match total amount.";

  public function __construct($message = self::LINE_ITEM_DIFFERRING_TOTAL_EXCEPTON_MSG, $error_code = 0, array $extraParams = [], $previous = NULL) {
    parent::__construct($message, $error_code, $extraParams, $previous);
  }

}
