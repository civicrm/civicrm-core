
-- Add new column to civicrm_msg_template table for PDF Page Format
ALTER TABLE civicrm_msg_template ADD COLUMN `pdf_format_id` int(10) unsigned default NULL COMMENT 'FK to civicrm_option_value containing PDF Page Format.';
ALTER TABLE civicrm_msg_template ADD CONSTRAINT pdf_format_id FOREIGN KEY (pdf_format_id) REFERENCES civicrm_option_value (id) ON DELETE SET NULL;

-- Create new option groups
INSERT INTO civicrm_option_group
    (`name`, {localize field='description'}description{/localize}, `is_reserved`, `is_active`)
VALUES
    ('paper_size'  , {localize}'Paper Size'{/localize}          , 0 , 1 ),
    ('pdf_format'  , {localize}'PDF Page Format'{/localize}     , 0 , 1 ),
    ('label_format', {localize}'Mailing Label Format'{/localize}, 0 , 1 );

-- Create navigation menu items
SELECT @nav_formats := id FROM civicrm_navigation WHERE name = 'Configure';
SELECT @nav_formats_weight := MAX(ROUND(weight)) from civicrm_navigation WHERE parent_id = @nav_formats;
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( {$domainID}, 'civicrm/admin/pdfFormats&reset=1',   '{ts escape="sql"}PDF Page Formats{/ts}',     'PDF Page Formats',      'administer CiviCRM', NULL, @nav_formats, '1', NULL, @nav_formats_weight+1 ),
    ( {$domainID}, 'civicrm/admin/labelFormats&reset=1', '{ts escape="sql"}Mailing Label Formats{/ts}','Mailing Label Formats', 'administer CiviCRM', NULL, @nav_formats, '1', NULL, @nav_formats_weight+2 );

