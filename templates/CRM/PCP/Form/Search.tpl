{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
<div class="crm-block crm-form-block crm-pcp-search-form-block">

<h3>{ts}Find Campaign Pages{/ts}</h3>
<table class="form-layout">
  <tr>
    <td>
      {$form.title.label}<br />
      {$form.title.html}<br />
      <span class="description font-italic">
          {ts}Complete OR partial pcp Name.{/ts}
      </span>
    </td>
    <td>
      {$form.supporter.label}<br />
      {$form.supporter.html}<br />
      <span class="description font-italic">
          {ts}Complete OR partial pcp supporter Name/Email.{/ts}
      </span>
    </td>
    <td>
      {$form.status_id.label}<br />
      {$form.status_id.html}<br />
      <span class="description font-italic">
          {ts}Filter search by Status.{/ts}
      </span>
    </td>
  </tr>
  <tr>
    <td id="pcp_type-block">
      {$form.page_type.label}<br />
      {$form.page_type.html}<br />
      <span class="description font-italic">
          {ts}Filter search by pcp Type.{/ts}
      </span>
    </td>
    <td>
      {$form.page_id.label}<br />
      {$form.page_id.html|crmAddClass:twenty}<br />
      <span class="description font-italic">
          {ts}Filter search by Contribution Page.{/ts}
      </span>
    </td>
    <td>
      {$form.event_id.label}<br />
      {$form.event_id.html|crmAddClass:twenty}<br />
      <span class="description font-italic">
          {ts}Filter search by Event.{/ts}
      </span>
    </td>
  </tr>
  <tr>
    <td>{$form.start_date.label}<br />
      {include file="CRM/common/jcalendar.tpl" elementName=start_date}<br />
      <span class="description font-italic">
	{ts}Filter search by Contribution/Event Start Date{/ts}
      </span>
    </td>
    <td>{$form.end_date.label}<br />
      {include file="CRM/common/jcalendar.tpl" elementName=end_date}<br />
      <span class="description font-italic">
	{ts}Filter search by Contribution/Event End Date{/ts}
      </span>
    </td>
  </tr>
  <tr>
     <td>{$form.buttons.html}</td><td colspan="2">
  </tr>
</table>
</div>
<table class="crm-pcp-selector">
  <thead>
    <tr>
      <th class='crm-pcp-search-form-block-name'>{ts}Page Title{/ts}</th>
      <th class='crm-pcp-supporter'>{ts}Supporter{/ts}</th>
      <th class='crm-pcp-page-type'>{ts}Contribution Page/Event{/ts}</th>
      <th class='crm-pcp-start'>{ts}Starts{/ts}</th>
      <th class='crm-pcp-end'>{ts}Ends{/ts}</th>
      <th class='crm-pcp-status'>{ts}Status{/ts}</th>
      <th class='crm-pcp-pcp_links nosort'>&nbsp;</th>
      <th class='hiddenElement'>&nbsp;</th>
    </tr>
  </thead>
</table>

{* handle enable/disable actions*}
{include file="CRM/common/enableDisableApi.tpl"}

{literal}
<script type="text/javascript">
CRM.$(function($) {
  buildPcpSelector(true, 1);
  $('#_qf_Search_refresh').click( function() {
    buildPcpSelector( true );
  });
  // Add livePage functionality
  $('#crm-container')
    .on('click', 'a.button, a.action-item[href*="action=delete"]', CRM.popup)
    .on('crmPopupFormSuccess', 'a.button, a.action-item[href*="action=delete"]', function() {
        // Refresh datatable when form completes
        var $context = $('#crm-main-content-wrapper');
        $('table.crm-pcp-selector', $context).dataTable().fnDraw();
    });

  function buildPcpSelector( filterSearch) {
    if ( filterSearch ) {
      if (typeof crmPcpSelector !== 'undefined') {
        crmPcpSelector.fnDestroy();
      }
      var ZeroRecordText = '<div class="status messages">{/literal}{ts escape="js"}No matching PCPs found for your search criteria. Suggestions:{/ts}{literal}<div class="spacer"></div><ul><li>{/literal}{ts escape="js"}Check your spelling.{/ts}{literal}</li><li>{/literal}{ts escape="js"}Try a different spelling or use fewer letters.{/ts}{literal}</li><li>{/literal}{ts escape="js"}Make sure you have enough privileges in the access control system.{/ts}{literal}</li></ul></div>';
    } else {
        var ZeroRecordText = {/literal}'{ts escape="js"}<div class="status messages">No PCPs have been created for this site.{/ts}</div>'{literal};
    }

    var columns = '';
    var sourceUrl = {/literal}'{crmURL p="civicrm/ajax/pcplist" h=0 q="snippet=4"}'{literal};
    var $context = $('#crm-main-content-wrapper');

    crmPcpSelector = $('table.crm-pcp-selector', $context).dataTable({
        "bFilter"    : false,
        "bAutoWidth" : false,
        "aaSorting"  : [],
        "aoColumns"  : [
                        {sClass:'crm-pcp-name'},
                        {sClass:'crm-pcp-supporter'},
                        {sClass:'crm-pcp-page-type'},
                        {sClass:'crm-pcp-start'},
                        {sClass:'crm-pcp-end'},
                        {sClass:'crm-pcp-status'},
                        {sClass:'crm-pcp-pcp_links', bSortable:false},
			{sClass:'hiddenElement', bSortable:false}
                       ],
        "bProcessing": true,
        "asStripClasses" : [ "odd-row", "even-row" ],
        "sPaginationType": "full_numbers",
        "sDom"       : '<"crm-datatable-pager-top"lfp>rt<"crm-datatable-pager-bottom"ip>',
        "bServerSide": true,
        "bJQueryUI": true,
        "sAjaxSource": sourceUrl,
        "iDisplayLength": 25,
        "oLanguage": { "sZeroRecords":  ZeroRecordText,
                       "sProcessing":    {/literal}"{ts escape='js'}Processing...{/ts}"{literal},
                       "sLengthMenu":    {/literal}"{ts escape='js'}Show _MENU_ entries{/ts}"{literal},
                       "sInfo":          {/literal}"{ts escape='js'}Showing _START_ to _END_ of _TOTAL_ entries{/ts}"{literal},
                       "sInfoEmpty":     {/literal}"{ts escape='js'}Showing 0 to 0 of 0 entries{/ts}"{literal},
                       "sInfoFiltered":  {/literal}"{ts escape='js'}(filtered from _MAX_ total entries){/ts}"{literal},
                       "sSearch":        {/literal}"{ts escape='js'}Search:{/ts}"{literal},
                       "oPaginate": {
                            "sFirst":    {/literal}"{ts escape='js'}First{/ts}"{literal},
                            "sPrevious": {/literal}"{ts escape='js'}Previous{/ts}"{literal},
                            "sNext":     {/literal}"{ts escape='js'}Next{/ts}"{literal},
                            "sLast":     {/literal}"{ts escape='js'}Last{/ts}"{literal}
                        }
                    },
        "fnRowCallback": function(nRow, aData, iDisplayIndex, iDisplayIndexFull) {
	  var status = $('.crm-pcp-status', nRow).text();
	  var action = '';
	  if(status=='Approved'){
	    action = 'revert';
	  } else {
	    action = 'renew';
	  }
	  var id = $('td:last', nRow).text().split(',')[0];
          var cl = $('td:last', nRow).text().split(',')[1];
          $(nRow).addClass(cl).attr({id: 'row_' + id, 'data-id': id, 'data-entity': 'PCP', 'action': action});
          return nRow;
        },
        "fnDrawCallback": function() {
          // FIXME: trigger crmLoad and crmEditable would happen automatically
          $('.crm-editable').crmEditable();
        },
        "fnServerData": function ( sSource, aoData, fnCallback ) {
            if ( filterSearch ) {
                aoData.push(
                    {name:'title', value: $('.crm-pcp-search-form-block #title').val()},
                    {name:'page_type', value: $('.crm-pcp-search-form-block #page_type').val()},
                    {name:'page_id', value: $('.crm-pcp-search-form-block #page_id').val()},
                    {name:'event_id', value: $('.crm-pcp-search-form-block #event_id').val()},
                    {name:'status_id', value: $('.crm-pcp-search-form-block #status_id').val()},
                    {name:'supporter', value: $('.crm-pcp-search-form-block #supporter').val()},
                    {name:'start_date', value: $('.crm-pcp-search-form-block #start_date').val()},
                    {name:'end_date', value: $('.crm-pcp-search-form-block #end_date').val()}
                );
            }
            $.ajax( {
                "dataType": 'json',
                "type": "POST",
                "url": sSource,
                "data": aoData,
                "success": fnCallback
            } );
        }
    });
  }

});

</script>
{/literal}
