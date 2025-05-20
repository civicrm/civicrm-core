{* file to handle db changes in 5.63.alpha1 during upgrade *}

{* https://github.com/civicrm/civicrm-core/pull/24916 *}
DELETE FROM `civicrm_navigation` WHERE `name` = 'Get started';
DELETE FROM `civicrm_navigation` WHERE `name` = 'Documentation';
DELETE FROM `civicrm_navigation` WHERE `name` = 'Ask a question';
DELETE FROM `civicrm_navigation` WHERE `name` = 'Get expert help';

SELECT @adminHelplastID := `id` FROM `civicrm_navigation` WHERE `name` = 'Support';
INSERT IGNORE INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( {$domainID}, 'https://docs.civicrm.org/user/?src=iam', '{ts escape="sql" skip="true"}User Guide{/ts}', 'User Guide', NULL, 'AND', @adminHelplastID, '1', NULL, 1 ),
    ( {$domainID}, 'https://civicrm.org/help?src=iam',       '{ts escape="sql" skip="true"}Get Help{/ts}',   'Get Help',   NULL, 'AND', @adminHelplastID, '1', NULL, 2 );

UPDATE IGNORE `civicrm_navigation` SET `name` = 'Register Your Site', `label` = '{ts escape="sql" skip="true"}Register Your Site{/ts}' WHERE `name` = 'Register your site';

-- Ensure new name field is not null/unique. Setting to ID is a bit lazy - but sql localisation is painful.
UPDATE civicrm_contribution_page SET `name` = `id`;

-- Add name field, make frontend_title required (in conjunction with php function)
{localize field='title,frontend_title'}
  UPDATE `civicrm_contribution_page`
  SET `title` = ''
  WHERE `title` IS NULL;

  UPDATE `civicrm_contribution_page`
  SET `frontend_title` = `title`
  WHERE `frontend_title` IS NULL OR `frontend_title` = '';
{/localize}
