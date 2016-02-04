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

{if !$hasCampaigns}
  <div class="messages status no-popup">
    <div class="icon inform-icon"></div>
    &nbsp;
    {ts}None found.{/ts}
  </div>
  <div class="action-link">
    {crmButton p="civicrm/campaign/add" q="reset=1" icon="crm-i fa-plus-circle" h=0}{ts}Add Campaign{/ts}{/crmButton}
  </div>
{elseif $buildSelector}

{* load campaign selector *}

  {include file="CRM/common/enableDisableApi.tpl"}

  {literal}
    <script type="text/javascript">
      CRM.$(function($) {
        loadCampaignList();
      });
    </script>
  {/literal}
  <table class="campaigns">
    <thead>
    <tr class="columnheader">
      <th class="hiddenElement">{ts}Campaign ID{/ts}</th>
      <th class="hiddenElement">{ts}Campaign Name{/ts}</th>
      <th>{ts}Title{/ts}</th>
      <th>{ts}Description{/ts}</th>
      <th>{ts}Start Date{/ts}</th>
      <th>{ts}End Date{/ts}</th>
      <th class="hiddenElement">{ts}Type ID{/ts}</th>
      <th>{ts}Type{/ts}</th>
      <th class="hiddenElement">{ts}Status ID{/ts}</th>
      <th>{ts}Status{/ts}</th>
      <th class="hiddenElement">{ts}Is Active?{/ts}</th>
      <th>{ts}Active?{/ts}</th>
      <th></th>
    </tr>
    </thead>
    <tbody></tbody>
  </table>
{else}
  <div class="action-link">
    {crmButton p="civicrm/campaign/add" q="reset=1" icon="crm-i fa-plus-circle" h=0}{ts}Add Campaign{/ts}{/crmButton}
  </div>
{* build search form here *}

{* Search form and results for campaigns *}
  <div class="crm-block crm-form-block crm-search-form-block">

    {assign var='searchForm' value="search_form_$searchFor"}

    <div id="{$searchForm}"
         class="crm-accordion-wrapper crm-campaign_search_form-accordion {if $force and !$buildSelector}collapsed{/if}">
      <div class="crm-accordion-header">
        {ts}Search Campaigns{/ts}
      </div>
      <!-- /.crm-accordion-header -->

      <div class="crm-accordion-body">
        {strip}
          <table class="form-layout-compressed">
            <tr>
              <td>{$form.campaign_title.label}<br/>
                {$form.campaign_title.html} &nbsp;
              </td>
              <td>
                {$form.description.label}<br/>
                {$form.description.html|crmAddClass:huge}
              </td>
            </tr>

            <tr>
              <td>{$form.start_date.label}<br/>
                {include file="CRM/common/jcalendar.tpl" elementName=start_date}
              </td>
              <td>{$form.end_date.label}<br/>
                {include file="CRM/common/jcalendar.tpl" elementName=end_date}
              </td>
            </tr>

            <tr>
              <td>{$form.campaign_type_id.label}<br/>
                {$form.campaign_type_id.html}
              </td>
              <td>{$form.status_id.label}<br/>
                {$form.status_id.html}
              </td>
              <td>{$form.is_active.label}<br/>
                {$form.is_active.html}
              </td>
            </tr>

            <tr>
              <td colspan="2">
                {if $context eq 'search'}
                  {$form.buttons.html}
                {else}
                  <a class="searchCampaign button" style="float:left;" href="#" title="{ts}Search{/ts}"
                     onClick="searchCampaigns( '{$qfKey}' );return false;">{ts}Search{/ts}</a>
                {/if}
              </td>
            </tr>
          </table>
        {/strip}
      </div>
    </div>
  </div>
{* search form ends here *}
  <div id='campaignList'></div>
{/if} {* end of search form build *}


