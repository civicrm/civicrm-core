-- CRM-6148
   {include file='../CRM/Upgrade/3.1.5.msg_template/civicrm_msg_template.tpl'}

-- CRM-6156
-- Update the names of several existing Taiwan provinces
UPDATE civicrm_state_province SET name = 'Changhua County'  WHERE id = 4848;
UPDATE civicrm_state_province SET name = 'Chiayi County'    WHERE id = 4849;
UPDATE civicrm_state_province SET name = 'Hsinchu County'   WHERE id = 4850;
UPDATE civicrm_state_province SET name = 'Hualien County'   WHERE id = 4851;
UPDATE civicrm_state_province SET name = 'Ilan County'      WHERE id = 4852;
UPDATE civicrm_state_province SET name = 'Kaohsiung County' WHERE id = 4853;
UPDATE civicrm_state_province SET name = 'Miaoli County'    WHERE id = 4854;
UPDATE civicrm_state_province SET name = 'Nantou County'    WHERE id = 4855;
UPDATE civicrm_state_province SET name = 'Penghu County'    WHERE id = 4856;
UPDATE civicrm_state_province SET name = 'Pingtung County'  WHERE id = 4857;
UPDATE civicrm_state_province SET name = 'Taichung County'  WHERE id = 4858;
UPDATE civicrm_state_province SET name = 'Tainan County'    WHERE id = 4859;
UPDATE civicrm_state_province SET name = 'Taipei County'    WHERE id = 4860;
UPDATE civicrm_state_province SET name = 'Taitung County'   WHERE id = 4861;
UPDATE civicrm_state_province SET name = 'Taoyuan County'   WHERE id = 4862;
UPDATE civicrm_state_province SET name = 'Yunlin Conuty'    WHERE id = 4863;
UPDATE civicrm_state_province SET name = 'Keelung City'     WHERE id = 4864;

-- Create additional Taiwan provinces
SELECT @country_id := id from civicrm_country where name = 'Taiwan';
INSERT IGNORE INTO civicrm_state_province ( country_id, abbreviation, name ) VALUES
( @country_id, 'TXG', 'Taichung City'  ),
( @country_id, 'KHH', 'Kaohsiung City' ),
( @country_id, 'TPE', 'Taipei City'    ),
( @country_id, 'CYI', 'Chiayi City'    ),
( @country_id, 'HSZ', 'Hsinchu City'   ),
( @country_id, 'TNN', 'Tainan City'    );

