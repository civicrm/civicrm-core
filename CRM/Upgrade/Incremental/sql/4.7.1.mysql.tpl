{* file to handle db changes in 4.7.1 during upgrade *}
# CRM-17852 - Misspeled state names in Estonia/Lithuania

#Estonia

SET @EstoniaCountryId = (SELECT id FROM civicrm_country cc WHERE cc.name = 'Estonia');

UPDATE civicrm_state_province SET name = 'Harjumaa' where name='Harjumsa' AND country_id = @EstoniaCountryId;
UPDATE civicrm_state_province SET name = 'Hiiumaa' where name='Hitumea' AND country_id = @EstoniaCountryId;
UPDATE civicrm_state_province SET name = 'Ida-Virumaa' where name='Ida-Virumsa' AND country_id = @EstoniaCountryId;
UPDATE civicrm_state_province SET name = 'Jõgevamaa' where name='Jogevamsa' AND country_id = @EstoniaCountryId;
UPDATE civicrm_state_province SET name = 'Järvamaa' where name='Jarvamsa' AND country_id = @EstoniaCountryId;
UPDATE civicrm_state_province SET name = 'Läänemaa' where name='Lasnemsa' AND country_id = @EstoniaCountryId;
UPDATE civicrm_state_province SET name = 'Lääne-Virumaa' where name='Laane-Virumaa' AND country_id = @EstoniaCountryId;
UPDATE civicrm_state_province SET name = 'Põlvamaa' where name='Polvamea' AND country_id = @EstoniaCountryId;
UPDATE civicrm_state_province SET name = 'Pärnumaa' where name='Parnumsa' AND country_id = @EstoniaCountryId;
UPDATE civicrm_state_province SET name = 'Raplamaa' where name='Raplamsa' AND country_id = @EstoniaCountryId;
UPDATE civicrm_state_province SET name = 'Saaremaa' where name='Saaremsa' AND country_id = @EstoniaCountryId;
UPDATE civicrm_state_province SET name = 'Tartumaa' where name='Tartumsa' AND country_id = @EstoniaCountryId;
UPDATE civicrm_state_province SET name = 'Viljandimaa' where name='Viljandimsa' AND country_id = @EstoniaCountryId;
UPDATE civicrm_state_province SET name = 'Võrumaa' where name='Vorumaa' AND country_id = @EstoniaCountryId;

#Lithuania

SET @LithuaniaCountryId = (SELECT id FROM civicrm_country cc WHERE cc.name = 'Lithuania');

UPDATE civicrm_state_province SET name = 'Klaipėdos Apskritis' where name='Klaipedos Apskritis' AND country_id = @LithuaniaCountryId;
UPDATE civicrm_state_province SET name = 'Marijampolės Apskritis' where name='Marijampoles Apskritis' AND country_id = @LithuaniaCountryId;
UPDATE civicrm_state_province SET name = 'Panevėžio Apskritis' where name='Panevezio Apskritis' AND country_id = @LithuaniaCountryId;
UPDATE civicrm_state_province SET name = 'Šiaulių Apskritis' where name='Sisuliu Apskritis' AND country_id = @LithuaniaCountryId;
UPDATE civicrm_state_province SET name = 'Tauragės Apskritis' where name='Taurages Apskritis' AND country_id = @LithuaniaCountryId;
UPDATE civicrm_state_province SET name = 'Telšių Apskritis' where name='Telsiu Apskritis' AND country_id = @LithuaniaCountryId;
