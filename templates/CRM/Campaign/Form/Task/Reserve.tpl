{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
<h3>
{ts}Add Respondent Reservation(s){/ts}
</h3>
<div class="crm-form-block crm-block crm-campaign-task-reserve-form-block">
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
<table class="form-layout-compressed">
  <tr class="crm-campaign-task-reserve-form-block-surveytitle">
    <td colspan=2>
      <div class="status">
        <div class="icon inform-icon"></div>&nbsp;{ts 1=$surveyTitle}Do you want to reserve respondents for '%1' ?{/ts}
      </div>
    </td>
  </tr>
  <tr><td colspan=2>{include file="CRM/Contact/Form/Task.tpl"}</td></tr>
</table>

{* Group options *}
 {* New Group *}
 <div id="new-group" class="crm-accordion-wrapper collapsed">
 <div class="crm-accordion-header">
 {ts}Add respondent(s) to a new group{/ts}
 </div><!-- /.crm-accordion-header -->
 <div class="crm-accordion-body">
            <table class="form-layout-compressed">
             <tr>
               <td class="description label">{$form.newGroupName.label}</td>
               <td>{$form.newGroupName.html}</td>
             </tr>
             <tr>
               <td class="description label">{$form.newGroupDesc.label}</td>
               <td>{$form.newGroupDesc.html}</td>
             </tr>
            </table>
 </div><!-- /.crm-accordion-body -->
 </div><!-- /.crm-accordion-wrapper -->


 {* Existing Group *}
 <div class="crm-accordion-wrapper crm-existing_group-accordion {if $hasExistingGroups} {else}collapsed{/if}">
 <div class="crm-accordion-header">
  {ts}Add respondent(s) to existing group(s){/ts}
 </div><!-- /.crm-accordion-header -->
 <div class="crm-accordion-body">

        <div class="form-item">
        <table><tr><td style="width: 14em;"></td><td>{$form.groups.html}</td></tr></table>
        </div>
 </div><!-- /.crm-accordion-body -->
 </div><!-- /.crm-accordion-wrapper -->
{* End of group options *}


<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>

{literal}
<script type="text/javascript">

 cj(function() {
   cj().crmAccordions();
   setDefaultGroup( );
 });

 function setDefaultGroup( )
 {
    var invalidGroupName = {/literal}'{$invalidGroupName}'{literal};
    if ( invalidGroupName ) {
       cj("#new-group.collapsed").crmAccordionToggle();
    } else {
       cj("#newGroupName").val( '' );
       cj("#newGroupDesc").val( '' );
    }
 }
</script>
{/literal}
