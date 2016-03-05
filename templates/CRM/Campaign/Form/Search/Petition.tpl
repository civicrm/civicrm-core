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

{if !$hasPetitions}
    <div class="messages status no-popup">
        <div class="icon inform-icon"></div> &nbsp;
        {ts}None found.{/ts}
    </div>

    <div class="action-link">
         {crmButton p='civicrm/petition/add' q='reset=1' h=0  icon="plus-circle"}{ts}Add Petition{/ts}{/crmButton}
    </div>

{elseif $buildSelector}

       {* use to display result set of petition *}
       <div id="petition-result-set-dialog" class="hiddenElement"></div>

       {* load petition selector *}

       {include file="CRM/common/enableDisableApi.tpl"}

       {literal}
       <script type="text/javascript">
       CRM.$(function($) {
           loadPetitionList( );
       });
       </script>
       {/literal}

       <table class="petitions">
           <thead>
              <tr class="columnheader">
            <th class="hiddenElement">{ts}Petition ID{/ts}</th>
      <th>{ts}Title{/ts}</th>
      <th class="hiddenElement">{ts}Campaign ID{/ts}</th>
      <th>{ts}Campaign{/ts}</th>
      <th class="hiddenElement">{ts}Petition Type ID{/ts}</th>
      <th class="hiddenElement">{ts}Petition Type{/ts}</th>
      <th>{ts}Default?{/ts}</th>
      <th class="hiddenElement">{ts}Is Active?{/ts}</th>
      <th>{ts}Active?{/ts}</th>
      <th></th>
              </tr>
           </thead>
           <tbody></tbody>
       </table>

{else}

   <div class="action-link">
      {crmButton p='civicrm/petition/add' q='reset=1' h=0  icon="plus-circle"}{ts}Add Petition{/ts}{/crmButton}
   </div>

    {* build search form here *}

    {* Search form and results for petitions *}
    <div class="crm-block crm-form-block crm-search-form-block">

    {assign var='searchForm' value="search_form_$searchFor"}

    <div id="{$searchForm}" class="crm-accordion-wrapper crm-petition_search_form-accordion {if $force and !$buildSelector}collapsed{/if}">
    <div class="crm-accordion-header">
        {ts}Search Petitions{/ts}
    </div><!-- /.crm-accordion-header -->

    <div class="crm-accordion-body">
    {strip}
        <table class="form-layout-compressed">
      <tr>
            <td>{$form.petition_title.label}<br />
            {$form.petition_title.html}
            </td>
          <td>{$form.petition_campaign_id.label}<br />
              {$form.petition_campaign_id.html}
            </td>
      </tr>
        <tr>
            <td colspan="2">
            {if $context eq 'search'}
              {$form.buttons.html}
          {else}
              <a class="searchPetition button" style="float:left;" href="#" title={ts}Search{/ts} onClick="searchPetitions( '{$qfKey}' );return false;">{ts}Search{/ts}</a>
          {/if}
          </td>
        </tr>
        </table>
    {/strip}
    </div>
    </div>
    </div>
    {* search form ends here *}

    <div id='petitionList'></div>

{/if} {* end of search form build *}


{literal}
<script type="text/javascript">

 {/literal}
 {* load selector when force *}
 {if $force and !$buildSelector}
 {literal}
 CRM.$(function($) {
    searchPetitions( {/literal}'{$qfKey}'{literal} );
 });

 {/literal}
 {/if}
 {literal}

function searchPetitions( qfKey )
{
      var dataUrl =  {/literal}"{crmURL h=0 q='search=1&snippet=4&type=petition'}"{literal};

      //lets carry qfKey to retain form session.
      if ( qfKey ) dataUrl = dataUrl + '&qfKey=' + qfKey;

      CRM.$.get( dataUrl, null, function( petitionList ) {
        CRM.$( '#petitionList' ).html( petitionList ).trigger('crmLoad');

        //collapse the search form.
        var searchFormName = '#search_form_' + {/literal}'{$searchFor}'{literal};
        CRM.$( searchFormName + '.crm-accordion-wrapper:not(.collapsed)').crmAccordionToggle();
      }, 'html' );
}

