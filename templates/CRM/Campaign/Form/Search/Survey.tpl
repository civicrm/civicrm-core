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

{if !$hasSurveys}
    <div class="messages status no-popup">
        <div class="icon inform-icon"></div> &nbsp;
        {ts}No surveys found.{/ts}
    </div>

    <div class="action-link">
         <a href="{crmURL p='civicrm/survey/add' q='reset=1' h=0 }" class="button"><span><div class="icon add-icon"></div>{ts}Add Survey{/ts}</span></a>
    </div>

{elseif $buildSelector}

  {* use to display result set of survey *}
  <div id="survey-result-set-dialog" class="hiddenElement"></div>

  {* load survey selector *}
  {include file="CRM/common/enableDisable.tpl"}

  {literal}
  <script type="text/javascript">
    cj( function( ){
      loadSurveyList( );
    });
  </script>
  {/literal}

  <table id="surveys">
    <thead>
    <tr class="columnheader">
      <th class="hiddenElement">{ts}Survey ID{/ts}</th>
      <th>{ts}Title{/ts}</th>
      <th class="hiddenElement">{ts}Campaign ID{/ts}</th>
      <th>{ts}Campaign{/ts}</th>
      <th class="hiddenElement">{ts}Survey Type ID{/ts}</th>
      <th>{ts}Survey Type{/ts}</th>
      <th>{ts}Release Frequency{/ts}</th>
      <th>{ts}Reserve Each Time{/ts}</th>
      <th>{ts}Total Reserve{/ts}</th>
      <th>{ts}Default?{/ts}</th>
      <th class="hiddenElement">{ts}Is Active?{/ts}</th>
      <th>{ts}Active?{/ts}</th>
      <th></th>
      <th></th>
      <th></th>
    </tr>
    </thead>
    <tbody></tbody>
  </table>

{else}

   <div class="action-link">
      <a href="{crmURL p='civicrm/survey/add' q='reset=1' h=0 }" class="button"><span><div class="icon add-icon"></div>{ts}Add Survey{/ts}</span></a>
   </div>

    {* build search form here *}

    {* Search form and results for surveys *}
    <div class="crm-block crm-form-block crm-search-form-block">
    {assign var='searchForm' value="search_form_$searchFor"}

      <div id="{$searchForm}" class="crm-accordion-wrapper crm-survey_search_form-accordion{if $force and !$buildSelector} collapsed{/if}">
        <div class="crm-accordion-header">
            {ts}Search Surveys{/ts}
        </div><!-- /.crm-accordion-header -->

        <div class="crm-accordion-body">
        {strip}
        <table class="form-layout-compressed">
          <tr>
            <td>{$form.survey_title.label}<br />
                {$form.survey_title.html}
            </td>
          </tr>
          <tr>
            <td>{$form.activity_type_id.label}<br />
                {$form.activity_type_id.html}
            </td>
            <td>{$form.survey_campaign_id.label}<br />
                {$form.survey_campaign_id.html}
            </td>
          </tr>
          <tr>
            <td colspan="2">
            {if $context eq 'search'}
              {$form.buttons.html}
            {else}
              <a class="searchSurvey button" style="float:left;" href="#" title="{ts}Search{/ts}" onClick="searchSurveys( '{$qfKey}' );return false;">{ts}Search{/ts}</a>
            {/if}
            </td>
          </tr>
        </table>
        {/strip}
        </div>
      </div>
    </div>
    {* search form ends here *}

    <div id='surveyList'></div>

{/if} {* end of search form build *}


{literal}
<script type="text/javascript">

 cj(function() {
    cj().crmAccordions();
 });

 {/literal}
 {* load selector when force *}
 {if $force and !$buildSelector}
 {literal}
 cj( function( ) {
    searchSurveys( {/literal}'{$qfKey}'{literal} );
 });

 {/literal}
 {/if}
 {literal}

function searchSurveys( qfKey )
{
      var dataUrl =  {/literal}"{crmURL h=0 q='search=1&snippet=4&type=survey'}"{literal};

      //lets carry qfKey to retain form session.
      if ( qfKey ) dataUrl = dataUrl + '&qfKey=' + qfKey;

      cj.get( dataUrl, null, function( surveyList ) {
        cj( '#surveyList' ).html( surveyList );

        //collapse the search form.
        var searchFormName = '#search_form_' + {/literal}'{$searchFor}'{literal};
        cj( searchFormName + '.crm-accordion-wrapper:not(.collapsed)').crmAccordionToggle();
      }, 'html' );
}

