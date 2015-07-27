-- CRM-5461
 SELECT @option_group_id_act   := MAX(id) from civicrm_option_group where name = 'activity_type';
 SELECT @activity_type_max_val := MAX(ROUND(op.value)) FROM civicrm_option_value op WHERE op.option_group_id = @option_group_id_act;
 SELECT @activity_type_max_wt  := MAX(ROUND(val.weight)) FROM civicrm_option_value val where val.option_group_id = @option_group_id_act;

UPDATE  civicrm_option_value val
   SET  val.value  = @activity_type_max_val+1,
        val.weight = @activity_type_max_wt+1
 WHERE  val.option_group_id = @option_group_id_act
   AND  val.name = 'Print PDF Letter';

