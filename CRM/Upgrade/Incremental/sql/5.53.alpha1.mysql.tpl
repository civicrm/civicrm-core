{* file to handle db changes in 5.53.alpha1 during upgrade *}

-- dev/core#3783 Recent Items providers
INSERT INTO civicrm_option_group
  (name, {localize field='title'}title{/localize}, is_reserved, is_active) VALUES ('recent_items_provider', {localize}'{ts escape="sql"}Recent Items Provider{/ts}'{/localize}, 0, 1);

SELECT @option_group_id_recent := max(id) from civicrm_option_group where name = 'recent_items_provider';
INSERT INTO civicrm_option_value (`option_group_id`, {localize field='label'}label{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, {localize field='description'}description{/localize}, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
 VALUES
    (@option_group_id_recent, {localize}'{ts escape="sql"}Contacts{/ts}'{/localize}, 'Contact', 'Contacts', NULL, NULL, 0, 1, '', 0, 0, 1, NULL, NULL),
    (@option_group_id_recent, {localize}'{ts escape="sql"}Relationships{/ts}'{/localize}, 'Relationship', 'Relationships', NULL, NULL, 0, 1, '', 0, 0, 1, NULL, NULL),
    (@option_group_id_recent, {localize}'{ts escape="sql"}Activities{/ts}'{/localize}, 'Activity', 'Activities', NULL, NULL, 0, 1, '', 0, 0, 1, NULL, NULL),
    (@option_group_id_recent, {localize}'{ts escape="sql"}Notes{/ts}'{/localize}, 'Note', 'Notes', NULL, NULL, 0, 1, '', 0, 0, 1, NULL, NULL),
    (@option_group_id_recent, {localize}'{ts escape="sql"}Groups{/ts}'{/localize}, 'Group', 'Groups', NULL, NULL, 0, 1, '', 0, 0, 1, NULL, NULL),
    (@option_group_id_recent, {localize}'{ts escape="sql"}Cases{/ts}'{/localize}, 'Case', 'Cases', NULL, NULL, 0, 1, '', 0, 0, 1, NULL, NULL),
    (@option_group_id_recent, {localize}'{ts escape="sql"}Contributions{/ts}'{/localize}, 'Contribution', 'Contributions', NULL, NULL, 0, 1, '', 0, 0, 1, NULL, NULL),
    (@option_group_id_recent, {localize}'{ts escape="sql"}Participants{/ts}'{/localize}, 'Participant', 'Participants', NULL, NULL, 0, 1, '', 0, 0, 1, NULL, NULL),
    (@option_group_id_recent, {localize}'{ts escape="sql"}Grants{/ts}'{/localize}, 'Grant', 'Grants', NULL, NULL, 0, 1, '', 0, 0, 1, NULL, NULL),
    (@option_group_id_recent, {localize}'{ts escape="sql"}Memberships{/ts}'{/localize}, 'Membership', 'Memberships', NULL, NULL, 0, 1, '', 0, 0, 1, NULL, NULL),
    (@option_group_id_recent, {localize}'{ts escape="sql"}Pledges{/ts}'{/localize}, 'Pledge', 'Pledges', NULL, NULL, 0, 1, '', 0, 0, 1, NULL, NULL),
    (@option_group_id_recent, {localize}'{ts escape="sql"}Events{/ts}'{/localize}, 'Event', 'Events', NULL, NULL, 0, 1, '', 0, 0, 1, NULL, NULL),
    (@option_group_id_recent, {localize}'{ts escape="sql"}Campaigns{/ts}'{/localize}, 'Campaign', 'Campaigns', NULL, NULL, 0, 1, '', 0, 0, 1, NULL, NULL);
