<<<<<<< HEAD
{* file to handle db changes in 4.6.alpha3 during upgrade *}
-- update permission for editing message templates (CRM-15819)

SELECT @messages_menu_id := id FROM civicrm_navigation WHERE name = 'Mailings';

UPDATE `civicrm_navigation` 
SET `permission` = 'edit message templates'
WHERE `parent_id` = @messages_menu_id
AND name = 'Message Templates';
=======
{* CRM-16846 - This file may have been accidentally skipped and so is conditionally re-run during 4.6.6 upgrade *}
>>>>>>> 650ff6351383992ec77abface9b7f121f16ae07e

-- Use proper names for Slovenian municipalities
UPDATE `civicrm_state_province` SET `name` = (N'Ajdovščina') WHERE `id` = 4383;
UPDATE `civicrm_state_province` SET `name` = (N'Braslovče') WHERE `id` = 4392;
UPDATE `civicrm_state_province` SET `name` = (N'Brežice') WHERE `id` = 4395;
UPDATE `civicrm_state_province` SET `name` = (N'Črenšovci') WHERE `id` = 4402;
UPDATE `civicrm_state_province` SET `name` = (N'Črna na Koroškem') WHERE `id` = 4403;
UPDATE `civicrm_state_province` SET `name` = (N'Črnomelj') WHERE `id` = 4404;
UPDATE `civicrm_state_province` SET `name` = (N'Divača') WHERE `id` = 4406;
UPDATE `civicrm_state_province` SET `name` = (N'Domžale') WHERE `id` = 4414;
UPDATE `civicrm_state_province` SET `name` = (N'Gorišnica') WHERE `id` = 4419;
UPDATE `civicrm_state_province` SET `name` = (N'Hoče-Slivnica') WHERE `id` = 4426;
UPDATE `civicrm_state_province` SET `name` = (N'Hodoš') WHERE `id` = 4427;
UPDATE `civicrm_state_province` SET `name` = (N'Horjul') WHERE `id` = 4428;
UPDATE `civicrm_state_province` SET `name` = (N'Ilirska Bistrica') WHERE `id` = 4433;
UPDATE `civicrm_state_province` SET `name` = (N'Ivančna Gorica') WHERE `id` = 4434;
UPDATE `civicrm_state_province` SET `name` = (N'Juršinci') WHERE `id` = 4438;
UPDATE `civicrm_state_province` SET `name` = (N'Kidričevo') WHERE `id` = 4441;
UPDATE `civicrm_state_province` SET `name` = (N'Kočevje') WHERE `id` = 4444;
UPDATE `civicrm_state_province` SET `name` = (N'Križevci') WHERE `id` = 4452;
UPDATE `civicrm_state_province` SET `name` = (N'Krško') WHERE `id` = 4453;
UPDATE `civicrm_state_province` SET `name` = (N'Laško') WHERE `id` = 4456;
UPDATE `civicrm_state_province` SET `name` = (N'Loška dolina') WHERE `id` = 4464;
UPDATE `civicrm_state_province` SET `name` = (N'Loški Potok') WHERE `id` = 4465;
UPDATE `civicrm_state_province` SET `name` = (N'Luče') WHERE `id` = 4467;
UPDATE `civicrm_state_province` SET `name` = (N'Majšperk') WHERE `id` = 4469;
UPDATE `civicrm_state_province` SET `name` = (N'Mengeš') WHERE `id` = 4473;
UPDATE `civicrm_state_province` SET `name` = (N'Mežica') WHERE `id` = 4475;
UPDATE `civicrm_state_province` SET `name` = (N'Miklavž na Dravskem polju') WHERE `id` = 4476;
UPDATE `civicrm_state_province` SET `name` = (N'Mirna Peč') WHERE `id` = 4478;
UPDATE `civicrm_state_province` SET `name` = (N'Moravče') WHERE `id` = 4480;
UPDATE `civicrm_state_province` SET `name` = (N'Novo mesto') WHERE `id` = 4488;
UPDATE `civicrm_state_province` SET `name` = (N'Sveti Andraž v Slovenskih goricah') WHERE `id` = 4490;
UPDATE `civicrm_state_province` SET `name` = (N'Šalovci') WHERE `id` = 4492;
UPDATE `civicrm_state_province` SET `name` = (N'Šempeter-Vrtojba') WHERE `id` = 4493;
UPDATE `civicrm_state_province` SET `name` = (N'Šenčur') WHERE `id` = 4494;
UPDATE `civicrm_state_province` SET `name` = (N'Šentilj') WHERE `id` = 4495;
UPDATE `civicrm_state_province` SET `name` = (N'Šentjernej') WHERE `id` = 4496;
UPDATE `civicrm_state_province` SET `name` = (N'Šentjur') WHERE `id` = 4497;
UPDATE `civicrm_state_province` SET `name` = (N'Škocjan') WHERE `id` = 4498;
UPDATE `civicrm_state_province` SET `name` = (N'Škofja Loka') WHERE `id` = 4499;
UPDATE `civicrm_state_province` SET `name` = (N'Škofljica') WHERE `id` = 4500;
UPDATE `civicrm_state_province` SET `name` = (N'Šmarje pri Jelšah') WHERE `id` = 4501;
UPDATE `civicrm_state_province` SET `name` = (N'Šmartno ob Paki') WHERE `id` = 4502;
UPDATE `civicrm_state_province` SET `name` = (N'Šmartno pri Litiji') WHERE `id` = 4503;
UPDATE `civicrm_state_province` SET `name` = (N'Šoštanj') WHERE `id` = 4504;
UPDATE `civicrm_state_province` SET `name` = (N'Štore') WHERE `id` = 4505;
UPDATE `civicrm_state_province` SET `name` = (N'Tišina') WHERE `id` = 4507;
UPDATE `civicrm_state_province` SET `name` = (N'Trbovlje') WHERE `id` = 4509;
UPDATE `civicrm_state_province` SET `name` = (N'Tržič') WHERE `id` = 4512;
UPDATE `civicrm_state_province` SET `name` = (N'Turnišče') WHERE `id` = 4514;
UPDATE `civicrm_state_province` SET `name` = (N'Velike Lašče') WHERE `id` = 4517;
UPDATE `civicrm_state_province` SET `name` = (N'Veržej') WHERE `id` = 4518;
UPDATE `civicrm_state_province` SET `name` = (N'Zavrč') WHERE `id` = 4527;
UPDATE `civicrm_state_province` SET `name` = (N'Zreče') WHERE `id` = 4528;
UPDATE `civicrm_state_province` SET `name` = (N'Žalec') WHERE `id` = 4529;
UPDATE `civicrm_state_province` SET `name` = (N'Železniki') WHERE `id` = 4530;
UPDATE `civicrm_state_province` SET `name` = (N'Žetale') WHERE `id` = 4531;
UPDATE `civicrm_state_province` SET `name` = (N'Žiri') WHERE `id` = 4532;
UPDATE `civicrm_state_province` SET `name` = (N'Žirovnica') WHERE `id` = 4533;
UPDATE `civicrm_state_province` SET `name` = (N'Žužemberk') WHERE `id` = 4534;

