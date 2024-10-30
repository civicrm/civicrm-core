<?php

namespace Civi\Test;

/**
 * Class MailingTestTrait
 * @package Civi\Test
 *
 * This trait defines a number of helper functions for managing
 * test mailings.
 */
trait MailingTestTrait {

  /**
   * Helper function to create new mailing.
   *
   * @param array $params
   * @param string $identifier
   *
   * @return int
   */
  public function createMailing(array $params = [], string $identifier = 'default'): int {
    $params = array_merge([
      'subject' => 'maild' . rand(),
      'body_text' => 'bdkfhdskfhduew{domain.address}{action.optOutUrl}',
      'name' => 'mailing name' . rand(),
      'created_id' => 1,
    ], $params);

    $result = $this->callAPISuccess('Mailing', 'create', $params);
    $this->ids['Mailing'][$identifier] = (int) $result['id'];
    return (int) $result['id'];
  }

  /**
   * Helper function to delete mailing.
   * @param $id
   */
  public function deleteMailing($id) {
    $params = [
      'id' => $id,
    ];

    $this->callAPISuccess('Mailing', 'delete', $params);
  }

}
