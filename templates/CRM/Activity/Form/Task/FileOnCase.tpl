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
<div class="crm-form crm-form-block crm-file-on-case-form-block">
<div id="help">
   File on Case
</div>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
   <table class="form-layout-compressed">
      <tr class="crm-file-on-case-form-block-unclosed_cases">
         <td class="label">{$form.unclosed_cases.label}
           <span class="crm-marker" title="{ts}This field is required.{/ts}">*</span></td>
         <td>{$form.unclosed_cases.html}<br />
           <span class="description">{ts}Begin typing client name for a list of open cases.{/ts}</span>
         </td>
      </tr>
     <tr>
        {include file="CRM/Activity/Form/Task.tpl"}
     </tr>
</table>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
{literal}
<script type="text/javascript">

var unclosedCaseUrl = {/literal}"{crmURL p='civicrm/case/ajax/unclosed' h=0}"{literal};
cj( "#unclosed_cases" ).autocomplete( unclosedCaseUrl, { width : 250, selectFirst : false, matchContains:true
                                    }).result( function(event, data, formatted) { 
                                      cj( "#unclosed_case_id" ).val( data[1] );
                                    }).bind( 'click', function( ) { 
                                      cj( "#unclosed_case_id" ).val('');
                                    });
{/literal}
</script>