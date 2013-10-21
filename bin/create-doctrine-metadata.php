#!/usr/bin/env php
<?php

require 'config/bootstrap.php';

use Doctrine\Common\Inflector\Inflector;
use Doctrine\ORM\Mapping\Driver\DatabaseDriver;
use Doctrine\ORM\Tools\DisconnectedClassMetadataFactory;
use Doctrine\ORM\Tools\EntityGenerator;
use Doctrine\ORM\Tools\Export\ClassMetadataExporter;

$name_fixes = array(
  array('/a_c_l/', 'acl'),
  array('/u_r_l/', 'url'),
  array('/p_c_p/', 'pcp'),
  array('/u_f_/', 'uf_'),
  array('/i_m/', 'im'),
  array('/i_d/', 'id'),
);

$table_map = array(
  'civicrm_acl' => 'ACL\ACL',
  'civicrm_acl_cache' => 'ACL\Cache',
  'civicrm_acl_contact_cache' => 'ACL\ContactCache',
  'civicrm_acl_entity_role' => 'ACL\EntityRole',
  'civicrm_action_log' => 'Core\ActionLog',
  'civicrm_action_mapping' => 'Core\ActionMapping',
  'civicrm_action_schedule' => 'Core\ActionSchedule',
  'civicrm_activity' => 'Activity\Activity',
  'civicrm_activity_contact' => 'Activity\Contact',
  'civicrm_address' => 'Core\Address',
  'civicrm_address_format' => 'Core\AddressFormat',
  'civicrm_batch' => 'Batch\Batch',
  'civicrm_cache' => 'Core\Cache',
  'civicrm_campaign' => 'Campaign\Campaign',
  'civicrm_campaign_group' => 'Campaign\CampaignGroup',
  'civicrm_case' => 'CCase\CCase',
  'civicrm_case_activity' => 'CCase\Activity',
  'civicrm_case_contact' => 'CCase\Contact',
  'civicrm_component' => 'Core\Component',
  'civicrm_contact' => 'Contact\Contact',
  'civicrm_contact_type' => 'Contact\Type',
  'civicrm_contribution' => 'Contribute\Contribution',
  'civicrm_contribution_page' => 'Contribute\ContributionPage',
  'civicrm_contribution_product' => 'Contribute\ContributionProduct',
  'civicrm_contribution_recur' => 'Contribute\ContributionRecur',
  'civicrm_contribution_soft' => 'Contribute\ContributionSoft',
  'civicrm_contribution_widget' => 'Contribut\ContributionWidget',
  'civicrm_country' => 'Core\Country',
  'civicrm_county' => 'Core\County',
  'civicrm_currency' => 'Financial\Currency',
  'civicrm_custom_field' => 'Core\CustomField',
  'civicrm_custom_group' => 'Core\CustomGroup',
  'civicrm_dashboard' => 'Core\Dashboard',
  'civicrm_dashboard_contact' => 'Contact\DashboardContact',
  'civicrm_dedupe_exception' => 'Dedupe\Exception',
  'civicrm_dedupe_rule' => 'Dedupe\Rule',
  'civicrm_dedupe_rule_group' => 'Dedupe\RuleGroup',
  'civicrm_discount' => 'Core\Discount',
  'civicrm_domain' => 'Core\Domain',
  'civicrm_email' => 'Core\Email',
  'civicrm_entity_batch' => 'Batch\EntityBatch',
  'civicrm_entity_file' => 'Core\EntityFile',
  'civicrm_entity_financial_account' => 'Financial\EntityFinancialAccount',
  'civicrm_entity_financial_trxn' => 'Financial\EntityFinancialTrxn',
  'civicrm_entity_tag' => 'Core\EntityTag',
  'civicrm_event' => 'Event\Event',
  'civicrm_event_carts' => 'Event\Cart',
  'civicrm_events_in_carts' => 'Event\EventInCart',
  'civicrm_extension' => 'Core\Extension',
  'civicrm_file' => 'Core\File',
  'civicrm_financial_account' => 'Financial\Account',
  'civicrm_financial_item' => 'Financial\Item',
  'civicrm_financial_trxn' => 'Financial\Trxn',
  'civicrm_financial_type' => 'Financial\Type',
  'civicrm_grant' => 'Grant\Grant',
  'civicrm_group' => 'Contact\Group',
  'civicrm_group_contact' => 'Contact\GroupContact',
  'civicrm_group_contact_cache' => 'Contact\GroupContactCache',
  'civicrm_group_nesting' => 'Contact\GroupNesting',
  'civicrm_group_organization' => 'Contact\GroupOrganization',
  'civicrm_im' => 'Core\IM',
  'civicrm_job' => 'Core\Job',
  'civicrm_job_log' => 'Core\JobLog',
  'civicrm_line_item' => 'Price\LineItem',
  'civicrm_loc_block' => 'Core\LocBlock',
  'civicrm_location_type' => 'Core\LocationType',
  'civicrm_log' => 'Core\Log',
  'civicrm_mail_settings' => 'Core\MailSettings',
  'civicrm_mailing' => 'Mailing\Mailing',
  'civicrm_mailing_bounce_pattern' => 'Mailing\BouncePattern',
  'civicrm_mailing_bounce_type' => 'Mailing\BounceType',
  'civicrm_mailing_component' => 'Mailing\Component',
  'civicrm_mailing_event_bounce' => 'Mailing\Event\Bounce',
  'civicrm_mailing_event_confirm' => 'Mailing\Event\Confirm',
  'civicrm_mailing_event_delivered' => 'Mailing\Event\Delivered',
  'civicrm_mailing_event_forward' => 'Mailing\Event\Forward',
  'civicrm_mailing_event_opened' => 'Mailing\Event\Opened',
  'civicrm_mailing_event_queue' => 'Mailing\Event\Queue',
  'civicrm_mailing_event_reply' => 'Mailing\Event\Reply',
  'civicrm_mailing_event_subscribe' => 'Mailing\Event\Subscribe',
  'civicrm_mailing_event_trackable_url_open' => 'Mailing\Event\TrackableURLOpen',
  'civicrm_mailing_event_unsubscribe' => 'Mailing\Event\Unsubscribe',
  'civicrm_mailing_group' => 'Mailing\Group',
  'civicrm_mailing_job' => 'Mailing\Job',
  'civicrm_mailing_recipients' => 'Mailing\Recipients',
  'civicrm_mailing_spool' => 'Mailing\Spool',
  'civicrm_mailing_trackable_url' => 'Mailing\TrackableURL',
  'civicrm_managed' => 'Core\Managed',
  'civicrm_mapping' => 'Core\Mapping',
  'civicrm_mapping_field' => 'Core\MappingField',
  'civicrm_membership' => 'Member\Membership',
  'civicrm_membership_block' => 'Member\MembershipBlock',
  'civicrm_membership_log' => 'Member\MembershipLog',
  'civicrm_membership_payment' => 'Member\MembershipPayment',
  'civicrm_membership_status' => 'Member\MembershipStatus',
  'civicrm_membership_type' => 'Member\MembershipType',
  'civicrm_menu' => 'Core\Menu',
  'civicrm_msg_template' => 'Core\MessageTemplate',
  'civicrm_navigation' => 'Core\Navigation',
  'civicrm_note' => 'Core\Note',
  'civicrm_openid' => 'Core\OpenID',
  'civicrm_option_group' => 'Core\OptionGroup',
  'civicrm_option_value' => 'Core\OptionValue',
  'civicrm_participant' => 'Event\Participant',
  'civicrm_participant_payment' => 'Event\ParticipantPayment',
  'civicrm_participant_status_type' => 'Event\ParticipantStatusType',
  'civicrm_payment_processor' => 'Financial\PaymentProcessor',
  'civicrm_payment_processor_type' => 'Financial\PaymentProcessorType',
  'civicrm_pcp' => 'PCP\PCP',
  'civicrm_pcp_block' => 'PCP\PCPBlock',
  'civicrm_persistent' => 'Core\Persistent',
  'civicrm_phone' => 'Core\Phone',
  'civicrm_pledge' => 'Pledge\Pledge',
  'civicrm_pledge_block' => 'Pledge\Block',
  'civicrm_pledge_payment' => 'Pledge\Payment',
  'civicrm_preferences_date' => 'Core\PreferencesDate',
  'civicrm_premiums' => 'Contribute\Premium',
  'civicrm_premiums_product' => 'Contribute\PremiumsProduct',
  'civicrm_prevnext_cache' => 'Core\PrevNextCache',
  'civicrm_price_field' => 'Price\Field',
  'civicrm_price_field_value' => 'Price\FieldValue',
  'civicrm_price_set' => 'Price\Set',
  'civicrm_price_set_entity' => 'Price\SetEntity',
  'civicrm_print_label' => 'Core\PrintLabel',
  'civicrm_product' => 'Contribute\Product',
  'civicrm_queue_item' => 'Queue\QueueItem',
  'civicrm_relationship' => 'Contact\Relationship',
  'civicrm_relationship_type' => 'Contact\RelationshipType',
  'civicrm_report_instance' => 'Report\ReportInstance',
  'civicrm_saved_search' => 'Contact\SavedSearch',
  'civicrm_setting' => 'Core\Setting',
  'civicrm_sms_provider' => 'SMS\Provider',
  'civicrm_state_province' => 'Core\StateProvince',
  'civicrm_subscription_history' => 'Contact\SubscriptionHistory',
  'civicrm_survey' => 'Campaign\Survey',
  'civicrm_tag' => 'Core\Tag',
  'civicrm_tell_friend' => 'Friend\Friend',
  'civicrm_timezone' => 'Core\Timezone',
  'civicrm_uf_field' => 'Core\UFField',
  'civicrm_uf_group' => 'Core\UFGroup',
  'civicrm_uf_join' => 'Core\UFJoin',
  'civicrm_uf_match' => 'Core\UFMatch',
  'civicrm_website' => 'Core\Website',
  'civicrm_word_replacement' => 'Core\WordReplacement',
  'civicrm_worldregion' => 'Core\Worldregion',
);
$civicrm_base_path = dirname(__DIR__);

