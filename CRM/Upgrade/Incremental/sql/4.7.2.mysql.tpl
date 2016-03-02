{* file to handle db changes in 4.7.2 during upgrade *}
# CRM-18014 - Missspeled county names in Sweden

#Sweden

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

