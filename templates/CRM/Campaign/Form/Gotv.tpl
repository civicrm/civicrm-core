{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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

{if $errorMessages}
  <div class="messages status crm-error no-popup">
     <div class="icon inform-icon"></div>
        <ul>
          {foreach from=$errorMessages item=errorMsg}
            <li>{ts}{$errorMsg}{/ts}</li>
          {/foreach}
       </ul>
     </div>
  </div>

{elseif $buildSelector}

       {* load voter selector for reserve/release *}

       {literal}
       <script type="text/javascript">
       CRM.$(function($) {
           loadVoterList( );
       });
       </script>
       {/literal}

       <table class="gotvVoterRecords">
           <thead>
              <tr class="columnheader">
            <th></th>
                  <th>{ts}Name{/ts}</th>
            <th>{ts}Street Address{/ts}</th>
            <th>{ts}Street Name{/ts}</th>
            <th>{ts}Street Number{/ts}</th>
            <th>{ts}Street Unit{/ts}</th>
            {if $searchVoterFor eq 'release'}
            <th>{ts}Is Interview Conducted?{/ts}</th>
            {elseif $searchVoterFor eq 'gotv'}
            <th>{ts}Voted?{/ts}</th>
            {else}
            <th>{ts}Is Reserved?{/ts}</th>
            {/if}
              </tr>
           </thead>
           <tbody></tbody>
       </table>

{else}

    {* build search form *}
    {include file='CRM/Campaign/Form/Search/Common.tpl' context='gotv'}
    <div id='voterList'></div>

{/if} {* end of search form build *}


{literal}
<script type="text/javascript">

 {/literal}
 {* load selector when force *}
 {if $force and !$buildSelector}
 {literal}
 CRM.$(function($) {
    searchVoters( {/literal}'{$qfKey}'{literal} );
 });

 {/literal}
 {/if}
 {literal}

function searchVoters( qfKey )
{
      var dataUrl =  {/literal}"{crmURL h=0 q='search=1&snippet=4'}"{literal};

      //carry survey and interviewer id,
      //might be helpful if user jump from current tab to interview tab.
      var surveyId = CRM.$( '#campaign_survey_id' ).val();
      var interviewerId = CRM.$( '#survey_interviewer_id' ).val();
      if ( surveyId ) dataUrl = dataUrl + '&sid=' + surveyId;
      if ( interviewerId ) dataUrl = dataUrl + '&cid=' + interviewerId;

      //lets carry qfKey to retain form session.
      if ( qfKey ) dataUrl = dataUrl + '&qfKey=' + qfKey;

      CRM.$.get( dataUrl, null, function( voterList ) {
        CRM.$( '#voterList' ).html( voterList ).trigger('crmLoad');

        //collapse the search form.
        var searchFormName = '#search_form_' + {/literal}'{$searchVoterFor}'{literal};
        CRM.$( searchFormName + '.crm-accordion-wrapper:not(.collapsed)').crmAccordionToggle();
      }, 'html' );
}

function loadVoterList( )
{
     var sourceUrl = {/literal}"{crmURL p='civicrm/ajax/rest' h=0 q='snippet=4&className=CRM_Campaign_Page_AJAX&fnName=voterList' }"{literal};

     var searchVoterFor = {/literal}'{$searchVoterFor}'{literal};
     CRM.$( 'table.gotvVoterRecords', 'form.{/literal}{$form.formClass}{literal}').dataTable({
               "bFilter"    : false,
    "bAutoWidth" : false,
        "bProcessing": true,
    "bJQueryUI"  : true,
                "aaSorting": [[0,''],[1,'asc'], [2,'asc'], [3,'asc'], [4,'asc'], [5,'asc'] ],
    "aoColumns":[{bSortable:false},{sClass:""},{sClass:""},{sClass:""},{sClass:""},{sClass:""},{bSortable:false}],
    "sPaginationType": "full_numbers",
    "sDom"       : '<"crm-datatable-pager-top"lfp>rt<"crm-datatable-pager-bottom"ip>',
       "bServerSide": true,
       "sAjaxSource": sourceUrl,
    "fnDrawCallback": function() { CRM.$().crmtooltip(); },

    "fnServerData": function ( sSource, aoData, fnCallback ) {
      var dataLength = aoData.length;

      var count = 1;
      var searchCriteria = ['campaign_search_voter_for'];

      //get the search criteria.
                        var searchParams = {/literal}{$searchParams}{literal};
                        for ( param in searchParams ) {
                            if ( val = CRM.$( '#' + param ).val( ) ) {
            aoData[dataLength++] = {name: param , value: val };
          }
          searchCriteria[count++] = param;
                        }

      //do search to reserve voters.
      aoData[dataLength++] = {name: 'campaign_search_voter_for', value: searchVoterFor};

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

function processVoterData( element, operation )
{

  var data = new Object;
  if ( !operation ) return;

  var data = new Object;
  if ( operation == 'release' ) {
         data['operation']   = operation;
  data['activity_id'] = CRM.$( element ).val( );
  data['isDelete']    = CRM.$( element ).prop('checked') ? 1:0;
  } else if ( operation == 'reserve' ) {
        var interviewerId           = CRM.$( '#survey_interviewer_id' ).val( );
        data['operation']           = operation;
        data['source_record_id']    = CRM.$( '#campaign_survey_id' ).val( );
  data['target_contact_id']   = CRM.$( element ).val( );
        data['source_contact_id']   = interviewerId;
        data['assignee_contact_id'] = interviewerId;
  data['isReserved']          = CRM.$( element ).prop('checked') ? 1:0;
  } else if ( operation == 'gotv' ) {
         data['operation']   = operation;
  data['activity_id'] = CRM.$( element ).val( );
  data['hasVoted']    = CRM.$( element ).prop('checked') ? 1: 0;
  }
  data['surveyTitle'] = {/literal}'{$surveyTitle|escape:javascript}'{literal};

  var actUrl = {/literal}
         "{crmURL p='civicrm/ajax/rest' h=0 q='className=CRM_Campaign_Page_AJAX&fnName=processVoterData'}"
         {literal};

  //post data to save voter as voted/non voted.
  CRM.$.post( actUrl,
       data,
     function( response ) {
         if ( response.status == 'success' ) {
                   var msgId = '#success_msg_' + CRM.$( element ).val( );
       CRM.$( msgId ).fadeIn('slow').fadeOut('slow');
       if ( operation == 'release' ) {
                 msg = '{/literal}{ts escape='js'}Save as voted.{/ts}{literal}';
           var isDeleted = CRM.$( element ).prop('checked') ? 1:0;
           if ( !isDeleted ) msg = '{/literal}{ts escape='js'}Save as non voted.{/ts}{literal}';
       } else if ( operation == 'gotv' ) {
           msg = '{/literal}{ts escape='js'}Vote Recorded.{/ts}{literal}';
           var hasVoted = CRM.$( element ).prop('checked') ? 1:0;
           var trObject = CRM.$( '[id^="survey_activity['+ CRM.$( element ).val() +']"]' ).parents('tr' );
           var methodName = 'addClass';
           if ( !hasVoted ) {
        msg = '{/literal}{ts escape='js'}Vote Cancelled.{/ts}{literal}';
        methodName = 'removeClass';
           }
           eval( 'trObject.' + methodName + "( 'name disabled' )" );
       } else if ( operation == 'reserve' ) {
           if ( CRM.$( element ).prop('checked') ) {
               msg = '{/literal}{ts escape='js'}Reserved.{/ts}{literal}';
           } else {
               msg = '{/literal}{ts escape='js'}Released.{/ts}{literal}';
           }
       }
       CRM.$( msgId ).html( msg );
         }
     }, 'json' );

}

</script>
{/literal}
