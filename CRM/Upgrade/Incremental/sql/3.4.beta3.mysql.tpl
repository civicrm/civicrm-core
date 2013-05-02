
-- CRM-7871
INSERT INTO civicrm_option_group
      (name, {localize field='description'}description{/localize}, is_reserved, is_active)
VALUES
      ('engagement_index', {localize}'{ts escape="sql"}Engagement Levels{/ts}'{/localize}, 0, 1);

SELECT @optGrpIdEngagementIndex := max(id) from civicrm_option_group where name = 'engagement_index';

INSERT INTO civicrm_option_value 
   (option_group_id, {localize field='label'}label{/localize}, value, name, grouping, filter, is_default, weight, is_optgroup, is_reserved, is_active, component_id, visibility_id)
VALUES
   (@optGrpIdEngagementIndex, {localize}'{ts escape="sql"}1{/ts}'{/localize}, 1, 1, NULL, 0, NULL, 1, 0, 0, 1, NULL, NULL),
   (@optGrpIdEngagementIndex, {localize}'{ts escape="sql"}2{/ts}'{/localize}, 2, 2, NULL, 0, NULL, 2, 0, 0, 1, NULL, NULL),
   (@optGrpIdEngagementIndex, {localize}'{ts escape="sql"}3{/ts}'{/localize}, 3, 3, NULL, 0, NULL, 3, 0, 0, 1, NULL, NULL),
   (@optGrpIdEngagementIndex, {localize}'{ts escape="sql"}4{/ts}'{/localize}, 4, 4, NULL, 0, NULL, 4, 0, 0, 1, NULL, NULL),
   (@optGrpIdEngagementIndex, {localize}'{ts escape="sql"}5{/ts}'{/localize}, 5, 5, NULL, 0, NULL, 5, 0, 0, 1, NULL, NULL);

-- insert navigation link.
-- NOTE: code below will not work due to spaces in increment statement for weight
-- and because CiviCampaign admin links were not inserted during 3.3 upgrade
-- fixing both issues in 3.4.1 upgrade: CRM-7956

SELECT @civiCampaignNavId    := MAX(id) FROM civicrm_navigation where name = 'CiviCampaign';
SELECT @cmapaignNavMaxWeight := MAX(ROUND(weight)) from civicrm_navigation WHERE parent_id = @civiCampaignNavId;

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( {$domainID}, 'civicrm/admin/options/engagement_index&group=engagement_index&reset=1', '{ts escape="sql"}Engagement Index{/ts}', 'Engagement Index', 'administer CiviCampaign', '', @civiCampaignNavId, 1, NULL, @cmapaignNavMaxWeight + 1 );

