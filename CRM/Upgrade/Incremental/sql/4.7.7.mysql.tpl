{* file to handle db changes in 4.7.7 during upgrade *}
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

# CRM-18014 - Missspeled county names in Sweden

SET @SwedenCountryId = (SELECT id FROM civicrm_country cc WHERE cc.name = 'Sweden');

UPDATE civicrm_state_province SET name = 'Blekinge län' where name='Blekinge lan' AND country_id = @SwedenCountryId;
UPDATE civicrm_state_province SET name = 'Dalarnas län' where name='Dalarnas lan' AND country_id = @SwedenCountryId;
UPDATE civicrm_state_province SET name = 'Gävleborgs län' where name='Gavleborge lan' AND country_id = @SwedenCountryId;
UPDATE civicrm_state_province SET name = 'Gotlands län' where name='Gotlands lan' AND country_id = @SwedenCountryId;
UPDATE civicrm_state_province SET name = 'Hallands län' where name='Hallands lan' AND country_id = @SwedenCountryId;
UPDATE civicrm_state_province SET name = 'Jämtlands län' where name='Jamtlande lan' AND country_id = @SwedenCountryId;
UPDATE civicrm_state_province SET name = 'Jönköpings län' where name='Jonkopings lan' AND country_id = @SwedenCountryId;
UPDATE civicrm_state_province SET name = 'Kalmar län' where name='Kalmar lan' AND country_id = @SwedenCountryId;
UPDATE civicrm_state_province SET name = 'Kronobergs län' where name='Kronoberge lan' AND country_id = @SwedenCountryId;
UPDATE civicrm_state_province SET name = 'Norrbottens län' where name='Norrbottena lan' AND country_id = @SwedenCountryId;
UPDATE civicrm_state_province SET name = 'Örebro län' where name='Orebro lan' AND country_id = @SwedenCountryId;
UPDATE civicrm_state_province SET name = 'Östergötlands län' where name='Ostergotlands lan' AND country_id = @SwedenCountryId;
UPDATE civicrm_state_province SET name = 'Skåne län' where name='Skane lan' AND country_id = @SwedenCountryId;
UPDATE civicrm_state_province SET name = 'Södermanlands län' where name='Sodermanlands lan' AND country_id = @SwedenCountryId;
UPDATE civicrm_state_province SET name = 'Stockholms län' where name='Stockholms lan' AND country_id = @SwedenCountryId;
UPDATE civicrm_state_province SET name = 'Uppsala län' where name='Uppsala lan' AND country_id = @SwedenCountryId;
UPDATE civicrm_state_province SET name = 'Värmlands län' where name='Varmlanda lan' AND country_id = @SwedenCountryId;
UPDATE civicrm_state_province SET name = 'Västerbottens län' where name='Vasterbottens lan' AND country_id = @SwedenCountryId;
UPDATE civicrm_state_province SET name = 'Västernorrlands län' where name='Vasternorrlands lan' AND country_id = @SwedenCountryId;
UPDATE civicrm_state_province SET name = 'Västmanlands län' where name='Vastmanlanda lan' AND country_id = @SwedenCountryId;
UPDATE civicrm_state_province SET name = 'Västra Götalands län' where name='Vastra Gotalands lan' AND country_id = @SwedenCountryId;

{include file='../CRM/Upgrade/4.7.4.msg_template/civicrm_msg_template.tpl'}

-- CRM-18037 - update preferred mail format to set as default
UPDATE `civicrm_contact` SET `preferred_mail_format` = 'Both' WHERE `preferred_mail_format` IS NULL;

-- Fix weight interchange of `Extensions` and `Connections` navigation menu
SELECT @parent_id := id from `civicrm_navigation` where name = 'System Settings' AND domain_id = {$domainID};
UPDATE
  `civicrm_navigation` AS nav1
  JOIN `civicrm_navigation` AS nav2 ON
  nav1.name = 'Connections'
  AND nav2.name = 'Manage Extensions'
  AND nav2.has_separator = 1
  AND nav1.parent_id = @parent_id
  AND nav1.weight > nav2.weight
SET
  nav1.weight = nav2.weight,
  nav2.weight = nav1.weight;
