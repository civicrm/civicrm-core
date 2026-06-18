<?php
declare(strict_types = 1);

namespace Civi\ExtuserTest;

use CRM_ExtuserTest_ExtensionUtil as E;
use Civi\Core\Service\AutoService;

/**
 * @service extuser_list
 */
class ExtuserList extends AutoService {

  public function getFile(): string {
    // TODO: move to data folder
    return \Civi::paths()->getPath('[civicrm.private]/ext_users-' . hash_hmac('sha256', 'ext_users', CIVICRM_SIGN_KEYS) . '.json');
  }

  public function getAll(): array {
    $file = $this->getFile();
    if (!file_exists($file)) {
      return [];
    }
    return json_decode(file_get_contents($file), TRUE);
  }

  public function get(string $identifier): ?array {
    foreach ($this->getAll() as $row) {
      if ($row['uid'] === $identifier) {
        return $row;
      }
    }
    return NULL;
  }

  public function update(string $identifier, array $updates): void {
    $row = $this->get($identifier);
    $row = array_merge($row, $updates);
    $this->save($row);
  }

  public function save(array $row): void {
    $row['timestamp'] = \CRM_Utils_Time::date('c');

    $all = $this->getAll();
    $found = FALSE;

    foreach (array_keys($all) as $rowId) {
      if ($all[$rowId]['uid'] === $row['uid']) {
        $all[$rowId] = $row;
        $found = TRUE;
      }
    }
    if (!$found) {
      $all[] = $row;
    }

    $json = json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    \Civi::fs()->dumpFile($this->getFile(), $json);
  }

}