-- Add missing Slovenian municipalities
<<<<<<< HEAD
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "86", (N'Ankaran'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "87", (N'Apače'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "88", (N'Cirkulane'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "89", (N'Gorje'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "90", (N'Kostanjevica na Krki'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "91", (N'Log-Dragomer'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "92", (N'Makole'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "93", (N'Mirna'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "94", (N'Mokronog-Trebelno'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "95", (N'Odranci'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "96", (N'Oplotnica'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "97", (N'Ormož'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "98", (N'Osilnica'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "99", (N'Pesnica'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "100", (N'Piran'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "101", (N'Pivka'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "102", (N'Podčetrtek'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "103", (N'Podlehnik'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "104", (N'Podvelka'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "105", (N'Poljčane'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "106", (N'Polzela'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "107", (N'Postojna'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "108", (N'Prebold'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "109", (N'Preddvor'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "110", (N'Prevalje'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "111", (N'Ptuj'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "112", (N'Puconci'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "113", (N'Rače-Fram'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "114", (N'Radeče'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "115", (N'Radenci'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "139", (N'Radlje ob Dravi'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "145", (N'Radovljica'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "171", (N'Ravne na Koroškem'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "172", (N'Razkrižje'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "173", (N'Rečica ob Savinji'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "174", (N'Renče-Vogrsko'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "175", (N'Ribnica'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "176", (N'Ribnica na Pohorju'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "177", (N'Rogaška Slatina'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "178", (N'Rogašovci'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "179", (N'Rogatec'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "180", (N'Ruše'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "195", (N'Selnica ob Dravi'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "196", (N'Semič'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "197", (N'Šentrupert'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "198", (N'Sevnica'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "199", (N'Sežana'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "200", (N'Slovenj Gradec'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "201", (N'Slovenska Bistrica'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "202", (N'Slovenske Konjice'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "203", (N'Šmarješke Toplice'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "204", (N'Sodražica'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "205", (N'Solčava'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "206", (N'Središče ob Dravi'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "207", (N'Starše'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "208", (N'Straža'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "209", (N'Sveta Trojica v Slovenskih goricah'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "210", (N'Sveti Jurij v Slovenskih goricah'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "211", (N'Sveti Tomaž'));
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1193, "212", (N'Vodice'));
=======
INSERT INTO civicrm_state_province (country_id, abbreviation, name)
VALUES
  (1193, "86", "Ankaran"),
  (1193, "87", "Apače"),
  (1193, "88", "Cirkulane"),
  (1193, "89", "Gorje"),
  (1193, "90", "Kostanjevica na Krki"),
  (1193, "91", "Log-Dragomer"),
  (1193, "92", "Makole"),
  (1193, "93", "Mirna"),
  (1193, "94", "Mokronog-Trebelno"),
  (1193, "95", "Odranci"),
  (1193, "96", "Oplotnica"),
  (1193, "97", "Ormož"),
  (1193, "98", "Osilnica"),
  (1193, "99", "Pesnica"),
  (1193, "100", "Piran"),
  (1193, "101", "Pivka"),
  (1193, "102", "Podčetrtek"),
  (1193, "103", "Podlehnik"),
  (1193, "104", "Podvelka"),
  (1193, "105", "Poljčane"),
  (1193, "106", "Polzela"),
  (1193, "107", "Postojna"),
  (1193, "108", "Prebold"),
  (1193, "109", "Preddvor"),
  (1193, "110", "Prevalje"),
  (1193, "111", "Ptuj"),
  (1193, "112", "Puconci"),
  (1193, "113", "Rače-Fram"),
  (1193, "114", "Radeče"),
  (1193, "115", "Radenci"),
  (1193, "139", "Radlje ob Dravi"),
  (1193, "145", "Radovljica"),
  (1193, "171", "Ravne na Koroškem"),
  (1193, "172", "Razkrižje"),
  (1193, "173", "Rečica ob Savinji"),
  (1193, "174", "Renče-Vogrsko"),
  (1193, "175", "Ribnica"),
  (1193, "176", "Ribnica na Pohorju"),
  (1193, "177", "Rogaška Slatina"),
  (1193, "178", "Rogašovci"),
  (1193, "179", "Rogatec"),
  (1193, "180", "Ruše"),
  (1193, "195", "Selnica ob Dravi"),
  (1193, "196", "Semič"),
  (1193, "197", "Šentrupert"),
  (1193, "198", "Sevnica"),
  (1193, "199", "Sežana"),
  (1193, "200", "Slovenj Gradec"),
  (1193, "201", "Slovenska Bistrica"),
  (1193, "202", "Slovenske Konjice"),
  (1193, "203", "Šmarješke Toplice"),
  (1193, "204", "Sodražica"),
  (1193, "205", "Solčava"),
  (1193, "206", "Središče ob Dravi"),
  (1193, "207", "Starše"),
  (1193, "208", "Straža"),
  (1193, "209", "Sveta Trojica v Slovenskih goricah"),
  (1193, "210", "Sveti Jurij v Slovenskih goricah"),
  (1193, "211", "Sveti Tomaž"),
  (1193, "212", "Vodice");
>>>>>>> 650ff6351383992ec77abface9b7f121f16ae07e