{literal}
<script type="text/javascript">
(function($) {

  window.searchCampaigns = function searchCampaigns(qfKey) {
    var dataUrl = {/literal}"{crmURL h=0 q='search=1&snippet=4&type=campaign'}"{literal};

    //lets carry qfKey to retain form session.
    if (qfKey) {
      dataUrl = dataUrl + '&qfKey=' + qfKey;
    }

    $.get(dataUrl, null, function (campaignList) {
      $('#campaignList').html(campaignList).trigger('crmLoad');

      //collapse the search form.
      var searchFormName = '#search_form_' + {/literal}'{$searchFor}'{literal};
      $(searchFormName + '.crm-accordion-wrapper:not(.collapsed)').crmAccordionToggle();
    }, 'html');
  };

  window.loadCampaignList = function() {
    var sourceUrl = {/literal}"{crmURL p='civicrm/ajax/rest' h=0 q='snippet=4&className=CRM_Campaign_Page_AJAX&fnName=campaignList' }"{literal};

    //build the search qill.
    //get the search criteria.
    var searchParams = {/literal}{$searchParams}{literal};
    var campaignTypes = {/literal}{$campaignTypes}{literal};
    var campaignStatus = {/literal}{$campaignStatus}{literal};
    var noRecordFoundMsg = '{/literal}{ts escape='js'}No matches found for:{/ts}{literal}';
    noRecordFoundMsg += '<div class="qill">';

    var count = 0;
    var searchQill = [];
    for (param in searchParams) {
      if (val = $('#' + param).val()) {
        if (param == 'status_id') {
          val = campaignStatus[val];
        }
        if (param == 'campaign_type_id') {
          val = campaignTypes[val];
        }
        searchQill[count++] = searchParams[param] + ' : ' + val;
      }
    }
    noRecordFoundMsg += searchQill.join('<span class="font-italic"> ...AND... </span></div><div class="qill">');
    $('table.campaigns', '#campaignList').dataTable({
      "bFilter": false,
      "bAutoWidth": false,
      "bProcessing": false,
      "bLengthChange": false,
      "aaSorting": [],
      "aoColumns": [
        {sClass: 'crm-campaign-id                   hiddenElement' },
        {sClass: 'crm-campaign-name                 hiddenElement' },
        {sClass: 'crmf-title'                              },
        {sClass: 'crmf-description'                        },
        {sClass: 'crm-campaign-start_date'                         },
        {sClass: 'crm-campaign-end_date'                           },
        {sClass: 'crm-campaign-campaign-type_id     hiddenElement' },
        {sClass: 'crmf-campaign_type_id'                      },
        {sClass: 'crm-campaign-campaign-status_id   hiddenElement' },
        {sClass: 'crmf-status_id'                    },
        {sClass: 'crm-campaign-campaign-is_active   hiddenElement' },
        {sClass: 'crm-campaign-campaign-isAactive'                 },
        {sClass: 'crm-campaign-action', bSortable: false}
      ],
      "sPaginationType": "full_numbers",
      "sDom": 'rt<"crm-datatable-pager-bottom"ip>',
      "bServerSide": true,
      "bJQueryUI": true,
      "sAjaxSource": sourceUrl,
      "asStripClasses": ["odd-row", "even-row"],
      "oLanguage": {"sEmptyTable": noRecordFoundMsg,
        "sZeroRecords": noRecordFoundMsg },
      "fnDrawCallback": function () {
        // FIXME: trigger crmLoad and crmEditable would happen automatically
        $('.crm-editable').crmEditable();
      },
      "fnRowCallback": function (nRow, aData, iDisplayIndex) {
        //insert the id for each row for enable/disable.
        var rowId = 'campaign-' + aData[0];
        $(nRow).attr('id', rowId).addClass('crm-entity');
        //handled disabled rows.
        var isActive = Boolean(Number(aData[10]));
        if (!isActive) {
          $(nRow).addClass('disabled');
        }

        // Crm-editable
        $(nRow).children().eq(2).addClass('crm-editable');
        $(nRow).children().eq(3).data('type', 'textarea').addClass('crm-editable');
        $(nRow).children().eq(7).data('type', 'select').addClass('crm-editable');
        $(nRow).children().eq(9).data({type: 'select', emptyOption: ''}).addClass('crm-editable');

        return nRow;
      },

      "fnServerData": function (sSource, aoData, fnCallback) {
        var dataLength = aoData.length;

        var count = 1;
        var searchCriteria = [];

        //get the search criteria.
        var searchParams = {/literal}{$searchParams}{literal};
        for (param in searchParams) {
          fldName = param;
          if (param == 'campaign_title') {
            fldName = 'title';
          }
          if (val = $('#' + param).val()) {
            aoData[dataLength++] = {name: fldName, value: val};
          }
          searchCriteria[count++] = fldName;
        }

        //do search for campaigns.
        aoData[dataLength++] = {name: 'search_for', value: 'campaign'};

        //lets transfer search criteria.
        aoData[dataLength++] = {name: 'searchCriteria', value: searchCriteria.join(',')};

        $.ajax({
          "dataType": 'json',
          "type": "POST",
          "url": sSource,
          "data": aoData,
          "success": fnCallback
        });
      }
    });
  };

  {/literal}
  {* load selector when force *}
  {if $force and !$buildSelector}
  {literal}
  $(function($) {
    searchCampaigns({/literal}'{$qfKey}'{literal});
  });

  {/literal}
  {/if}
  {literal}
})(CRM.$);
</script>
{/literal}
