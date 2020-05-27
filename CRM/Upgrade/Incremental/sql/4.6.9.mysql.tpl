{* file to handle db changes in 4.6.9 during upgrade *}

-- CRM-17112 - Add Missing countries Saint Barthélemy and Saint Martin
INSERT INTO civicrm_country (name,iso_code,region_id,is_province_abbreviated) VALUES("Saint Barthélemy", "BL", "2", "0");
INSERT INTO civicrm_country (name,iso_code,region_id,is_province_abbreviated) VALUES("Saint Martin (French part)", "MF", "2", "0");

-- CRM-17039 - Add credit note for cancelled payments
{include file='../CRM/Upgrade/4.6.9.msg_template/civicrm_msg_template.tpl'}

-- CRM-17258 - Add created id, owner_id to report instances.
ALTER TABLE civicrm_report_instance
ADD COLUMN `created_id` int(10) unsigned DEFAULT NULL COMMENT 'FK to contact table.',
ADD COLUMN `owner_id` int(10) unsigned DEFAULT NULL COMMENT 'FK to contact table.',
ADD CONSTRAINT `FK_civicrm_report_instance_created_id` FOREIGN KEY (`created_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `FK_civicrm_report_instance_owner_id` FOREIGN KEY (`owner_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE SET NULL;

-- CRM-16907
SELECT @navMaxWeight := MAX(ROUND(weight)) from civicrm_navigation WHERE parent_id IS NULL;

-- Add "support" menu if it's not there already
INSERT INTO civicrm_navigation (domain_id, label, name, is_active, weight)
SELECT * FROM (SELECT {$domainID} as domain_id, 'Support' as label, 'Help' as name, 1 as is_active, @navMaxWeight + 1 as weight) AS tmp
WHERE NOT EXISTS (
SELECT name FROM civicrm_navigation WHERE name = 'Help' AND domain_id = {$domainID}
) LIMIT 1;

SELECT @adminHelplastID := id FROM civicrm_navigation WHERE name = 'Help' AND domain_id = {$domainID};

UPDATE civicrm_navigation
SET name = 'Support', label = '{ts escape="sql" skip="true"}Support{/ts}'
WHERE id = @adminHelplastID;

DELETE FROM civicrm_navigation where parent_id = @adminHelplastID AND (name = 'Developer' OR url LIKE "http://civicrm.org%");

INSERT INTO civicrm_navigation
( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
( {$domainID}, 'https://civicrm.org/get-started?src=iam',        '{ts escape="sql" skip="true"}Get started{/ts}',        'Get started',        NULL, 'AND', @adminHelplastID, '1', NULL, 1 ),
( {$domainID}, 'https://civicrm.org/documentation?src=iam',      '{ts escape="sql" skip="true"}Documentation{/ts}',      'Documentation',      NULL, 'AND', @adminHelplastID, '1', NULL, 2 ),
( {$domainID}, 'https://civicrm.org/ask-a-question?src=iam',     '{ts escape="sql" skip="true"}Ask a question{/ts}',     'Ask a question',     NULL, 'AND', @adminHelplastID, '1', NULL, 3 ),
( {$domainID}, 'https://civicrm.org/experts?src=iam',            '{ts escape="sql" skip="true"}Get expert help{/ts}',    'Get expert help',    NULL, 'AND', @adminHelplastID, '1', NULL, 4 ),
( {$domainID}, 'https://civicrm.org/about?src=iam',              '{ts escape="sql" skip="true"}About CiviCRM{/ts}',      'About CiviCRM',      NULL, 'AND', @adminHelplastID, '1', 1, 5 ),
( {$domainID}, 'https://civicrm.org/register-your-site?src=iam&sid={ldelim}sid{rdelim}', '{ts escape="sql" skip="true"}Register your site{/ts}', 'Register your site', NULL, 'AND', @adminHelplastID, '1', NULL, 6 ),
( {$domainID}, 'https://civicrm.org/become-a-member?src=iam&sid={ldelim}sid{rdelim}',      '{ts escape="sql" skip="true"}Join CiviCRM{/ts}',       'Join CiviCRM',       NULL, 'AND', @adminHelplastID, '1', NULL, 7 );

INSERT INTO civicrm_navigation
( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
( {$domainID}, NULL, '{ts escape="sql" skip="true"}Developer{/ts}', 'Developer', 'administer CiviCRM', '', @adminHelplastID, '1', 1, 8 );

SET @devellastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
( {$domainID}, 'civicrm/api', '{ts escape="sql" skip="true"}API Explorer{/ts}','API Explorer', 'administer CiviCRM', '', @devellastID, '1', NULL, 1 ),
( {$domainID}, 'https://civicrm.org/developer-documentation?src=iam', '{ts escape="sql" skip="true"}Developer Docs{/ts}', 'Developer Docs', 'administer CiviCRM', '', @devellastID, '1', NULL, 3 );

-- Set CiviCRM URLs to https
UPDATE civicrm_navigation SET url = REPLACE(url, 'http://civicrm.org', 'https://civicrm.org');
