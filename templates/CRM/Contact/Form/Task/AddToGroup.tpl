{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block crm-contact-task-addtogroup-form-block">
<table class="form-layout">
    {if $form.group_id.value}
       <tr class="crm-contact-task-addtogroup-form-block-group_id">
          <td class="label">{ts}Group{/ts}</td>
          <td>{$form.group_id.html}</td>
       </tr>
    {else}
        <tr><td>{$form.group_option.html}</td></tr>
        <tr id="id_existing_group">
            <td>
                <table class="form-layout">
                <tr><td class="label">{$form.group_id.label}<span class="crm-marker">*</span></td><td>{$form.group_id.html}</td></tr>
                </table>
            </td>
        </tr>
        <tr id="id_new_group" class="html-adjust">
            <td>
                <table class="form-layout">
                <tr class="crm-contact-task-addtogroup-form-block-title">
                   <td class="label">{$form.title.label}<span class="crm-marker">*</span></td>
                   <td>{$form.title.html}</td>
                <tr>
                <tr class="crm-contact-task-addtogroup-form-block-description">
                   <td class="label">{$form.description.label}</td>
                   <td>{$form.description.html}</td></tr>
                {if !empty($form.group_type)}
                <tr class="crm-contact-task-addtogroup-form-block-group_type">
        <td class="label">{$form.group_type.label}</td>
                    <td>{$form.group_type.html}</td>
                </tr>
                {/if}
                <tr>
                  <td colspan=2>{include file="CRM/common/customDataBlock.tpl" groupID='' customDataType='Group' customDataSubType=false cid=false}</td>
                </tr>
                </table>
            </td>
        </tr>
    {/if}
</table>
<table class="form-layout">
        <tr><td>{include file="CRM/Contact/Form/Task.tpl"}</td></tr>
</table>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>

{if !$form.group_id.value}
{literal}
<script type="text/javascript">
showElements();
function showElements() {
    if ( document.getElementsByName('group_option')[0].checked ) {
      cj('#id_existing_group').show();
      cj('#id_new_group').hide();
    } else {
      cj('#id_new_group').show();
      cj('#id_existing_group').hide();
    }
}
</script>
{/literal}
{/if}
