<?php

class CRM_Contribute_Exception_PastContributionPageException extends Exception {
  private $id;

  /**
   * @param string $message
   * @param int $id
   */
  public function __construct($message, $id) {
    parent::__construct(ts($message));
    $this->id = $id;
    CRM_Core_Error::debug_log_message('Access to contribution page with past end date attempted - page number ' . $id);
  }

  /**
   * Get Contribution page ID.
   *
   * @return int
   */
  public function getID() {
    return $this->id;
  }

}
