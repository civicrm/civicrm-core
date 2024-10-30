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

use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;
use Civi\Api4\Utils\CoreUtil;

/**
 * @service
 * @internal
 */
class CaseCreationSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    $creator = new FieldSpec('creator_id', $spec->getEntity(), 'Integer');
    $creator->setTitle(ts('Case Creator'));
    $creator->setDescription('Contact who created the case.');
    $creator->setFkEntity('Contact');
    $creator->setInputType('EntityRef');
    $creator->setRequired(TRUE);
    $creator->setDefaultValue('user_contact_id');
    $spec->addFieldSpec($creator);

    $multiClient = \Civi::settings()->get('civicaseAllowMultipleClients');
    $contact = new FieldSpec('contact_id', $spec->getEntity(), $multiClient ? 'Array' : 'Integer');
    $contact->setTitle($multiClient ? ts('Case Clients') : ts('Case Client'));
    $contact->setDescription($multiClient ? 'Contact(s) who are case clients.' : 'The case client');
    $contact->setFkEntity('Contact');
    $contact->setInputType('EntityRef');
    $contact->setRequired(TRUE);
    $spec->addFieldSpec($contact);

    $location = new FieldSpec('location', $spec->getEntity(), 'String');
    $location->setTitle(ts('Activity Location'));
    $location->setInputType('Text');
    $location->setDescription('Open Case activity location.');
    $spec->addFieldSpec($location);

    $medium_id = new FieldSpec('medium_id', $spec->getEntity(), 'Integer');
    $medium_id->setTitle(ts('Activity Medium'));
    $medium_id->setDescription('Open Case activity medium.');
    $medium_id->setOptionsCallback([__CLASS__, 'getMediumOptions']);
    $suffixes = CoreUtil::getOptionValueFields('encounter_medium', 'name');
    $medium_id->setSuffixes($suffixes);
    $spec->addFieldSpec($medium_id);

    $duration = new FieldSpec('duration', $spec->getEntity(), 'Integer');
    $duration->setTitle(ts('Activity Duration'));
    $duration->setInputType('Number');
    $duration->setDescription('Open Case activity duration (minutes).');
    $spec->addFieldSpec($duration);

    // Set defualt value for status_id so it is not required
    $defaultStatus = \CRM_Core_DAO::singleValueQuery('SELECT value FROM civicrm_option_value
      WHERE is_default
        AND option_group_id = (SELECT id FROM civicrm_option_group WHERE name = "case_status")
      LIMIT 1');
    if ($defaultStatus) {
      $status = $spec->getFieldByName('status_id');
      $status->setDefaultValue((int) $defaultStatus);
    }

    $spec->getFieldByName('start_date')->setDefaultValue('now');
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $entity === 'Case' && $action === 'create';
  }

  public static function getMediumOptions($fieldName, $values, $loadOptions, $checkPermissions): array {
    return \Civi::entity('Activity')->getOptions('medium_id', $values, FALSE, $checkPermissions);
  }

}
