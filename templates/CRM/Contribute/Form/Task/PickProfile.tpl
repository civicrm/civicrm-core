{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="form-item crm-block crm-form-block crm-contribution-form-block">
     <table class="form-layout-compressed">
       <tr class="crm-contribution-form-block-uf_group_id">
          <td class="label">{$form.uf_group_id.label}</td>
    <td class="html-adjust">{$form.uf_group_id.html}</td>
       </tr>
       <tr>
         <td class="label"></td>
   <td> {include file="CRM/Contribute/Form/Task.tpl"}</td>
       </tr>
    </table>
    <div class="crm-submit-buttons">{$form.buttons.html}</td></div>
</div>