$entity_manager = CRM_DB_EntityManager::singleton();
$platform = $entity_manager->getConnection()->getDatabasePlatform();
$platform->registerDoctrineTypeMapping('enum', 'string');
$database_driver = new DatabaseDriver($entity_manager->getConnection()->getSchemaManager());
$entity_manager->getConfiguration()->setMetadataDriverImpl($database_driver);
$database_driver->setNamespace('Civi\\');
foreach ($table_map as $table_name => $class_name) {
  $database_driver->setClassNameForTable($table_name, $class_name);
}
$class_metadata_factory = new DisconnectedClassMetadataFactory();
$class_metadata_factory->setEntityManager($entity_manager);
$metadata = $class_metadata_factory->getAllMetadata();
$class_metadata_exporter = new ClassMetadataExporter();
$exporter = $class_metadata_exporter->getExporter('annotation', 'src');
$entity_generator = new EntityGenerator();
$exporter->setEntityGenerator($entity_generator);
$entity_generator->setClassToExtend('Civi\Core\Entity');
$output = fopen('php://stdout', 'w');
if (count($metadata)) {
  foreach ($metadata as $class) {
    fwrite($output, sprintf("Processing entity \"%s\"\n", $class->name));
  }
  $exporter->setMetadata($metadata);
  $exporter->export();
  fwrite($output, PHP_EOL . sprintf('Exporting "%s" mapping information to "%s"', 'annotation', 'src') . "\n");
} else {
  fwrite($output, "No Metadata Classes to process.\n");
}
