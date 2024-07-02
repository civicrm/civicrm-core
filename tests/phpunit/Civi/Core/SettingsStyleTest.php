<?php
namespace Civi\Core;

class SettingsStyleTest extends \CiviUnitTestCase {

  protected function setUp(): void {
    parent::setUp();
    $this->useTransaction(TRUE);
  }

  /**
   * Scan all known settings
   */
  public function testConformance(): void {
    $errors = [];
    $assert = function (string $setting, bool $condition, string $message) use (&$errors) {
      if (!$condition) {
        $errors[] = $setting . ': ' . $message;
      }
    };

    $validTypes = array_merge(
      // The list of 'type's are a bit of a mess. We'll prevent it from becoming more of a mess...
      array_keys(\CRM_Utils_Type::getValidTypes()),
      [\CRM_Utils_Type::T_STRING, \CRM_Utils_Type::T_BOOLEAN, \CRM_Utils_Type::T_INT],
      ['Array', 'Integer']
    );

    $all = SettingsMetadata::getMetadata();
    $this->assertTrue(count($all) > 10);
    foreach ($all as $key => $spec) {
      $add = $spec['add'] ?? 'UNKNOWN';
      $assert($key, preg_match(';^\d+\.\d+(\.\d+)?$;', $add), 'Should have well-formed \"add\" property');
      $isDomain = $spec['is_domain'] ?? NULL;
      $isContact = $spec['is_contact'] ?? NULL;
      $assert($key, !($isDomain && $isContact), 'Cannot be both is_domain and is_contact');
      $name = $spec['name'] ?? NULL;
      $assert($key, $key === $name, 'Should have matching name');
      $type = $spec['type'] ?? 'UNKNOWN';
      $assert($key, in_array($type, $validTypes), 'Should have known type. Found: ' . $type);
      if (version_compare($spec['add'], '5.53', '>=')) {
        $assert($key, preg_match(';^[a-z0-9]+(_[a-z0-9]+)+$;', $key), 'In 5.53+, names should use snake_case with a group/subsystem prefix.');
      }
      else {
        $assert($key, preg_match(';^[a-z][a-zA-Z0-9_]+$;', $key), 'In 4.1-5.52, names should use snake_case or lowerCamelCase.');
      }
    }
    $this->assertEquals([], $errors);
  }

}
