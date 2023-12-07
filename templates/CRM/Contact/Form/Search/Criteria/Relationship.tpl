{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div id="relationship" class="form-item">
  <table class="form-layout">
    <tr>
      <td>
        {$form.relation_type_id.label}<br />
        {$form.relation_type_id.html}
      </td>
      <td>
         <div>
           {$form.relation_target_name.label}<br />
           {$form.relation_target_name.html|crmAddClass:huge}
            <div class="description font-italic">
                {ts}Complete OR partial contact name.{/ts}
            </div>
          </div>
      </td>
    </tr>
    <tr>
      <td>
         {$form.relation_status.label}<br />
         {$form.relation_status.html}
         </p>
         {$form.relation_permission.label}<br />
         {$form.relation_permission.html}
      </td>
      <td>
        {$form.relation_target_group.label} {help id="id-relationship-target-group" file="CRM/Contact/Form/Search/Advanced.hlp"}<br />
        {$form.relation_target_group.html|crmAddClass:huge}
      </td>
    </tr>
    <tr>
      <td colspan="2">
        {$form.relation_description.label}<br />
        {$form.relation_description.html}
      </td>
    </tr>
    <tr>
      {include file="CRM/Core/DatePickerRangeWrapper.tpl" fieldName="relationship_start_date"  to='' from='' colspan='' class='' hideRelativeLabel=0}
    </tr>
    <tr>
      {include file="CRM/Core/DatePickerRangeWrapper.tpl" fieldName="relationship_end_date"  to='' from='' colspan='' class='' hideRelativeLabel=0}
    </tr>
    <tr>
      <td colspan="2"><label>{ts}Active Period{/ts}</label> {help id="id-relationship-active-period" file="CRM/Contact/Form/Search/Advanced.hlp"}<br /></td>
    </tr>
    <tr>
      {include file="CRM/Core/DatePickerRangeWrapper.tpl" fieldName="relation_active_period_date" to='' from='' colspan='' class ='' hideRelativeLabel=1}
    </tr>
    {if !empty($relationshipGroupTree)}
      <tr>
      <td colspan="2">
        {include file="CRM/Custom/Form/Search.tpl" groupTree=$relationshipGroupTree showHideLinks=false}
      </td>
      </tr>
    {/if}
  </table>
</div>
