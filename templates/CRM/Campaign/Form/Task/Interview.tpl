{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
{if $votingTab and $errorMessages}
  <div class='messages status'>
    <div class="icon inform-icon"></div>
    <ul>
      {foreach from=$errorMessages item=errorMsg}
        <li>{ts}{$errorMsg}{/ts}</li>
      {/foreach}
    </ul>
  </div>
  </div>
{elseif $voterDetails}
<div class="form-item">
  <fieldset>
    {if $surveyValues.instructions}
      <div id='survey_instructions' class='help'>{ts 1=$surveyValues.instructions}%1{/ts}</div>
    {/if}

    <div id='responseErrors' class = "hiddenElement messages crm-error"></div>

    <div class='help'>
      {if $votingTab}
        {ts}Click <strong>record response</strong> button to update values for each respondent as needed.{/ts}
      {else}
        {ts}Click <strong>record response</strong> button to update values for each respondent as needed. <br />Click <strong>Release Respondents >></strong> button below to release any respondents for whom you haven't recorded a response. <br />Click <strong>Reserve More Respondents >></strong> button if you need to get more respondents to interview.{/ts}
      {/if}
    </div>
    {if $instanceId}
      {capture assign=instanceURL}{crmURL p="civicrm/report/instance/$instanceId" q="reset=1"}{/capture}
      <div class="float-right"><a href='{$instanceURL}' class="button">{ts}Survey Report{/ts}</a></div>
    {/if}
    <div id="order-by-elements" class="civireport-criteria">
      <table id="optionField" class="form-layout-compressed">
        <tr>
          <th></th>
          <th>{ts}Column{/ts}</th>
          <th>{ts}Order{/ts}</th>
          <th></th>
        </tr>

        {section name=rowLoop start=1 loop=5}
          {assign var=index value=$smarty.section.rowLoop.index}
          <tr id="optionField_{$index}" class="form-item {cycle values="odd-row,even-row"}">
            <td>
              {if $index GT 1}
                <a onclick="hideRow({$index}); return false;" name="orderBy_{$index}" href="#" class="form-link"><img src="{$config->resourceBase}i/TreeMinus.gif" class="action-icon" alt="{ts}hide field or section{/ts}"/></a>
              {/if}
            </td>
            <td> {$form.order_bys.$index.column.html}</td>
            <td> {$form.order_bys.$index.order.html}</td>
            <td>
            {if $index eq 1}
              {$form.buttons._qf_Interview_submit_orderBy.html}
            {/if}
            </td>
          </tr>
        {/section}
      </table>
      <div id="optionFieldLink" class="add-remove-link">
        <a onclick="showHideRow(); return false;" name="optionFieldLink" href="#" class="form-link"><img src="{$config->resourceBase}i/TreePlus.gif" class="action-icon" alt="{ts}show field or section{/ts}"/>{ts}another column{/ts}</a>
      </div>

      <script type="text/javascript">
        var showRows   = new Array('optionField_1');
        var hideBlocks = new Array('optionField_2','optionField_3','optionField_4');
        var rowcounter = 0;
          {literal}
          if (navigator.appName == "Microsoft Internet Explorer") {
            for ( var count = 0; count < hideBlocks.length; count++ ) {
              var r = document.getElementById(hideBlocks[count]);
              r.style.display = 'none';
            }
          }

          // hide and display the appropriate blocks as directed by the php code
          on_load_init_blocks( showRows, hideBlocks, '' );

          if (CRM.$("#order_bys_2_column").val()){
            CRM.$("#optionField_2").show();
          }
          if (CRM.$("#order_bys_3_column").val()){
            CRM.$("#optionField_3").show();
          }
          if (CRM.$("#order_bys_4_column").val()){
            CRM.$("#optionField_4").show();
          }

          function hideRow(i) {
            showHideRow(i);
            // clear values on hidden field, so they're not saved
            CRM.$('select#order_by_column_'+ i).val('');
            CRM.$('select#order_by_order_'+ i).val('ASC');
          }
          {/literal}
      </script>
    </div>

    <table id="voterRecords-{$instanceId}" class="display crm-copy-fields">
      <thead>
      <tr class="columnheader">
        {foreach from=$readOnlyFields item=fTitle key=fName}
          <th {if $fName neq 'contact_type'} class="contact_details"{/if}>{$fTitle}</th>
        {/foreach}

      {* display headers for profile survey fields *}
        {if $surveyFields}
          {foreach from=$surveyFields item=field key=fieldName}
            {if $field.skipDisplay}
              {continue}
            {/if}
            <th><img  src="{$config->resourceBase}i/copy.png" alt="{ts 1=$field.title}Click to copy %1 from row one to all rows.{/ts}" fname="{$field.name}" class="action-icon" title="{ts}Click here to copy the value in row one to ALL rows.{/ts}" />{$field.title}</th>
          {/foreach}
        {/if}

        <th><img  src="{$config->resourceBase}i/copy.png" alt="{ts 1=note}Click to copy %1 from row one to all rows.{/ts}" fname="note" class="action-icon" title="{ts}Click here to copy the value in row one to ALL rows.{/ts}" />{ts}Note{/ts}</th>
        <th><img  src="{$config->resourceBase}i/copy.png" alt="{ts 1=result}Click to copy %1 from row one to all rows.{/ts}" fname="result" class="action-icon" title="{ts}Click here to copy the value in row one to ALL rows.{/ts}" />{ts}Result{/ts}</th>
        <th><a id="interview_voter_button" class='button' style="float:left;" href="#" title={ts}Vote{/ts} onclick="registerInterviewforall( ); return false;">{ts}Record Responses for All{/ts}</a></th>
      </tr>
      </thead>

      <tbody>
        {foreach from=$componentIds item=voterId}
        <tr id="row_{$voterId}" class="{cycle values="odd-row,even-row"}" entity_id="{$voterId}">
          {foreach from=$readOnlyFields item=fTitle key=fName}
            <td {if $fName neq 'contact_type'} class="name"{/if}>{$voterDetails.$voterId.$fName}</td>
          {/foreach}

        {* here build the survey profile fields *}
          {if $surveyFields}
            {foreach from=$surveyFields item=field key=fieldName}
              {if $field.skipDisplay}
                {continue}
              {/if}
              <td class="compressed {$field.data_type} {$fieldName}">
                {if ( ( $fieldName eq 'thankyou_date' ) or ( $fieldName eq 'cancel_date' ) or ( $fieldName eq 'receipt_date' ) or (  $fieldName eq 'activity_date_time') ) and $field.is_view neq 1 }
                {include file="CRM/common/jcalendar.tpl" elementName=$fieldName elementIndex=$voterId batchUpdate=1}
                {elseif $fieldName|substr:0:5 eq 'phone'}
                  {assign var="phone_ext_field" value=$fieldName|replace:'phone':'phone_ext'}
                  {$form.field.$voterId.$fieldName.html}
                  {if $form.field.$voterId.$phone_ext_field.html}
                    &nbsp;{$form.field.$voterId.$phone_ext_field.html}
                  {/if}
                {else}
                  {$form.field.$voterId.$fieldName.html}
                {/if}
              </td>
            {/foreach}
          {/if}

          <td class='note'>{$form.field.$voterId.note.html}</td>
          <td class='result'>{$form.field.$voterId.result.html}</td>

          <td>
            <a id="interview_voter_button_{$voterId}" class='button' style="float:left;" href="#" title={ts}Vote{/ts} onclick="registerInterview( {$voterId} ); return false;">
              {ts}record response{/ts}
            </a>
            {if $allowAjaxReleaseButton}
              <a id="release_voter_button_{$voterId}" class='button'  href="#" title={ts}Release{/ts} onclick="releaseOrReserveVoter( {$voterId} ); return false;">
                {ts}release{/ts}
              </a>
            {/if}
            <span id='restmsg_vote_{$voterId}' class="ok" style="display:none;float:right;">
              {ts}Response Saved.{/ts}
            </span>

            <span id='restmsg_release_or_reserve_{$voterId}' class="ok" style="display:none;float:right;">
              {ts}Released.{/ts}
            </span>
          </td>
        </tr>
        {/foreach}
      </tbody>
    </table>

    {if !$votingTab}
      <div class="spacer"></div>
      <div class="crm-submit-buttons">{$form.buttons._qf_Interview_cancel_interview.html}&nbsp;{$form.buttons._qf_Interview_next_interviewToRelease.html}&nbsp;{$form.buttons._qf_Interview_done_interviewToReserve.html}</div>
    {/if}

  </fieldset>
</div>

{literal}
<script type="text/javascript">
var updateVote = "{/literal}{ts escape='js'}Update Response{/ts}{literal}";
var updateVoteforall = "{/literal}{ts escape='js'}Update Responses for All{/ts}{literal}";
CRM.$(function($) {
  var count = 0; var columns='';

  CRM.$('#voterRecords-{/literal}{$instanceId}{literal} th').each( function( ) {
    if ( CRM.$(this).attr('class') == 'contact_details' ) {
      columns += '{"sClass": "contact_details"},';
    }
    else {
      columns += '{ "bSortable": false },';
    }
    count++;
  });

  columns    = columns.substring(0, columns.length - 1 );
  eval('columns =[' + columns + ']');

  //load jQuery data table.
  CRM.$('table#voterRecords-{/literal}{$instanceId}{literal}').dataTable( {
    "sPaginationType": "full_numbers",
    "bJQueryUI"  : true,
    "aoColumns"  : columns
  });

});

function registerInterview( voterId ) {
  //reset all errors.
  CRM.$( '#responseErrors' ).hide( ).html( '' );

  //collect all submitted data.
  var data = new Object;

  //get the values for common elements.
  var fieldName = 'field_' + voterId + '_custom_';
  var specialFieldType = new Array( 'radio', 'checkbox', 'select' );
  CRM.$( '[id^="'+ fieldName +'"]' ).each( function( ) {
    fieldType = CRM.$( this ).attr( 'type' );
    if ( specialFieldType.indexOf( fieldType ) == -1 ) {
      data[CRM.$(this).attr( 'id' )] = CRM.$( this ).val( );
    }
  });

  //get the values for select.
  CRM.$('select[id^="'+ fieldName +'"]').each( function( ) {
    value = CRM.$(this).val( );
    if (CRM.$(this).attr( 'multiple')) {
      values = value;
      value = '';
      if ( values ) {
        submittedValues = values.toString().split(",");
        value = new Object;
        for (val in submittedValues) {
          currentVal = submittedValues[val];
          value[currentVal] = currentVal;
        }
      }
    }
    data[CRM.$(this).attr('id')] = value;
  });

  var checkBoxField = 'field['+ voterId +'][custom_';
  CRM.$('input:checkbox[name^="'+ checkBoxField +'"]').each( function( ) {
    value = '';
    if (CRM.$(this).is(':checked') == true) value = 1;
    data[CRM.$(this).attr( 'name' )] = value;
  });

  var allRadios   = new Object;
  var radioField = 'field['+ voterId +'][custom_';
  CRM.$('input:radio[name^="'+ radioField +'"]').each( function( ) {
    radioName = CRM.$(this).attr( 'name' );
    if (CRM.$(this).is(':checked') == true) {
      data[radioName] = CRM.$(this).val();
    }
    allRadios[radioName] = radioName;
  });
  for (radioName in allRadios) {
    if (!data.hasOwnProperty(radioName)) data[radioName] = '';
  }

  //carry contact related profile field data.
  var fieldName = 'field_' + voterId;
  var checkBoxFieldName = 'field[' + voterId + ']';
  CRM.$('[id^="'+ fieldName +'"], [id^="'+ checkBoxFieldName +'"]').each(function( ) {
    fldId = CRM.$(this).attr('id');
    if (fldId.indexOf('_custom_') == -1 &&
      fldId.indexOf('_result') == -1  &&
      fldId.indexOf('_note') == -1 ) {
      data[fldId] = CRM.$(this).val( );
    }
  });

var surveyActivityIds = {/literal}{$surveyActivityIds}{literal};
  activityId =  eval("surveyActivityIds.activity_id_" + voterId);
  if (!activityId) return;

  data['voter_id']         = voterId;
  data['interviewer_id']   = {/literal}{$interviewerId}{literal};
  data['activity_type_id'] = {/literal}{$surveyTypeId}{literal};
  data['activity_id']      = activityId;
  data['result']           = CRM.$( '#field_' + voterId + '_result' ).val( );
  data['note']             = CRM.$( '#field_' + voterId + '_note' ).val( );
  data['surveyTitle']      = {/literal}'{$surveyValues.title|escape:javascript}'{literal};
  data['survey_id']        = {/literal}'{$surveyValues.id}'{literal};

  var dataUrl = {/literal}"{crmURL p='civicrm/campaign/registerInterview' h=0}"{literal}

  //post data to create interview.
  CRM.$.post(dataUrl, data, function(interview) {
    if ( interview.status == 'success' ) {
      CRM.$("#row_"+voterId+' td.name').attr('class', 'name strikethrough' );
      CRM.$('#restmsg_vote_' + voterId).fadeIn("slow").fadeOut("slow");
      CRM.$('#interview_voter_button_' + voterId).html(updateVote);
      CRM.$('#release_voter_button_' + voterId).hide( );
    }
    else if (interview.status == 'fail' && interview.errors) {
      var errorList = '';
      for (error in interview.errors) {
        if (interview.errors[error]) errorList =  errorList + '<li>' + interview.errors[error] + '</li>';
      }
      if ( errorList ) {
        var allErrors = '<i class="crm-i fa-exclamation-triangle crm-i-red"></i> {/literal}{ts}Please correct the following errors in the survey fields below:{/ts}{literal}<ul>' + errorList + '</ul>';
        CRM.$('#responseErrors').show( ).html(allErrors);
      }
    }
  }, 'json');
}

function releaseOrReserveVoter(voterId) {
  if (!voterId) return;

  var surveyActivityIds = {/literal}{$surveyActivityIds}{literal};
  activityId =  eval("surveyActivityIds.activity_id_" + voterId);
  if ( !activityId ) return;

  var operation  = 'release';
  var isReleaseOrReserve = CRM.$('#field_' + voterId + '_is_release_or_reserve').val( );
  if (isReleaseOrReserve == 1) {
    operation = 'reserve';
    isReleaseOrReserve = 0;
  }
  else {
    isReleaseOrReserve = 1;
  }

  var data = new Object;
  data['operation']   = operation;
  data['isDelete']    = (operation == 'release') ? 1 : 0;
  data['activity_id'] = activityId;

  var actUrl = {/literal}
  "{crmURL p='civicrm/ajax/rest' h=0 q='className=CRM_Campaign_Page_AJAX&fnName=processVoterData'}"
  {literal};

    //post data to release / reserve voter.
    CRM.$.post( actUrl,
    data,
    function( response ) {
      if (response.status == 'success') {
        if ( operation == 'release' ) {
          CRM.$( '#interview_voter_button_' + voterId ).hide( );
          CRM.$( '#restmsg_release_or_reserve' + voterId ).fadeIn( 'slow' ).fadeOut( 'slow' );
          CRM.$( '#row_' + voterId + ' td.name' ).addClass( 'disabled' );
          CRM.$( '#release_voter_button_'+ voterId ).html( "{/literal}{ts escape='js'}reserve{/ts}{literal}"  );
          CRM.$( '#release_voter_button_' + voterId ).attr('title',"{/literal}{ts escape='js'}Reserve{/ts}{literal}");
        }
        else {
          CRM.$( '#interview_voter_button_' + voterId ).show( );
          CRM.$( '#restmsg_release_or_reserve' + voterId ).fadeIn( 'slow' ).fadeOut( 'slow' );
          CRM.$( '#row_' + voterId + ' td.name' ).removeClass( 'disabled' );
          CRM.$( '#release_voter_button_'+ voterId ).html( "{/literal}{ts escape='js'}release{/ts}{literal}"  );
          CRM.$( '#release_voter_button_' + voterId ).attr('title',"{/literal}{ts escape='js'}Release{/ts}{literal}");
        }
        CRM.$( '#field_' + voterId + '_is_release_or_reserve' ).val( isReleaseOrReserve );
      }
    },
  'json');
}

function registerInterviewforall( ) {
  var Ids = {/literal}{$componentIdsJson}{literal};
  for (var contactid in Ids) {
    if (CRM.$('#field_'+ Ids[contactid] +'_result').val()) {
      registerInterview(Ids[contactid]);
      CRM.$('#interview_voter_button').html(updateVoteforall);
    }
  }
}

</script>
{/literal}
{*include batch copy js js file*}
{include file="CRM/common/batchCopy.tpl"}
{/if}
