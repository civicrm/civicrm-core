{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* This included tpl hides and displays the appropriate blocks based on trigger values in specified field(s) *}

<script type="text/javascript">
    var trigger_field_id = '{$trigger_field_id}';
    var trigger_value = '{$trigger_value}';
    var target_element_id = '{$target_element_id}';
    var target_element_type = '{$target_element_type}';
    var field_type  = '{$field_type}';
    var invert = {$invert};

    showHideByValue(trigger_field_id, trigger_value, target_element_id, target_element_type, field_type, invert);

</script>