function loadSurveyList( )
{
     var sourceUrl = {/literal}"{crmURL p='civicrm/ajax/rest' h=0 q='snippet=4&className=CRM_Campaign_Page_AJAX&fnName=surveyList' }"{literal};

     //build the search qill.
     //get the search criteria.
     var searchParams   = {/literal}{$searchParams}{literal};
     var surveyTypes    = {/literal}{$surveyTypes}{literal};
     var surveyCampaigns = {/literal}{$surveyCampaigns}{literal};

     var noRecordFoundMsg  = "{/literal}{ts escape='js'}No matches found for:{/ts}{literal}";
     noRecordFoundMsg += '<div class="qill">';

     var count = 0;
     var searchQill = new Array( );
     for ( param in searchParams ) {
        if ( val = cj( '#' + param ).val( ) ) {
      if ( param == 'activity_type_id' ) val = surveyTypes[val];
      if ( param == 'survey_campaign_id' ) val = surveyCampaigns[val];
      searchQill[count++] = searchParams[param] + ' : ' + val;
  }
     }
     noRecordFoundMsg += searchQill.join( '<span class="font-italic"> ...AND... </span></div><div class="qill">' );

     cj( '#surveys' ).dataTable({
             "bFilter"    : false,
             "bAutoWidth" : false,
             "bProcessing": false,
             "bLengthChange": false,
             "aaSorting": [],
             "aoColumns":[{sClass:'crm-survey-id                          hiddenElement' },
                          {sClass:'crm-survey-title'                                     },
                          {sClass:'crm-survey-campaign_id                 hiddenElement' },
                          {sClass:'crm-survey-campaign'                                  },
                          {sClass:'crm-survey-activity_type_id            hiddenElement' },
                          {sClass:'crm-survey-activity_type'                             },
                          {sClass:'crm-survey-release_frequency'                         },
                          {sClass:'crm-survey-default_number_of_contacts'                },
                          {sClass:'crm-survey-max_number_of_contacts'                    },
                          {sClass:'crm-survey-is_default'                                },
                          {sClass:'crm-survey-is_active                   hiddenElement' },
                          {sClass:'crm-survey-isActive'                                  },
                          {sClass:'crm-survey-result_id',                 bSortable:false},
                          {sClass:'crm-survey-action',                    bSortable:false},
                          {sClass:'crm-campaign-voterLinks',              bSortable:false}
           ],
         "sPaginationType": "full_numbers",
             "sDom"       : 'rt<"crm-datatable-pager-bottom"ip>',
             "bServerSide": true,
             "bJQueryUI": true,
             "sAjaxSource": sourceUrl,
             "asStripClasses" : [ "odd-row", "even-row" ],
             "oLanguage":{"sEmptyTable"  : noRecordFoundMsg,
                 "sZeroRecords" : noRecordFoundMsg },
             "fnDrawCallback": function() { cj().crmtooltip(); },
             "fnRowCallback": function( nRow, aData, iDisplayIndex ) {
         //insert the id for each row for enable/disable.
         var rowId = 'survey_row_' + aData[0];
         cj(nRow).attr( 'id', rowId );
         //handled disabled rows.
         var isActive = Boolean(Number(aData[10]));
         if ( !isActive ) cj(nRow).addClass( 'disabled' );

         //add id for yes/no column.
         cj(nRow).children().eq(11).attr( 'id', rowId + '_status' );

         return nRow;
    },

    "fnServerData": function ( sSource, aoData, fnCallback ) {
      var dataLength = aoData.length;

      var count = 1;
      var searchCriteria = new Array( );

      //get the search criteria.
                        var searchParams = {/literal}{$searchParams}{literal};
                        for ( param in searchParams ) {
          fldName = param;
          if ( param == 'survey_title' ) fldName = 'title';
          if ( param == 'survey_campaign_id' ) fldName = 'campaign_id';
                            if ( val = cj( '#' + param ).val( ) ) {
            aoData[dataLength++] = {name: fldName, value: val};
          }
          searchCriteria[count++] = fldName;
                        }

      //do search for surveys.
      aoData[dataLength++] = {name: 'search_for', value: 'survey'};

      //lets transfer search criteria.
      aoData[dataLength++] = {name: 'searchCriteria', value:searchCriteria.join(',')};

      cj.ajax( {
        "dataType": 'json',
        "type": "POST",
        "url": sSource,
        "data": aoData,
        "success": fnCallback
      } ); }
         });
}

function displayResultSet( surveyId, surveyTitle, OptionGroupId ) {
  var data                = new Object;
  data['option_group_id'] = OptionGroupId;
  data['survey_id']       = surveyId;

  var dataUrl  = {/literal}"{crmURL p='civicrm/ajax/rest' h=0 q='className=CRM_Campaign_Page_AJAX&fnName=loadOptionGroupDetails' }"{literal};
  var content  = '<tr><th>{/literal}{ts escape='js'}Label{/ts}{literal}</th><th>{/literal}{ts escape='js'}Value{/ts}{literal}</th><th>{/literal}{ts escape='js'}Recontact Interval{/ts}{literal}</th><th>{/literal}{ts escape='js'}Weight{/ts}{literal}</th></tr>';
  var setTitle = '{/literal}{ts escape='js'}Result Set for{/ts} {literal}' + surveyTitle;

  cj.post( dataUrl, data, function( opGroup ) {
    if ( opGroup.status == 'success' ) {
      var result = opGroup.result;
      for( key in result ) {
        var interval = '';
  if ( result[key].interval && result[key].interval != 'undefined' ) {
    interval = result[key].interval;
  }
        content += '<tr><td>'+  result[key].label +'</td><td>'+ result[key].value +'</td><td>'+ interval +'</td><td>'+ result[key].weight +'</td></tr>';
      }

      cj("#survey-result-set-dialog").show( ).html('<table>'+content+'</table>').dialog({
        title: setTitle,
        modal: true,
        width: 480,
        overlay: {
          opacity: 0.5,
          background: "black"
        },
        beforeclose: function(event, ui) {
          cj(this).dialog("destroy");
        }
      });
    }
  }, "json" );

}

</script>
{/literal}
