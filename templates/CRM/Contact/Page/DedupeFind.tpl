{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
{if $action eq 2 || $action eq 16}
<div class="form-item">
  <div class="crm-accordion-wrapper crm-search_filters-accordion">
    <div class="crm-accordion-header">
    {ts}Filter Contacts{/ts}</a>
    </div><!-- /.crm-accordion-header -->
    <div class="crm-accordion-body">
      <table class="no-border form-layout-compressed" id="searchOptions" style="width:100%;">
        <tr>
          <td class="crm-contact-form-block-contact1">
            <label for="contact1">{ts}Contact 1{/ts}</label><br />
            <input type="text" placeholder="Search Contact1" search-column="2" />
          </td>
          <td class="crm-contact-form-block-contact2">
            <label for="contact2">{ts}Contact 2{/ts}</label><br />
            <input type="text" placeholder="Search Contact2" search-column="4" />
          </td>
          <td class="crm-contact-form-block-email1">
            <label for="email1">{ts}Email 1{/ts}</label><br />
            <input type="text" placeholder="Search Email1" search-column="5" />
          </td>
          <td class="crm-contact-form-block-email2">
            <label for="email2">{ts}Email 2{/ts}</label><br />
            <input type="text" placeholder="Search Email2" search-column="6" />
          </td>
        </tr>
        <tr>
          <td class="crm-contact-form-block-street-address1">
            <label for="street-adddress1">{ts}Street Address 1{/ts}</label><br />
            <input type="text" placeholder="Search Street Address1" search-column="7" />
          </td>
          <td class="crm-contact-form-block-street-address2">
            <label for="street-adddress2">{ts}Street Address 2{/ts}</label><br />
            <input type="text" placeholder="Search Street Address2" search-column="8" />
          </td>
          <td class="crm-contact-form-block-postcode1">
            <label for="postcode1">{ts}Postcode 1{/ts}</label><br />
            <input type="text" placeholder="Search Postcode1" search-column="9" />
          </td>
          <td class="crm-contact-form-block-postcode2">
            <label for="postcode2">{ts}Postcode 2{/ts}</label><br />
            <input type="text" placeholder="Search Postcode2" search-column="10" />
          </td>
        </tr>
      </table>
    </div><!-- /.crm-accordion-body -->
  </div><!-- /.crm-accordion-wrapper -->
  <div>
    Show / Hide columns:
    <input type='checkbox' id ='steet-address' class='toggle-vis' data-column-main="7" data-column-dupe="8" >
        <label for="steet-address">{ts}Street Address{/ts}&nbsp;</label>
    <input type='checkbox' id ='post-code' class='toggle-vis' data-column-main="9" data-column-dupe="10" >
        <label for="post-code">{ts}Post Code{/ts}&nbsp;</label>
    <input type='checkbox' id ='conflicts' class='toggle-vis' data-column-main="11"  >
        <label for="conflicts">{ts}Conflicts{/ts}&nbsp; </label>
    <input type='checkbox' id ='threshold' class='toggle-vis' data-column-main="12"  >
        <label for="threshold">{ts}Threshold{/ts}&nbsp;</label>
  </div><br/>
  <span id="dupePairs_length_selection">
    <input type='checkbox' id ='crm-dedupe-display-selection' name="display-selection">
    <label for="display-selection">{ts}Within Selections{/ts}&nbsp;</label>
  </span>

  <table id="dupePairs"
    class="nestedActivitySelector crm-ajax-table"
    cellspacing="0"
    width="100%"
    data-page-length="10",
    data-searching='true',
    data-dom='flrtip',
    data-order='[]',
    data-column-defs='{literal}[{"targets": [0,1,3,13], "orderable":false}, {"targets": [7,8,9,10,11,12], "visible":false}]{/literal}'>
    <thead>
      <tr class="columnheader">
        <th data-data="is_selected_input" class="crm-dedupe-selection"><input type="checkbox" value="0" name="pnid_all" class="crm-dedupe-select-all"></th>
        <th data-data="src_image"    class="crm-empty">&nbsp;</th>
        <th data-data="src"          class="crm-contact">{ts}Contact{/ts} 1</th>
        <th data-data="dst_image"    class="crm-empty">&nbsp;</th>
        <th data-data="dst"          class="crm-contact-duplicate">{ts}Contact{/ts} 2 ({ts}Duplicate{/ts})</th>
        <th data-data="src_email"    class="crm-contact">{ts}Email{/ts} 1</th>
        <th data-data="dst_email"    class="crm-contact-duplicate">{ts}Email{/ts} 2 ({ts}Duplicate{/ts})</th>
        <th data-data="src_street"   class="crm-contact">{ts}Street Address{/ts} 1</th>
        <th data-data="dst_street"   class="crm-contact-duplicate">{ts}Street Address{/ts} 2 ({ts}Duplicate{/ts})</th>
        <th data-data="src_postcode" class="crm-contact">{ts}Postcode{/ts} 1</th>
        <th data-data="dst_postcode" class="crm-contact-duplicate">{ts}Postcode{/ts} 2 ({ts}Duplicate{/ts})</th>
        <th data-data="conflicts"    class="crm-contact-conflicts">{ts}Conflicts{/ts}</th>
        <th data-data="weight"       class="crm-threshold">{ts}Threshold{/ts}</th>
        <th data-data="actions"      class="crm-empty">&nbsp;</th>
      </tr>
    </thead>
    <tbody>
    </tbody>
  </table>
  {if $cid}
    <table style="width: 45%; float: left; margin: 10px;">
      <tr class="columnheader"><th colspan="2">{ts 1=$main_contacts[$cid]}Merge %1 with{/ts}</th></tr>
      {foreach from=$dupe_contacts[$cid] item=dupe_name key=dupe_id}
        {if $dupe_name}
          {capture assign=link}<a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=$dupe_id"}">{$dupe_name}</a>{/capture}
          {capture assign=merge}<a href="{crmURL p='civicrm/contact/merge' q="reset=1&cid=$cid&oid=$dupe_id"}">{ts}merge{/ts}</a>{/capture}
          <tr class="{cycle values="odd-row,even-row"}">
      <td>{$link}</td>
      <td style="text-align: right">{$merge}</td>
      <td style="text-align: right"><a class='crm-notDuplicate' href="#" title={ts}not a duplicate{/ts} onClick="processDupes( {$main.srcID}, {$main.dstID}, 'dupe-nondupe' );return false;">{ts}not a duplicate{/ts}</a></td>
      </tr>
        {/if}
      {/foreach}
    </table>
  {/if}
</div>

{if $context eq 'search'}
   {crmButton href=$backURL icon="times"}{ts}Done{/ts}{/crmButton}
{elseif $context eq 'conflicts'}
   {if call_user_func(array('CRM_Core_Permission','check'), 'force merge duplicate contacts')}
     {if $gid}
       {capture assign=backURL}{crmURL p="civicrm/contact/dedupemerge" q="reset=1&rgid=`$rgid`&gid=`$gid`&action=map&mode=aggressive" a=1}{/capture}
     {else}
       {capture assign=backURL}{crmURL p="civicrm/contact/dedupemerge" q="reset=1&rgid=`$rgid`&action=map&mode=aggressive" a=1}{/capture}
     {/if}
     <a href="{$backURL}" title="{ts}Force Merge Selected Duplicates{/ts}" onclick="return confirm('{ts escape="js"}This will run the batch merge process on the selected duplicates. The operation will run in force merge mode - all selected duplicates will be merged into main contacts even in case of any conflicts. Click OK to proceed if you are sure you wish to run this operation.{/ts}');" class="button"><span><i class="crm-i fa-bolt"></i> {ts}Force Merge Selected Duplicates{/ts}</span></a>

     {if $gid}
       {capture assign=backURL}{crmURL p="civicrm/contact/dedupemerge" q="reset=1&rgid=`$rgid`&gid=`$gid`&action=map" a=1}{/capture}
     {else}
       {capture assign=backURL}{crmURL p="civicrm/contact/dedupemerge" q="reset=1&rgid=`$rgid`&action=map" a=1}{/capture}
     {/if}
     <a href="{$backURL}" title="{ts}Safe Merge Selected Duplicates{/ts}" onclick="return confirm('{ts escape="js"}This will run the batch merge process on the selected duplicates. The operation will run in safe mode - only records with no direct data conflicts will be merged. Click OK to proceed if you are sure you wish to run this operation.{/ts}');" class="button"><span><i class="crm-i fa-compress"></i> {ts}Safe Merge Selected Duplicates{/ts}</span></a>
   {/if}

   {if $gid}
      {capture assign=backURL}{crmURL p="civicrm/contact/dedupefind" q="reset=1&action=update&rgid=`$rgid`&gid=`$gid`" a=1}{/capture}
   {else}
      {capture assign=backURL}{crmURL p="civicrm/contact/dedupefind" q="reset=1&action=update&rgid=`$rgid`" a=1}{/capture}
   {/if}
   <a href="{$backURL}" title="{ts}List All Duplicates{/ts}" class="button"><span><i class="crm-i fa-refresh"></i> {ts}List All Duplicates{/ts}</span></a>
{else}
   {if $gid}
      {capture assign=backURL}{crmURL p="civicrm/contact/dedupefind" q="reset=1&rgid=`$rgid`&gid=`$gid`&action=renew" a=1}{/capture}
   {else}
      {capture assign=backURL}{crmURL p="civicrm/contact/dedupefind" q="reset=1&rgid=`$rgid`&action=renew" a=1}{/capture}
   {/if}
   <a href="{$backURL}" title="{ts}Refresh List of Duplicates{/ts}" onclick="return confirm('{ts escape="js"}This will refresh the duplicates list. Click OK to proceed.{/ts}');" class="button">
     <span><i class="crm-i fa-refresh"></i> {ts}Refresh Duplicates{/ts}</span>
   </a>

   {if $gid}
      {capture assign=backURL}{crmURL p="civicrm/contact/dedupemerge" q="reset=1&rgid=`$rgid`&gid=`$gid`&action=map" a=1}{/capture}
   {else}
      {capture assign=backURL}{crmURL p="civicrm/contact/dedupemerge" q="reset=1&rgid=`$rgid`&action=map" a=1}{/capture}
   {/if}
   <a href="{$backURL}" title="{ts}Batch Merge Duplicate Contacts{/ts}" onclick="return confirm('{ts escape="js"}This will run the batch merge process on the selected duplicates. The operation will run in safe mode - only records with no direct data conflicts will be merged. Click OK to proceed if you are sure you wish to run this operation.{/ts}');" class="button"><span><i class="crm-i fa-compress"></i> {ts}Batch Merge Selected Duplicates{/ts}</span></a>

   {if $gid}
      {capture assign=backURL}{crmURL p="civicrm/contact/dedupemerge" q="reset=1&rgid=`$rgid`&gid=`$gid`" a=1}{/capture}
   {else}
      {capture assign=backURL}{crmURL p="civicrm/contact/dedupemerge" q="reset=1&rgid=`$rgid`" a=1}{/capture}
   {/if}
   <a href="{$backURL}" title="{ts}Batch Merge Duplicate Contacts{/ts}" onclick="return confirm('{ts escape="js"}This will run the batch merge process on the listed duplicates. The operation will run in safe mode - only records with no direct data conflicts will be merged. Click OK to proceed if you are sure you wish to run this operation.{/ts}');" class="button"><span><i class="crm-i fa-compress"></i> {ts}Batch Merge All Duplicates{/ts}</span></a>

   <a href='#' title="{ts}Flip Selected Duplicates{/ts}" class="crm-dedupe-flip-selections button"><span><i class="crm-i fa-exchange"></i> {ts}Flip Selected Duplicates{/ts}</span></a>

   {capture assign=backURL}{crmURL p="civicrm/contact/deduperules" q="reset=1" a=1}{/capture}
   <a href="{$backURL}" class="button crm-button-type-cancel">
     <span><i class="crm-i fa-times"></i> {ts}Done{/ts}</span>
   </a>
{/if}
<div style="clear: both;"></div>
{else}
{include file="CRM/Contact/Form/DedupeFind.tpl"}
{/if}

{* process the dupe contacts *}
{include file='CRM/common/dedupe.tpl'}
{literal}
<script type="text/javascript">
  (function($) {
    CRM.$('table#dupePairs').data({
      "ajax": {
        "url": {/literal}'{$sourceUrl}'{literal}
      },
      rowCallback: function (row, data) {
        // Set the checked state of the checkbox in the table
        $('input.crm-dedupe-select', row).prop('checked', data.is_selected == 1);
        if (data.is_selected == 1) {
          $(row).toggleClass('crm-row-selected');
        }
        // for action column at the last, set nowrap
        $('td:last', row).attr('nowrap','nowrap');
        // for conflcts column
        var col = CRM.$('table#dupePairs thead th.crm-contact-conflicts').index();
        $('td:eq(' + col + ')', row).attr('nowrap','nowrap');
      }
    });
    $(function($) {
      $('.button').click(function() {
        // no unsaved changes confirmation dialogs
        $('[data-warn-changes=true]').attr('data-warn-changes', 'false');
      });

      var sourceUrl = {/literal}'{$sourceUrl}'{literal};
      var context   = {/literal}'{$context}'{literal};

      // redraw datatable if searching within selected records
      $('#crm-dedupe-display-selection').on('click', function(){
        reloadUrl = sourceUrl;
        if($(this).prop('checked')){
          reloadUrl = sourceUrl+'&selected=1';
        }
        CRM.$('table#dupePairs').DataTable().ajax.url(reloadUrl).draw();
      });

      $('#dupePairs_length_selection').appendTo('#dupePairs_length');

      // apply selected class on click of a row
      $('#dupePairs tbody').on('click', 'tr', function(e) {
        $(this).toggleClass('crm-row-selected');
        $('input.crm-dedupe-select', this).prop('checked', $(this).hasClass('crm-row-selected'));
        var ele = $('input.crm-dedupe-select', this);
        toggleDedupeSelect(ele, 0);
      });

      // when select-all checkbox is checked
      $('#dupePairs thead tr .crm-dedupe-selection').on('click', function() {
        var checked = $('.crm-dedupe-select-all').prop('checked');
        if (checked) {
          $("#dupePairs tbody tr input[type='checkbox']").prop('checked', true);
          $("#dupePairs tbody tr").addClass('crm-row-selected');
        }
        else{
          $("#dupePairs tbody tr input[type='checkbox']").prop('checked', false);
          $("#dupePairs tbody tr").removeClass('crm-row-selected');
        }
        var ele = $('#dupePairs tbody tr');
        toggleDedupeSelect(ele, 1);
      });

      // inline search boxes placed in tfoot
      $('#dupePairsColFilters thead th').each( function () {
        var title = $('#dupePairs thead th').eq($(this).index()).text();
        if (title.length > 1) {
          $(this).html( '<input type="text" placeholder="Search '+title+'" />' );
        }
      });

      // get dataTable
      var table = CRM.$('table#dupePairs').DataTable();

      // apply the search
      $('#searchOptions input').on( 'keyup change', function () {
        table
          .column($(this).attr('search-column'))
          .search(this.value)
          .draw();
      });

      // show / hide columns
      $('input.toggle-vis').on('click', function (e) {
        var column = table.column( $(this).attr('data-column-main') );
        column.visible( ! column.visible() );

        // nowrap to conflicts column is applied only during initial rendering
        // for show / hide clicks we need to set it explicitly
        var col = CRM.$('table#dupePairs thead th.crm-contact-conflicts').index() + 1;
        if (col > 0) {
          CRM.$('table#dupePairs tbody tr td:nth-child(' + col + ')').attr('nowrap','nowrap');
        }

        if ($(this).attr('data-column-dupe')) {
          column = table.column( $(this).attr('data-column-dupe') );
          column.visible( ! column.visible() );
        }
      });

      // keep the conflicts checkbox checked when context is "conflicts"
      if(context == 'conflicts') {
        $('#conflicts').attr('checked', true);
        var column = table.column( $('#conflicts').attr('data-column-main') );
        column.visible( ! column.visible() );
      }

      // on click of flip link of a row
      $('#dupePairs tbody').on('click', 'tr .crm-dedupe-flip', function(e) {
        e.stopPropagation();
        var $el   = $(this);
        var $elTr = $(this).closest('tr');
        var postUrl = {/literal}"{crmURL p='civicrm/ajax/flipDupePairs' h=0 q='snippet=4'}"{literal};
        var request = $.post(postUrl, {pnid : $el.data('pnid')});
        request.done(function(dt) {
          var mapper = {1:3, 2:4, 5:6, 7:8, 9:10}
          var idx = table.row($elTr).index();
          $.each(mapper, function(key, val) {
            var v1  = table.cell(idx, key).data();
            var v2  = table.cell(idx, val).data();
            table.cell(idx, key).data(v2);
            table.cell(idx, val).data(v1);
          });
          // keep the checkbox checked if needed
          $('input.crm-dedupe-select', $elTr).prop('checked', $elTr.hasClass('crm-row-selected'));
        });
      });

      $(".crm-dedupe-flip-selections").on('click', function(e) {
        var ids = [];
        $('.crm-row-selected').each(function() {
          var ele = CRM.$('input.crm-dedupe-select', this);
          ids.push(CRM.$(ele).attr('name').substr(5));
        });
        if (ids.length > 0) {
          var dataUrl = {/literal}"{crmURL p='civicrm/ajax/flipDupePairs' h=0 q='snippet=4'}"{literal};
          CRM.$.post(dataUrl, {pnid: ids}, function (response) {
            var mapper = {1:3, 2:4, 5:6, 7:8, 9:10}
            $('.crm-row-selected').each(function() {
              var idx = table.row(this).index();
              $.each(mapper, function(key, val) {
                var v1  = table.cell(idx, key).data();
                var v2  = table.cell(idx, val).data();
                table.cell(idx, key).data(v2);
                table.cell(idx, val).data(v1);
              });
              // keep the checkbox checked if needed
              $('input.crm-dedupe-select', this).prop('checked', $(this).hasClass('crm-row-selected'));
            });
          }, 'json');
        }
      });
    });
  })(CRM.$);

  function toggleDedupeSelect(element, isMultiple) {
    if (!isMultiple) {
      var is_selected = CRM.$(element).prop('checked') ? 1: 0;
      var id = CRM.$(element).prop('name').substr(5);
    }
    else {
      var id = [];
      CRM.$(element).each(function() {
        var sth = CRM.$('input.crm-dedupe-select', this);
        id.push(CRM.$(sth).prop('name').substr(5));
      });
      var is_selected = CRM.$('.crm-dedupe-select-all').prop('checked') ? 1 : 0;
    }

    var dataUrl = {/literal}"{crmURL p='civicrm/ajax/toggleDedupeSelect' h=0 q='snippet=4'}"{literal};
    var rgid = {/literal}"{$rgid}"{literal};
    var gid = {/literal}"{$gid}"{literal};

    rgid = rgid.length > 0 ? rgid : 0;
    gid  = gid.length > 0 ? gid : 0;

    CRM.$.post(dataUrl, {pnid: id, rgid: rgid, gid: gid, is_selected: is_selected}, function (data) {
      // nothing to do for now
    }, 'json');
  }
</script>
{/literal}