function loadPetitionList( )
{
     var sourceUrl = {/literal}"{crmURL p='civicrm/ajax/rest' h=0 q='snippet=4&className=CRM_Campaign_Page_AJAX&fnName=petitionList' }"{literal};

     //build the search qill.
     //get the search criteria.
     var searchParams   = {/literal}{$searchParams}{literal};
     var campaigns      = {/literal}{$petitionCampaigns}{literal};

     var noRecordFoundMsg  = '{/literal}{ts escape='js'}No matches found for:{/ts}{literal}';
     noRecordFoundMsg += '<div class="qill">';

     var count = 0;
     var searchQill = [];
     for ( param in searchParams ) {
       if ( val = CRM.$( '#' + param ).val( ) ) {
         if ( param == 'petition_campaign_id' ) val = campaigns[val];
         searchQill[count++] = searchParams[param] + ' : ' + val;
       }
     }
     noRecordFoundMsg += searchQill.join( '<span class="font-italic"> ...AND... </span></div><div class="qill">' );
     CRM.$( 'table.petitions', '#petitionList').dataTable({
             "bFilter"    : false,
             "bAutoWidth" : false,
             "bProcessing": false,
             "bLengthChange": false,
             "aaSorting": [],
             "aoColumns":[
                 {sClass:'crm-petition-id                          hiddenElement' },
                 {sClass:'crmf-title'                                     },
                 {sClass:'crm-petition-campaign_id                 hiddenElement' },
                 {sClass:'crmf-campaign_id'                                  },
                 {sClass:'crm-petition-activity_type_id            hiddenElement' },
                 {sClass:'crm-petition-activity_type               hiddenElement' },
                 {sClass:'crm-petition-is_default'                                },
                 {sClass:'crm-petition-is_active                   hiddenElement' },
                 {sClass:'crm-petition-isActive'                                  },
                 {sClass:'crm-petition-action',                    bSortable:false}
             ],
             "sPaginationType": "full_numbers",
             "sDom"       : 'rt<"crm-datatable-pager-bottom"ip>',
             "bServerSide": true,
             "bJQueryUI": true,
             "sAjaxSource": sourceUrl,
             "asStripClasses" : [ "odd-row", "even-row" ],
             "oLanguage":{"sEmptyTable"  : noRecordFoundMsg,
                 "sZeroRecords" : noRecordFoundMsg },
             "fnDrawCallback": function() {
               // FIXME: trigger crmLoad and crmEditable would happen automatically
               CRM.$('.crm-editable').crmEditable();
             },
             "fnRowCallback": function( nRow, aData, iDisplayIndex ) {

               // Crm-editable
               CRM.$(nRow).children().eq(1).addClass('crm-editable');

         //insert the id for each row for enable/disable.
         var rowId = 'survey-' + aData[0];
         CRM.$(nRow).attr( 'id', rowId).addClass('crm-entity');
         //handled disabled rows.
         var isActive = Boolean(Number(aData[7]));
         if ( !isActive ) CRM.$(nRow).addClass( 'disabled' );

         //add id for yes/no column.
         CRM.$(nRow).children().eq(8).attr( 'id', rowId + '_status' );

         return nRow;
    },

    "fnServerData": function ( sSource, aoData, fnCallback ) {
      var dataLength = aoData.length;

      var count = 1;
      var searchCriteria = [];

      //get the search criteria.
                        var searchParams = {/literal}{$searchParams}{literal};
                        for ( param in searchParams ) {
          fldName = param;
          if ( param == 'petition_title' ) fldName = 'title';
          if ( param == 'petition_campaign_id' ) fldName = 'campaign_id';
                            if ( val = CRM.$( '#' + param ).val( ) ) {
            aoData[dataLength++] = {name: fldName, value: val};
          }
          searchCriteria[count++] = fldName;
                        }

      //do search for petitions.
      aoData[dataLength++] = {name: 'search_for', value: 'petition'};

      //lets transfer search criteria.
      aoData[dataLength++] = {name: 'searchCriteria', value:searchCriteria.join(',')};

      CRM.$.ajax( {
        "dataType": 'json',
        "type": "POST",
        "url": sSource,
        "data": aoData,
        "success": fnCallback
      } ); }
         });
}

</script>
{/literal}
