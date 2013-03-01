<div class="form-item">
<legend>DB Template Strings Information</legend>
<div class="crm-block crm-form-block crm-admin-options-form-block">
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
<table  class="form-layout-compressed">
   <tr>
     <td class="label ">{$form.context.label}</td>
     <td>{$form.context.html|crmAddClass:huge}</dd>
   </tr>
   <tr>
     <td class="label ">{$form.name.label}</td>
     <td>{$form.name.html|crmAddClass:huge}</dd>
   </tr>
   <tr>
     <td class="label ">{$form.data.label}</td>
     <td>{$form.data.html}</dd>
    </tr>
</table>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