-- Insert Paper Sizes
SELECT @option_group_id_paperSize := max(id) from civicrm_option_group where name = 'paper_size';
INSERT INTO
    `civicrm_option_value` (`option_group_id`, {localize field='label'}label{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, {localize field='description'}`description`{/localize}, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
VALUES
    (@option_group_id_paperSize, {localize}'Letter'{/localize},          '{literal}{"metric":"in","width":8.5,"height":11}{/literal}',          'letter',      NULL, NULL, 1, 1,  {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'Legal'{/localize},           '{literal}{"metric":"in","width":8.5,"height":14}{/literal}',          'legal',       NULL, NULL, 0, 2,  {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'Ledger'{/localize},          '{literal}{"metric":"in","width":17,"height":11}{/literal}',           'ledger',      NULL, NULL, 0, 3,  {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'Tabloid'{/localize},         '{literal}{"metric":"in","width":11,"height":17}{/literal}',           'tabloid',     NULL, NULL, 0, 4,  {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'Executive'{/localize},       '{literal}{"metric":"in","width":7.25,"height":10.5}{/literal}',       'executive',   NULL, NULL, 0, 5,  {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'Folio'{/localize},           '{literal}{"metric":"in","width":8.5,"height":13}{/literal}',          'folio',       NULL, NULL, 0, 6,  {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'Envelope #9'{/localize},     '{literal}{"metric":"pt","width":638.93,"height":278.93}{/literal}',   'envelope-9',  NULL, NULL, 0, 7,  {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'Envelope #10'{/localize},    '{literal}{"metric":"pt","width":684,"height":297}{/literal}',         'envelope-10', NULL, NULL, 0, 8,  {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'Envelope #11'{/localize},    '{literal}{"metric":"pt","width":747,"height":324}{/literal}',         'envelope-11', NULL, NULL, 0, 9,  {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'Envelope #12'{/localize},    '{literal}{"metric":"pt","width":792,"height":342}{/literal}',         'envelope-12', NULL, NULL, 0, 10, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'Envelope #14'{/localize},    '{literal}{"metric":"pt","width":828,"height":360}{/literal}',         'envelope-14', NULL, NULL, 0, 11, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'Envelope ISO B4'{/localize}, '{literal}{"metric":"pt","width":1000.63,"height":708.66}{/literal}',  'envelope-b4', NULL, NULL, 0, 12, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'Envelope ISO B5'{/localize}, '{literal}{"metric":"pt","width":708.66,"height":498.9}{/literal}',    'envelope-b5', NULL, NULL, 0, 13, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'Envelope ISO B6'{/localize}, '{literal}{"metric":"pt","width":498.9,"height":354.33}{/literal}',    'envelope-b6', NULL, NULL, 0, 14, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'Envelope ISO C3'{/localize}, '{literal}{"metric":"pt","width":1298.27,"height":918.42}{/literal}',  'envelope-c3', NULL, NULL, 0, 15, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'Envelope ISO C4'{/localize}, '{literal}{"metric":"pt","width":918.42,"height":649.13}{/literal}',   'envelope-c4', NULL, NULL, 0, 16, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'Envelope ISO C5'{/localize}, '{literal}{"metric":"pt","width":649.13,"height":459.21}{/literal}',   'envelope-c5', NULL, NULL, 0, 17, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'Envelope ISO C6'{/localize}, '{literal}{"metric":"pt","width":459.21,"height":323.15}{/literal}',   'envelope-c6', NULL, NULL, 0, 18, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'Envelope ISO DL'{/localize}, '{literal}{"metric":"pt","width":623.622,"height":311.811}{/literal}', 'envelope-dl', NULL, NULL, 0, 19, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO A0'{/localize},          '{literal}{"metric":"pt","width":2383.94,"height":3370.39}{/literal}', 'a0',          NULL, NULL, 0, 20, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO A1'{/localize},          '{literal}{"metric":"pt","width":1683.78,"height":2383.94}{/literal}', 'a1',          NULL, NULL, 0, 21, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO A2'{/localize},          '{literal}{"metric":"pt","width":1190.55,"height":1683.78}{/literal}', 'a2',          NULL, NULL, 0, 22, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO A3'{/localize},          '{literal}{"metric":"pt","width":841.89,"height":1190.55}{/literal}',  'a3',          NULL, NULL, 0, 23, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO A4'{/localize},          '{literal}{"metric":"pt","width":595.28,"height":841.89}{/literal}',   'a4',          NULL, NULL, 0, 24, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO A5'{/localize},          '{literal}{"metric":"pt","width":419.53,"height":595.28}{/literal}',   'a5',          NULL, NULL, 0, 25, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO A6'{/localize},          '{literal}{"metric":"pt","width":297.64,"height":419.53}{/literal}',   'a6',          NULL, NULL, 0, 26, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO A7'{/localize},          '{literal}{"metric":"pt","width":209.76,"height":297.64}{/literal}',   'a7',          NULL, NULL, 0, 27, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO A8'{/localize},          '{literal}{"metric":"pt","width":147.4,"height":209.76}{/literal}',    'a8',          NULL, NULL, 0, 28, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO A9'{/localize},          '{literal}{"metric":"pt","width":104.88,"height":147.4}{/literal}',    'a9',          NULL, NULL, 0, 29, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO A10'{/localize},         '{literal}{"metric":"pt","width":73.7,"height":104.88}{/literal}',     'a10',         NULL, NULL, 0, 30, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO B0'{/localize},          '{literal}{"metric":"pt","width":2834.65,"height":4008.19}{/literal}', 'b0',          NULL, NULL, 0, 31, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO B1'{/localize},          '{literal}{"metric":"pt","width":2004.09,"height":2834.65}{/literal}', 'b1',          NULL, NULL, 0, 32, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO B2'{/localize},          '{literal}{"metric":"pt","width":1417.32,"height":2004.09}{/literal}', 'b2',          NULL, NULL, 0, 33, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO B3'{/localize},          '{literal}{"metric":"pt","width":1000.63,"height":1417.32}{/literal}', 'b3',          NULL, NULL, 0, 34, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO B4'{/localize},          '{literal}{"metric":"pt","width":708.66,"height":1000.63}{/literal}',  'b4',          NULL, NULL, 0, 35, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO B5'{/localize},          '{literal}{"metric":"pt","width":498.9,"height":708.66}{/literal}',    'b5',          NULL, NULL, 0, 36, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO B6'{/localize},          '{literal}{"metric":"pt","width":354.33,"height":498.9}{/literal}',    'b6',          NULL, NULL, 0, 37, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO B7'{/localize},          '{literal}{"metric":"pt","width":249.45,"height":354.33}{/literal}',   'b7',          NULL, NULL, 0, 38, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO B8'{/localize},          '{literal}{"metric":"pt","width":175.75,"height":249.45}{/literal}',   'b8',          NULL, NULL, 0, 39, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO B9'{/localize},          '{literal}{"metric":"pt","width":124.72,"height":175.75}{/literal}',   'b9',          NULL, NULL, 0, 40, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO B10'{/localize},         '{literal}{"metric":"pt","width":87.87,"height":124.72}{/literal}',    'b10',         NULL, NULL, 0, 41, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO C0'{/localize},          '{literal}{"metric":"pt","width":2599.37,"height":3676.54}{/literal}', 'c0',          NULL, NULL, 0, 42, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO C1'{/localize},          '{literal}{"metric":"pt","width":1836.85,"height":2599.37}{/literal}', 'c1',          NULL, NULL, 0, 43, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO C2'{/localize},          '{literal}{"metric":"pt","width":1298.27,"height":1836.85}{/literal}', 'c2',          NULL, NULL, 0, 44, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO C3'{/localize},          '{literal}{"metric":"pt","width":918.43,"height":1298.27}{/literal}',  'c3',          NULL, NULL, 0, 45, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO C4'{/localize},          '{literal}{"metric":"pt","width":649.13,"height":918.43}{/literal}',   'c4',          NULL, NULL, 0, 46, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO C5'{/localize},          '{literal}{"metric":"pt","width":459.21,"height":649.13}{/literal}',   'c5',          NULL, NULL, 0, 47, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO C6'{/localize},          '{literal}{"metric":"pt","width":323.15,"height":459.21}{/literal}',   'c6',          NULL, NULL, 0, 48, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO C7'{/localize},          '{literal}{"metric":"pt","width":229.61,"height":323.15}{/literal}',   'c7',          NULL, NULL, 0, 49, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO C8'{/localize},          '{literal}{"metric":"pt","width":161.57,"height":229.61}{/literal}',   'c8',          NULL, NULL, 0, 50, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO C9'{/localize},          '{literal}{"metric":"pt","width":113.39,"height":161.57}{/literal}',   'c9',          NULL, NULL, 0, 51, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO C10'{/localize},         '{literal}{"metric":"pt","width":79.37,"height":113.39}{/literal}',    'c10',         NULL, NULL, 0, 52, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO RA0'{/localize},         '{literal}{"metric":"pt","width":2437.8,"height":3458.27}{/literal}',  'ra0',         NULL, NULL, 0, 53, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO RA1'{/localize},         '{literal}{"metric":"pt","width":1729.13,"height":2437.8}{/literal}',  'ra1',         NULL, NULL, 0, 54, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO RA2'{/localize},         '{literal}{"metric":"pt","width":1218.9,"height":1729.13}{/literal}',  'ra2',         NULL, NULL, 0, 55, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO RA3'{/localize},         '{literal}{"metric":"pt","width":864.57,"height":1218.9}{/literal}',   'ra3',         NULL, NULL, 0, 56, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO RA4'{/localize},         '{literal}{"metric":"pt","width":609.45,"height":864.57}{/literal}',   'ra4',         NULL, NULL, 0, 57, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO SRA0'{/localize},        '{literal}{"metric":"pt","width":2551.18,"height":3628.35}{/literal}', 'sra0',        NULL, NULL, 0, 58, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO SRA1'{/localize},        '{literal}{"metric":"pt","width":1814.17,"height":2551.18}{/literal}', 'sra1',        NULL, NULL, 0, 59, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO SRA2'{/localize},        '{literal}{"metric":"pt","width":1275.59,"height":1814.17}{/literal}', 'sra2',        NULL, NULL, 0, 60, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO SRA3'{/localize},        '{literal}{"metric":"pt","width":907.09,"height":1275.59}{/literal}',  'sra3',        NULL, NULL, 0, 61, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_paperSize, {localize}'ISO SRA4'{/localize},        '{literal}{"metric":"pt","width":637.8,"height":907.09}{/literal}',    'sra4',        NULL, NULL, 0, 62, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL);

-- Insert Label Formats
SELECT @option_group_id_label := max(id) from civicrm_option_group where name = 'label_format';
INSERT INTO
    `civicrm_option_value` (`option_group_id`, {localize field='label'}label{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, {localize field='description'}`description`{/localize}, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
VALUES
    (@option_group_id_label, {localize}'Avery 3475'{/localize}, '{literal}{"paper-size":"a4","orientation":"portrait","font-name":"helvetica","font-size":10,"font-style":"","metric":"mm","lMargin":0,"tMargin":5,"NX":3,"NY":8,"SpaceX":0,"SpaceY":0,"width":70,"height":36,"lPadding":5.08,"tPadding":5.08}{/literal}',                   '3475',  'Avery', NULL, 0, 1,  {localize}NULL{/localize}, 0, 1, 1, NULL, NULL),
    (@option_group_id_label, {localize}'Avery 5160'{/localize}, '{literal}{"paper-size":"letter","orientation":"portrait","font-name":"helvetica","font-size":8,"font-style":"","metric":"in","lMargin":0.21975,"tMargin":0.5,"NX":3,"NY":10,"SpaceX":0.14,"SpaceY":0,"width":2.5935,"height":1,"lPadding":0.20,"tPadding":0.20}{/literal}', '5160',  'Avery', NULL, 0, 2,  {localize}NULL{/localize}, 0, 1, 1, NULL, NULL),
    (@option_group_id_label, {localize}'Avery 5161'{/localize}, '{literal}{"paper-size":"letter","orientation":"portrait","font-name":"helvetica","font-size":8,"font-style":"","metric":"in","lMargin":0.175,"tMargin":0.5,"NX":2,"NY":10,"SpaceX":0.15625,"SpaceY":0,"width":4,"height":1,"lPadding":0.20,"tPadding":0.20}{/literal}',     '5161',  'Avery', NULL, 0, 3,  {localize}NULL{/localize}, 0, 1, 1, NULL, NULL),
    (@option_group_id_label, {localize}'Avery 5162'{/localize}, '{literal}{"paper-size":"letter","orientation":"portrait","font-name":"helvetica","font-size":8,"font-style":"","metric":"in","lMargin":0.1525,"tMargin":0.88,"NX":2,"NY":7,"SpaceX":0.195,"SpaceY":0,"width":4,"height":1.33,"lPadding":0.20,"tPadding":0.20}{/literal}',   '5162',  'Avery', NULL, 0, 4,  {localize}NULL{/localize}, 0, 1, 1, NULL, NULL),
    (@option_group_id_label, {localize}'Avery 5163'{/localize}, '{literal}{"paper-size":"letter","orientation":"portrait","font-name":"helvetica","font-size":8,"font-style":"","metric":"in","lMargin":0.18,"tMargin":0.5,"NX":2,"NY":5,"SpaceX":0.14,"SpaceY":0,"width":4,"height":2,"lPadding":0.20,"tPadding":0.20}{/literal}',          '5163',  'Avery', NULL, 0, 5,  {localize}NULL{/localize}, 0, 1, 1, NULL, NULL),
    (@option_group_id_label, {localize}'Avery 5164'{/localize}, '{literal}{"paper-size":"letter","orientation":"portrait","font-name":"helvetica","font-size":12,"font-style":"","metric":"in","lMargin":0.156,"tMargin":0.5,"NX":2,"NY":3,"SpaceX":0.1875,"SpaceY":0,"width":4,"height":3.33,"lPadding":0.20,"tPadding":0.20}{/literal}',   '5164',  'Avery', NULL, 0, 6,  {localize}NULL{/localize}, 0, 1, 1, NULL, NULL),
    (@option_group_id_label, {localize}'Avery 8600'{/localize}, '{literal}{"paper-size":"letter","orientation":"portrait","font-name":"helvetica","font-size":8,"font-style":"","metric":"mm","lMargin":7.1,"tMargin":19,"NX":3,"NY":10,"SpaceX":9.5,"SpaceY":3.1,"width":66.6,"height":25.4,"lPadding":5.08,"tPadding":5.08}{/literal}',    '8600',  'Avery', NULL, 0, 7,  {localize}NULL{/localize}, 0, 1, 1, NULL, NULL),
    (@option_group_id_label, {localize}'Avery L7160'{/localize}, '{literal}{"paper-size":"a4","orientation":"portrait","font-name":"helvetica","font-size":9,"font-style":"","metric":"in","lMargin":0.28,"tMargin":0.6,"NX":3,"NY":7,"SpaceX":0.1,"SpaceY":0,"width":2.5,"height":1.5,"lPadding":0.20,"tPadding":0.20}{/literal}',          'L7160', 'Avery', NULL, 0, 8,  {localize}NULL{/localize}, 0, 1, 1, NULL, NULL),
    (@option_group_id_label, {localize}'Avery L7161'{/localize}, '{literal}{"paper-size":"a4","orientation":"portrait","font-name":"helvetica","font-size":9,"font-style":"","metric":"in","lMargin":0.28,"tMargin":0.35,"NX":3,"NY":6,"SpaceX":0.1,"SpaceY":0,"width":2.5,"height":1.83,"lPadding":0.20,"tPadding":0.20}{/literal}',        'L7161', 'Avery', NULL, 0, 9,  {localize}NULL{/localize}, 0, 1, 1, NULL, NULL),
    (@option_group_id_label, {localize}'Avery L7162'{/localize}, '{literal}{"paper-size":"a4","orientation":"portrait","font-name":"helvetica","font-size":9,"font-style":"","metric":"in","lMargin":0.18,"tMargin":0.51,"NX":2,"NY":8,"SpaceX":0.1,"SpaceY":0,"width":3.9,"height":1.33,"lPadding":0.20,"tPadding":0.20}{/literal}',        'L7162', 'Avery', NULL, 0, 10, {localize}NULL{/localize}, 0, 1, 1, NULL, NULL),
    (@option_group_id_label, {localize}'Avery L7163'{/localize}, '{literal}{"paper-size":"a4","orientation":"portrait","font-name":"helvetica","font-size":9,"font-style":"","metric":"in","lMargin":0.18,"tMargin":0.6,"NX":2,"NY":7,"SpaceX":0.1,"SpaceY":0,"width":3.9,"height":1.5,"lPadding":0.20,"tPadding":0.20}{/literal}',          'L7163', 'Avery', NULL, 0, 11, {localize}NULL{/localize}, 0, 1, 1, NULL, NULL);


-- CRM-8133, add entries for assignee contact for activities of type Membership Renewal Reminder
SELECT @option_value_membership_reminder := value FROM civicrm_option_value v, civicrm_option_group g WHERE v.option_group_id = g.id AND g.name = 'activity_type' AND v.name = 'Membership Renewal Reminder';

INSERT INTO civicrm_activity_assignment ( activity_id, assignee_contact_id ) SELECT ca.id, ca.source_contact_id FROM civicrm_activity ca LEFT JOIN civicrm_activity_assignment cas ON ( cas.activity_id = ca.id ) WHERE ca.activity_type_id = @option_value_membership_reminder AND cas.id IS NULL AND ca.source_contact_id IS NOT NULL;
