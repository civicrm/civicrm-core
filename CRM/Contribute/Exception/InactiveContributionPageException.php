<?php
/**
 * Created by PhpStorm.
 * User: eileen
 * Date: 8/12/2014
 * Time: 10:33 AM
 */

class CRM_Contribute_Exception_InactiveContributionPageException extends Exception{
  private $id;
  public function __construct($message, $id) {
    parent::__construct(ts($message));
    $this->id = $id;
    CRM_Core_Error::debug_log_message('inactive contribution page access attempted - page number ' . $id);
  }

  public function getID() {
    return $this->id;
  }
}
