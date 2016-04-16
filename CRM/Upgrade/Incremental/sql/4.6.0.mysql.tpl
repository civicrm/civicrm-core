{* file to handle db changes in 4.6.0 during upgrade *}

--CRM-16148 add missing option names
SELECT @option_group_id_pcm := max(id) from civicrm_option_group where name = 'preferred_communication_method';
SELECT @option_group_id_notePrivacy := max(id) from civicrm_option_group where name = 'note_privacy';

UPDATE civicrm_option_value
SET name = 'Phone'
WHERE option_group_id = @option_group_id_pcm AND value = 1;
UPDATE civicrm_option_value
SET name = 'Email'
WHERE option_group_id = @option_group_id_pcm AND value = 2;
UPDATE civicrm_option_value
SET name = 'Postal Mail'
WHERE option_group_id = @option_group_id_pcm AND value = 3;
UPDATE civicrm_option_value
SET name = 'SMS'
WHERE option_group_id = @option_group_id_pcm AND value = 4;
UPDATE civicrm_option_value
SET name = 'Fax'
WHERE option_group_id = @option_group_id_pcm AND value = 5;

UPDATE civicrm_option_value
SET name = 'None'
WHERE option_group_id = @option_group_id_notePrivacy AND value = 0;
UPDATE civicrm_option_value
SET name = 'Author Only'
WHERE option_group_id = @option_group_id_notePrivacy AND value = 1;

--These labels were never translated so just copy them over as names
{if $multilingual}
  UPDATE civicrm_option_value v, civicrm_option_group g
  SET v.name = v.label_{$locales.0}
  WHERE g.id = v.option_group_id AND g.name IN
  ('group_type', 'safe_file_extension', 'wysiwyg_editor');
{else}
  UPDATE civicrm_option_value v, civicrm_option_group g
  SET v.name = v.label
  WHERE g.id = v.option_group_id AND g.name IN
  ('group_type', 'safe_file_extension', 'wysiwyg_editor');
{/if}

--This one is weird. What the heck is this anyway?
UPDATE civicrm_option_value v, civicrm_option_group g
SET v.name = v.value
WHERE g.id = v.option_group_id AND g.name = 'redaction_rule';
