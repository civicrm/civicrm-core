<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */


namespace Civi\Api4\Service\Spec\Provider;

use Civi\Api4\Service\Spec\RequestSpec;

/**
 * @service
 * @internal
 */
class UserJobSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    $spec->getFieldByName('metadata')->addOutputFormatter([__CLASS__, 'formatMetadata']);
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $entity === 'UserJob';
  }

  public static function formatMetadata(&$metadata): void {
    if (is_string($metadata)) {
      $metadata = json_decode($metadata, TRUE);
    }
    if (!empty($metadata['entity_configuration']) && is_array($metadata['entity_configuration'])) {
      foreach ($metadata['entity_configuration'] as &$entityConfig) {
        // Backward-compat for single-valued dedupe_rule (now expects an array)
        if (isset($entityConfig['dedupe_rule']) && !is_array($entityConfig['dedupe_rule'])) {
          // Backward-compat for rule id provided instead of name
          if (is_numeric($entityConfig['dedupe_rule'])) {
            $entityConfig['dedupe_rule'] = \CRM_Dedupe_BAO_DedupeRuleGroup::getDbVal('name', $entityConfig['dedupe_rule']);
          }
          $entityConfig['dedupe_rule'] = [$entityConfig['dedupe_rule']];
        }
      }
    }
  }

}
