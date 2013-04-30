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
{* template for adding form elements for soft credit form*}

  {include file="CRM/Contact/Form/NewContact.tpl"}
{*  <tr id="softCreditID" class="crm-contribution-form-block-soft_credit_to"><td class="label">{$form.soft_credit_to.label}</td>
    <td {$valueStyle}>
      {$form.soft_credit_to.html} {help id="id-soft_credit"}
      {if $siteHasPCPs}
        <div id="showPCPLink"><a href='#' onclick='showPCP(); return false;'>{ts}credit this contribution to a personal campaign page{/ts}</a>{help id="id-link_pcp"}</div>
      {/if}
    </td>
  </tr>
    {if $siteHasPCPs}
    <tr id="pcpID" class="crm-contribution-form-block-pcp_made_through_id">
      <td class="label">{$form.pcp_made_through.label}</td>
      <td>
        {$form.pcp_made_through.html} &nbsp;
        <span class="showSoftCreditLink">{ts}<a href="#" onclick='showSoftCredit(); return false;'>unlink from personal campaign page</a>{/ts}</span><br />
        <span class="description">{ts}Search for the Personal Campaign Page by the fund-raiser's last name or email address.{/ts}</span>
        <div class="spacer"></div>
        <div class="crm-contribution-form-block-pcp_details">
          <table class="crm-contribution-form-table-credit_to_pcp">
            <tr id="pcpDisplayRollID" class="crm-contribution-form-block-pcp_display_in_roll"><td class="label">{$form.pcp_display_in_roll.label}</td>
              <td>{$form.pcp_display_in_roll.html}</td>
            </tr>
            <tr id="nickID" class="crm-contribution-form-block-pcp_roll_nickname">
              <td class="label">{$form.pcp_roll_nickname.label}</td>
              <td>{$form.pcp_roll_nickname.html|crmAddClass:big}<br />
                <span class="description">{ts}Name or nickname contributor wants to be displayed in the Honor Roll. Enter "Anonymous" for anonymous contributions.{/ts}</span></td>
            </tr>
            <tr id="personalNoteID" class="crm-contribution-form-block-pcp_personal_note">
              <td class="label" style="vertical-align: top">{$form.pcp_personal_note.label}</td>
              <td>{$form.pcp_personal_note.html}
                <span class="description">{ts}Personal message submitted by contributor for display in the Honor Roll.{/ts}</span>
              </td>
            </tr>
          </table>
        </div>
      </td>
    </tr>
    {/if}
*}
 {literal}
 <script type="text/javascript">
 var url = "{/literal}{$dataUrl}{literal}";

  cj('#soft_credit_to').autocomplete( url, { width : 180, selectFirst : false, matchContains: true
  }).result( function(event, data, formatted) {
      cj( "#soft_contact_id" ).val( data[1] );
  });
 {/literal}

// load form during form rule.
{if $buildPriceSet}{literal}buildAmount( );{/literal}{/if}

{if $siteHasPCPs}
  {literal}
  var pcpUrl = "{/literal}{$pcpDataUrl}{literal}";

  cj('#pcp_made_through').autocomplete( pcpUrl, { width : 360, selectFirst : false, matchContains: true
  }).result( function(event, data, formatted) {
      cj( "#pcp_made_through_id" ).val( data[1] );
  });
{/literal}

  {if $pcpLinked}
    {literal}hideSoftCredit( );{/literal}{* hide soft credit on load if we have PCP linkage *}
  {else}
    {literal}cj('#pcpID').hide();{/literal}{* hide PCP section *}
  {/if}

  {literal}
  function hideSoftCredit ( ){
    cj("#softCreditID").hide();
  }
  function showPCP( ) {
    cj('#pcpID').show();
    cj("#softCreditID").hide();
  }
  function showSoftCredit( ) {
    cj('#pcp_made_through_id').val('');
    cj('#pcp_made_through').val('');
    cj('#pcp_roll_nickname').val('');
    cj('#pcp_personal_note').val('');
    cj('#pcp_display_in_roll').attr('checked', false);
    cj("#pcpID").hide();
    cj('#softCreditID').show();
  }
  {/literal}
{/if}
</script>






