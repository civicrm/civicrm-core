<?php
/**
 * @file
 * Integration with the Feeds module.
 */

/**
 * Implements hook_feeds_processor_targets_alter().
 *
 * @see FeedsNodeProcessor::getMappingTargets().
 */
function civicrm_contact_ref_feeds_processor_targets_alter(&$targets, $entity_type, $bundle_name) {
  foreach (field_info_instances($entity_type, $bundle_name) as $name => $instance) {
    $info = field_info_field($name);
    if (in_array($info['type'], array('civicrm_contact_ref_contact'))) {
      $targets[$name] = array(
        'name' => $instance['label'],
        'callback' => 'civicrm_contact_ref_feeds_set_target',
        'description' => t('The @label field of the node.', array('@label' => $instance['label'])),
        'optional_unique' => TRUE,
      );
    }
  }
}

/**
 * Callback function for mapping CiviCRM Contact Ref field.
 *
 * This function is invoked via hook_feeds_processor_targets_alter().
 * Here is where the actual mapping happens.
 *
 * @param $target
 *   the name of the field the user has decided to map to.
 * @param $value
 *   the value of the feed item element the user has picked as a source.
 */
function civicrm_contact_ref_feeds_set_target($source, $entity, $target, $value) {
  $value = is_array($value) ? $value : array($value);

  $info = field_info_field($target);

  // Iterate over all values.

  $field = isset($entity->$target) ? $entity->$target : array();
  // Allow for multiple mappings to the same target.
  $delta = 0;
  foreach ($value as $v) {
    if ($info['cardinality'] == $delta) {
      break;
    }

    if (is_object($v) && ($v instanceof FeedsElement)) {
      $v = $v->getValue();
    }

    if (is_numeric($v)) {
      $field['und'][$delta]['contact_id'] = $v;
      $delta++;
    }
  }

  $entity->{$target} = $field;
}
