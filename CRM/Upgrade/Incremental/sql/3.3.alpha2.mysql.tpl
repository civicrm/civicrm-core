-- CRM-4572

SELECT @uf_group_id_sharedAddress   := max(id) from civicrm_uf_group where name = 'shared_address';

UPDATE `civicrm_uf_field`
   SET `is_reserved` = '1',
       `is_required` = '1'           
WHERE civicrm_uf_field.uf_group_id = @uf_group_id_sharedAddress AND civicrm_uf_field.field_name IN ('city', 'street_address' );

-- CRM-6977
SELECT   @opt_grp_id_frm_email_address  := MAX(id) from civicrm_option_group where name = 'from_email_address';
UPDATE   civicrm_option_value
   SET   {localize field='label'}label = REPLACE(label, '"<', '" <' ){/localize},
         name = REPLACE( name, '"<', '" <' )
 WHERE   option_group_id = @opt_grp_id_frm_email_address;

