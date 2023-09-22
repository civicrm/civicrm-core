<?php
namespace Civi\Api4\Action\OAuthClient;

class Update extends \Civi\Api4\Generic\DAOUpdateAction {

  /**
   * @inheritdoc
   */
  protected function formatWriteValues(&$record) {
    $result = parent::formatWriteValues($record);

    // Hrm, parent doesn't validate <callback> PC's by default.
    if (isset($this->values['provider'])) {
      $ps = \CRM_OAuth_BAO_OAuthClient::getProviders();
      if (!isset($ps[$this->values['provider']])) {
        throw new \CRM_Core_Exception("Invalid provider name: " . $this->values['provider']);
      }
    }

    return $result;
  }

}
