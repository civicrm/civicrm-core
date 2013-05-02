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
{* this template is used for adding/editing/deleting case *}
{if $addCaseContact }
   {include file="CRM/Contact/Form/AddContact.tpl"}
{else}

<fieldset>
{if $action eq 1}
    <legend>{ts}New Case{/ts}</legend>
{elseif $action eq 2}
    <legend>{ts}Edit Case{/ts}</legend>
{elseif $action eq 8 and !$context}
    <legend>{ts}Delete Case{/ts}</legend>
{elseif $action eq 8 and $context}
    <legend>{ts}Detach Activity From Case{/ts}</legend>
{/if}
    <div class="form-item">
        <table class="form-layout-compressed">

    {if $action eq 8 and $context}
        <div class="status">{ts}Are you sure you want to detach this case from Activity?{/ts}</div>
    {elseif $action eq 8 and !$context}
        <div class="status">{ts}Are you sure you want to delete this case?{/ts} {ts}This action cannot be undone.{/ts}</div>
    {else}
          <tr><td class="label">{$form.subject.label}</td><td>{$form.subject.html}</td></tr>
            <tr><td class="label">&nbsp;</td><td class="description">{ts}Enter the case subject{/ts}</td></tr>
            <tr><td class="label">{$form.status_id.label}</td><td>{$form.status_id.html}</td></tr>
            <tr><td class="label">&nbsp;</td><td class="description">{ts}Select the status for this case{/ts}</td></tr>

             <tr>
            {if $action neq 4 and $search eq false}
              <td class="label">{ts}Add To {/ts}</td><td class="view-value">{$currentlyViewedContact}</td></tr>
             <tr>
                <td class="label">{ts}Add More {/ts}</td>
                <td>
                   <span id="case_contact_1"></span>
                   {edit}<span class="description">{ts}You can optionally add this case to someone. Added case will appear in their Contact Dashboard.{/ts}</span>{/edit}
                </td>
            {else}
                <td class="label">{ts}Add To {/ts}</td><td class="view-value">{$caseContacts}</td>
            {/if}
             </tr>
            <tr><td class="label">{$form.case_type_id.label}</td><td>{$form.case_type_id.html}</td></tr>
          <tr><td class="label">&nbsp;</td><td class="description">{ts}Select the appropriate type of the case {/ts}</td></tr>
            <tr><td class="label">{$form.start_date.label}</td><td>{$form.start_date.html}
              {include file="CRM/common/calendar/desc.tpl" trigger=trigger_case_1}
              {include file="CRM/common/calendar/body.tpl" dateVar=start_date offset=10 trigger=trigger_case_1}
                </td>
            </tr>
            <tr><td class="label">{$form.end_date.label}</td><td>{$form.end_date.html}
                {include file="CRM/common/calendar/desc.tpl" trigger=trigger_case_2}
              {include file="CRM/common/calendar/body.tpl" dateVar=end_date offset=10 trigger=trigger_case_2}
                </td>
            </tr>
           <tr><td class="label">{$form.details.label}</td><td>{$form.details.html}</td></tr>
     {/if}
            <tr> {* <tr> for add / edit form buttons *}
            <td>&nbsp;</td><td>{$form.buttons.html}</td>
          </tr>
       </table>
    </div>
</fieldset>

{* Build add contact *}
{literal}
<script type="text/javascript">
{/literal}
{if $action neq 4 }
{literal}
   buildContact( 1, 'case_contact' );
{/literal}
{/if}
{literal}

var caseContactCount = {/literal}"{$caseContactCount}"{literal}

if ( caseContactCount ) {
    for ( var i = 1; i <= caseContactCount; i++ ) {
  buildContact( i, 'case_contact' );
    }
}

function buildContact( count, pref )
{
    if ( count > 1 ) {
  prevCount = count - 1;
    {/literal}
    {if $action eq 1  OR $action eq 2}
    {literal}
  cj('#' + pref + '_' + prevCount + '_show').hide();
    {/literal}
    {/if}
    {literal}
    }

    var dataUrl = {/literal}"{crmURL h=0 q='snippet=4&count='}"{literal} + count + '&' + pref + '=1';

    var result = dojo.xhrGet({
        url: dataUrl,
        handleAs: "text",
  sync: true,
        timeout: 5000, //Time in milliseconds
        handle: function(response, ioArgs) {
                if (response instanceof Error) {
        if (response.dojoType == "cancel") {
      //The request was canceled by some other JavaScript code.
      console.debug("Request canceled.");
        } else if (response.dojoType == "timeout") {
      //The request took over 5 seconds to complete.
      console.debug("Request timed out.");
        } else {
      //Some other error happened.
      console.error(response);
        }
                } else {
        // on success
        dojo.byId( pref + '_' + count).innerHTML = response;
        dojo.parser.parse( pref + '_' + count );
    }
      }
  });
}
</script>

{/literal}
{/if}
