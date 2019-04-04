{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
{include file="CRM/common/dedupe.tpl"}
<div class="crm-accordion-header">
  {ts}Filter Contacts{/ts}
</div>
<div class="crm-accordion-body">
  <form method="get">
    <table class="no-border form-layout-compressed" id="searchOptions" style="width:100%;">
      <tr>
        <td class="crm-contact-form-block-contact1">
          <label for="search-contact1">{ts}Contact Name{/ts}</label><br />
          <input class="crm-form-text" type="text" size="50" placeholder="Search Contacts" value="{$searchcontact1}" id="search-contact1" search-column="0" />
        </td>
        <td class="crm-contact-form-block-search">
          <label>&nbsp;</label><br />
          <button type="submit" class="button crm-button filtercontacts"><span><i class="crm-i fa-search"></i> Find Contacts</span></button>
        </td>
      </tr>
    </table>
  </form>
</div>


<div class="crm-content-block crm-block">
  {include file="CRM/common/pager.tpl" location="top"}
  {include file='CRM/common/jsortable.tpl'}

  <div id="claim_level-wrapper" class="dataTables_wrapper">
    <table id="claim_level-table" class="display">
      <thead>
        <tr>
          <th>{ts}Contact 1{/ts}</th>
          <th>{ts}Contact 2 (Duplicate){/ts}</th>
          <th data-orderable="false"></th>
        </tr>
      </thead>
      <tbody>
        {assign var="rowClass" value="odd-row"}
        {assign var="rowCount" value=0}

        {foreach from=$exceptions key=errorId item=exception}
        {assign var="rowCount" value=$rowCount+1}

        <tr id="row{$rowCount}" class="{cycle values="odd,even"}">

          <td>
            {assign var="contact1name" value="contact_id1.display_name"}
            <a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$exception.contact_id1`"}" target="_blank">{ $exception.$contact1name }</a>
          </td>
          <td>
            {assign var="contact2name" value="contact_id2.display_name"}
            <a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$exception.contact_id2`"}" target="_blank">{ $exception.$contact2name }</a>
          </td>
          <td>
            <a id='duplicateContacts' href="#" title={ts}Remove Exception{/ts} onClick="processDupes( {$exception.contact_id1}, {$exception.contact_id2}, 'nondupe-dupe', 'dedupe-exception' );return false;">&raquo; {ts}Remove Exception{/ts}</a>
          </td>
        </tr>

        {if $rowClass eq "odd-row"}
          {assign var="rowClass" value="even-row"}
        {else}
          {assign var="rowClass" value="odd-row"}
        {/if}

      {/foreach}
      </tbody>
    </table>
  </div>
  {include file="CRM/common/pager.tpl" location="bottom"}
</div>



<div class="clear"><br /></div>
<div class="action-link">
  {crmButton p="civicrm/contact/deduperules" q="reset=1" icon="times"}{ts}Done{/ts}{/crmButton}
</div>

{* process the dupe contacts *}
{literal}
  <script type="text/javascript">
    CRM.$(function($) {

      {/literal}
        var $form = $({if empty($form.formClass)}'#crm-main-content-wrapper'{else}'form.{$form.formClass}'{/if});
        var currentLocation = {$pager->_response.currentLocation|json_encode};
      {literal}

        var refreshing = false;
        var timer = null;

      // apply the search
      $('.filtercontacts').on( 'click', function (e) {
        e.preventDefault();
        clearTimeout(timer);
        timer = setTimeout(updateTable, 500)
      });

      function updateTable() {

        var contact1term = $('#search-contact1').val();

        currentLocation = currentLocation.replace(/crmPID=\d+/, 'crmPID=' + 0);

        if (currentLocation.indexOf('crmContact1Q') !== -1) {
          currentLocation = currentLocation.replace(/crmContact1Q=\w*/, 'crmContact1Q=' + contact1term);
        }
        else {
          currentLocation += '&crmContact1Q='+contact1term;
        }

        refresh(currentLocation);
      }

      function refresh(url) {
        if (!refreshing) {
          refreshing = true;
          var options = url ? {url: url} : {};
          $form.off('.crm-pager').closest('.crm-ajax-container, #crm-main-content-wrapper').crmSnippet(options).crmSnippet('refresh');
        }
      }


    });
  </script>
{/literal}
