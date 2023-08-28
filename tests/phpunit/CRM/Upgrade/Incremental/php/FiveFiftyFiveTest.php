<?php

require_once 'CiviTest/CiviCaseTestCase.php';

/**
 * Class CRM_Upgrade_Incremental_php_FiveFiftyFiveTest
 * @group headless
 */
class CRM_Upgrade_Incremental_php_FiveFiftyFiveTest extends CiviCaseTestCase {

  public function testFixingPrintLabelUpgrade(): void {
    CRM_Core_DAO::executeQuery("INSERT INTO civicrm_print_label (title, name, description, label_format_name, label_type_id, is_default, is_reserved, is_active, data) VALUES ('Annual Conference Hanging Badge (Avery 5395) Busted', 'Annual_Conference_Hanging_Badge_busted', 'For our annual conference', 'Avery 5395', 1, 1, 1, 1, '" . '{"title":"Annual Conference Hanging Badge (Avery 5395)","label_format_name":"Avery 5395","description":"For our annual conference","token":{"1":"{event.title}","2":"{contact.display_name}","3":"{contact.current_employer}","4":"{event.start_date|crmDate:\"%B %E%f\"}"},"font_name":{"1":"dejavusans","2":"dejavusans","3":"dejavusans","4":"dejavusans"},"font_size":{"1":"9","2":"20","3":"15","4":"9"},"font_style":{"1":"","2":"","3":"","4":""},"text_alignment":{"1":"L","2":"C","3":"C","4":"R"},"barcode_type":"barcode","barcode_alignment":"R","image_1":"","image_2":"","is_default":"1","is_active":"1","is_reserved":"1","_qf_default":"Layout:next","_qf_Layout_refresh":"Save and Preview"}' . "'),('Annual Conference Hanging Badge (Avery 5395) Fixed', 'Annual_Conference_Hanging_Badge_fixed', 'For our annual conference', 'Avery 5395', 1, 1, 1, 1, '" . '{"title":"Annual Conference Hanging Badge (Avery 5395)","label_format_name":"Avery 5395","description":"For our annual conference","token":{"1":"{event.title}","2":"{contact.display_name}","3":"{contact.current_employer}","4":"{event.start_date|crmDate:\\\"%B %E%f\\\"}"},"font_name":{"1":"dejavusans","2":"dejavusans","3":"dejavusans","4":"dejavusans"},"font_size":{"1":"9","2":"20","3":"15","4":"9"},"font_style":{"1":"","2":"","3":"","4":""},"text_alignment":{"1":"L","2":"C","3":"C","4":"R"},"barcode_type":"barcode","barcode_alignment":"R","image_1":"","image_2":"","is_default":"1","is_active":"1","is_reserved":"1","_qf_default":"Layout:next","_qf_Layout_refresh":"Save and Preview"}' . "')");
    $this->assertEquals(1, CRM_Core_DAO::singleValueQuery("SELECT count(id) FROM civicrm_print_label WHERE data like '%" . 'crmDate:\"%B %E%f\"' . "%'"));
    $originalFixed = CRM_Core_DAO::singleValueQuery("SELECT data FROM civicrm_print_label WHERE name = 'Annual_Conference_Hanging_Badge_fixed'");
    $originalBusted = CRM_Core_DAO::singleValueQuery("SELECT data FROM civicrm_print_label WHERE name = 'Annual_Conference_Hanging_Badge_busted'");
    $this->assertEquals(NULL, json_decode($originalBusted));
    $this->assertFalse(NULL === json_decode($originalFixed));
    CRM_Upgrade_Incremental_php_FiveFiftyFive::fix_event_badge_upgrade();
    $fixedBusted = CRM_Core_DAO::singleValueQuery('SELECT data FROM civicrm_print_label WHERE name = \'Annual_Conference_Hanging_Badge_busted\'');
    $fixedFixed = CRM_Core_DAO::singleValueQuery("SELECT data FROM civicrm_print_label WHERE name = 'Annual_Conference_Hanging_Badge_fixed'");
    $this->assertEquals($originalFixed, $fixedFixed);
    $this->assertFalse(NULL === json_decode($fixedBusted));
  }

}
