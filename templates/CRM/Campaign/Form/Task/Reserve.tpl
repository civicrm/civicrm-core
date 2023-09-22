{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<h3>{ts}Add Respondent Reservation(s){/ts}</h3>
<div class="crm-form-block crm-block crm-campaign-task-reserve-form-block">
  <div class="messages status no-popup">
    {icon icon="fa-info-circle"}{/icon}
    {ts 1=$surveyTitle}Do you want to reserve respondents for '%1' ?{/ts}
  </div>
  {include file="CRM/Contact/Form/Task.tpl"}

  {* New Group *}
  <div id="new-group" class="crm-accordion-wrapper collapsed">
    <div class="crm-accordion-header">{ts}Add respondent(s) to a new group{/ts}</div>
    <div class="crm-accordion-body">
            <table class="form-layout-compressed">
             <tr>
               <td class="label">{$form.newGroupName.label}</td>
               <td>{$form.newGroupName.html}</td>
             </tr>
             <tr>
               <td class="label">{$form.newGroupDesc.label}</td>
               <td>{$form.newGroupDesc.html}</td>
             </tr>
            </table>
    </div>
  </div>

  {* Existing Group *}
  <div class="crm-accordion-wrapper crm-existing_group-accordion {if $hasExistingGroups} {else}collapsed{/if}">
    <div class="crm-accordion-header">{ts}Add respondent(s) to existing group(s){/ts}</div>
    <div class="crm-accordion-body">
      <table class="form-layout-compressed">
        <tr>
          <td class="label">{$form.groups.label}</td>
          <td>{$form.groups.html}</td>
        </tr>
      </table>
    </div>
  </div>

  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>

{literal}
<script type="text/javascript">
  CRM.$(function($) {
    setDefaultGroup();
  });

  function setDefaultGroup() {
    var invalidGroupName = {/literal}'{$invalidGroupName}'{literal};
    if (invalidGroupName) {
       cj("#new-group.collapsed").crmAccordionToggle();
    } else {
       cj("#newGroupName").val('');
       cj("#newGroupDesc").val('');
    }
  }
</script>
{/literal}
