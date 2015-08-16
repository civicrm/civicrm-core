
{* file to handle db changes in 4.6.6 during upgrade *}

-- CRM-16846 - This upgrade may have been previously skipped so moving it to 4.6.6
-- update permission for editing message templates (CRM-15819)

SELECT @messages_menu_id := id FROM civicrm_navigation WHERE name = 'Mailings';

UPDATE `civicrm_navigation`
SET `permission` = 'edit message templates'
WHERE `parent_id` = @messages_menu_id
AND name = 'Message Templates';

-- CRM-16907
SELECT @adminHelplastID := id FROM civicrm_navigation WHERE name = 'Help';
UPDATE civicrm_navigation
SET name = 'Support', label = 'Support'
WHERE id = @adminHelplastID;

DELETE FROM civicrm_navigation where parent_id = @adminHelplastID;

INSERT INTO civicrm_navigation
     ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
 VALUES
     ( {$domainID}, 'http://civicrm.org/get-started?src=iam',        '{ts escape="sql" skip="true"}Get started{/ts}',        'Get started',        NULL, 'AND', @adminHelplastID, '1', NULL, 1 ),
     ( {$domainID}, 'http://civicrm.org/documentation?src=iam',      '{ts escape="sql" skip="true"}Documentation{/ts}',      'Documentation',      NULL, 'AND', @adminHelplastID, '1', NULL, 2 ),
     ( {$domainID}, 'http://civicrm.org/ask-a-question?src=iam',     '{ts escape="sql" skip="true"}Ask a question{/ts}',     'Ask a question',     NULL, 'AND', @adminHelplastID, '1', NULL, 3 ),
     ( {$domainID}, 'http://civicrm.org/experts?src=iam',            '{ts escape="sql" skip="true"}Get expert help{/ts}',    'Get expert help',    NULL, 'AND', @adminHelplastID, '1', NULL, 4 ),
     ( {$domainID}, 'http://civicrm.org/about?src=iam',              '{ts escape="sql" skip="true"}About CiviCRM{/ts}',      'About CiviCRM',      NULL, 'AND', @adminHelplastID, '1', 1, 5 ),
     ( {$domainID}, 'http://civicrm.org/register-your-site?src=iam', '{ts escape="sql" skip="true"}Register your site{/ts}', 'Register your site', NULL, 'AND', @adminHelplastID, '1', NULL, 6 ),    
     ( {$domainID}, 'http://civicrm.org/become-member?src=iam',      '{ts escape="sql" skip="true"}Join CiviCRM{/ts}',       'Join CiviCRM',       NULL, 'AND', @adminHelplastID, '1', NULL, 7 );

INSERT INTO civicrm_navigation
     ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
 VALUES
     ( {$domainID}, NULL, '{ts escape="sql" skip="true"}Developer{/ts}', 'Developer', 'administer CiviCRM', '', @adminHelplastID, '1', 1, 8 );

SET @devellastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
     ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
 VALUES
     ( {$domainID}, 'civicrm/api', '{ts escape="sql" skip="true"}API Explorer{/ts}','API Explorer', 'administer CiviCRM', '', @devellastID, '1', NULL, 1 ),
     ( {$domainID}, 'http://civicrm.org/developer-documentation?src=iam', '{ts escape="sql" skip="true"}Developer Docs{/ts}', 'Developer Docs', 'administer CiviCRM', '', @devellastID, '1', NULL, 3 );
 