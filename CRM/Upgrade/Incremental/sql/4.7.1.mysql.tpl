{* file to handle db changes in 4.7.1 during upgrade *}
# CRM-17852 - Misspeled state names in Estonia/Lithuania

#Estonia
UPDATE civicrm_state_province SET name = 'Harjumaa' where id = 2376;
UPDATE civicrm_state_province SET name = 'Hiiumaa' where id = 2377;
UPDATE civicrm_state_province SET name = 'Ida-Virumaa' where id = 2378;
UPDATE civicrm_state_province SET name = 'Jõgevamaa' where id = 2379;
UPDATE civicrm_state_province SET name = 'Järvamaa' where id = 2380;
UPDATE civicrm_state_province SET name = 'Läänemaa' where id = 2381;
UPDATE civicrm_state_province SET name = 'Lääne-Virumaa' where id = 2382;
UPDATE civicrm_state_province SET name = 'Põlvamaa' where id = 2383;
UPDATE civicrm_state_province SET name = 'Pärnumaa' where id = 2384;
UPDATE civicrm_state_province SET name = 'Raplamaa' where id = 2385;
UPDATE civicrm_state_province SET name = 'Saaremaa' where id = 2386;
UPDATE civicrm_state_province SET name = 'Tartumaa' where id = 2387;
UPDATE civicrm_state_province SET name = 'Viljandimaa' where id = 2389;
UPDATE civicrm_state_province SET name = 'Võrumaa' where id = 2390;

#Lithuania
UPDATE civicrm_state_province SET name = 'Klaipėdos Apskritis' where id = 3514;
UPDATE civicrm_state_province SET name = 'Marijampolės Apskritis' where id = 3515;
UPDATE civicrm_state_province SET name = 'Panevėžio Apskritis' where id = 3516;
UPDATE civicrm_state_province SET name = 'Šiaulių Apskritis' where id = 3517;
UPDATE civicrm_state_province SET name = 'Tauragės Apskritis' where id = 3518;
UPDATE civicrm_state_province SET name = 'Telšių Apskritis' where id = 3519;
