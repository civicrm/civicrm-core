-- adding inbound activity CRM-5290
{if $addInboundEmail}
  SELECT @option_group_id_activity_type := max(id) from civicrm_option_group where name = 'activity_type';
  SELECT @max_val    := MAX(ROUND(op.value)) FROM civicrm_option_value op WHERE op.option_group_id  = @option_group_id_activity_type;
  SELECT @max_wt     := max(weight) from civicrm_option_value where option_group_id=@option_group_id_activity_type;

  INSERT INTO civicrm_option_value
        (option_group_id,                {localize field='label'}label{/localize}, {localize field='description'}description{/localize}, value,                           name,                    weight,                      filter, is_reserved)
        VALUES
        (@option_group_id_activity_type, {localize}'Inbound Email'{/localize},        {localize}'Inbound Email.'{/localize},              (SELECT @max_val := @max_val+1), 'Inbound Email',       (SELECT @max_wt := @max_wt+1), 1,     1);
{/if}
