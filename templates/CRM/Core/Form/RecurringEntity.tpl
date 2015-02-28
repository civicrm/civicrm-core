{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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

<div class="crm-core-form-recurringentity-block crm-accordion-wrapper" id="recurring-entity-block">
  <div class="crm-accordion-header">
    Repeat {if $entityType}{$entityType}{/if}
  </div>
  <div class="crm-accordion-body">
    <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location="top"}
    </div>
    <table class="form-layout-compressed">
      <tr class="crm-core-form-recurringentity-block-repetition_start_date" id="tr-repetition_start_date">
        <td class="label">{$form.repetition_start_date.label}</td>
        <td>{include file="CRM/common/jcalendar.tpl" elementName=repetition_start_date}</td>
      </tr>
      <tr class="crm-core-form-recurringentity-block-repetition_frequency_unit">
        <td class="label">{$form.repetition_frequency_unit.label}&nbsp;<span class="crm-marker" title="This field is required.">*</span>  {help id="id-repeats" entityType=$entityType file="CRM/Core/Form/RecurringEntity.hlp"}</td>
        <td>{$form.repetition_frequency_unit.html}</td>
      </tr>
      <tr class="crm-core-form-recurringentity-block-repetition_frequency_interval">
        <td class="label">{$form.repetition_frequency_interval.label}&nbsp;<span class="crm-marker" title="This field is required.">*</span> {help id="id-repeats-every" entityType=$entityType file="CRM/Core/Form/RecurringEntity.hlp"}</td>
        <td>{$form.repetition_frequency_interval.html} &nbsp;<span id="repeats-every-text">hour(s)</span>
        </td>
      </tr>
      <tr class="crm-core-form-recurringentity-block-start_action_condition">
        <td class="label">
          <label for="repeats_on">{$form.start_action_condition.label} {help id="id-repeats-on" entityType=$entityType file="CRM/Core/Form/RecurringEntity.hlp"}</label>
        </td>
        <td>
          {$form.start_action_condition.html}
        </td>
      </tr>
      <tr class="crm-core-form-recurringentity-block-repeats_by">
        <td class="label">{$form.repeats_by.label}</td>
        <td>{$form.repeats_by.1.html}&nbsp;&nbsp;{$form.limit_to.html} {help id="id-repeats-by-month" entityType=$entityType file="CRM/Core/Form/RecurringEntity.hlp"}
        </td>
      </tr>
      <tr class="crm-core-form-recurringentity-block-repeats_by">
        <td class="label">{help id="id-repeats-by-week" entityType=$entityType file="CRM/Core/Form/RecurringEntity.hlp"}</td>
        <td>{$form.repeats_by.2.html}&nbsp;&nbsp;{$form.entity_status_1.html}&nbsp;&nbsp;{$form.entity_status_2.html}
        </td>
      </tr>
      <tr class="crm-core-form-recurringentity-block-ends">
        <td class="label">{$form.ends.label}&nbsp;<span class="crm-marker" title="This field is required.">*</span> {help id="id-ends-after" entityType=$entityType file="CRM/Core/Form/RecurringEntity.hlp"}</td>
        <td>{$form.ends.1.html}&nbsp;{$form.start_action_offset.html}&nbsp;occurrences&nbsp;</td>
      </tr>
      <tr class="crm-core-form-recurringentity-block-absolute_date">
        <td class="label"> {help id="id-ends-on" entityType=$entityType file="CRM/Core/Form/RecurringEntity.hlp"}</td>
        <td>{$form.ends.2.html}&nbsp;{include file="CRM/common/jcalendar.tpl" elementName=repeat_absolute_date}
        </td>
      </tr>
      <tr class="crm-core-form-recurringentity-block-exclude_date">
        <td class="label">{$form.exclude_date.label} {help id="id-exclude-date" entityType=$entityType file="CRM/Core/Form/RecurringEntity.hlp"}</td>
        <td>&nbsp;{include file="CRM/common/jcalendar.tpl" elementName=exclude_date}
          &nbsp;{$form.add_to_exclude_list.html}&nbsp;{$form.remove_from_exclude_list.html}
          {$form.exclude_date_list.html}
        </td>
      </tr>
      <tr>
        <td class="label bold">{ts}Summary:{/ts}</td>
        <td><span id="rec-summary"></span></td>
      </tr>
    </table>
    <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>
  </div>
</div>
<div id="preview-dialog" class="hiddenElement">
  <div id="generated_dates" class="show-block"></div>
</div>
{literal}
<script type="text/javascript">
  CRM.$(function($) {
    $('#repetition_start_date_display').closest("tr").hide();
    /****** On load "Repeats By" and "Repeats On" blocks should be hidden if dropdown value is not week or month****** (Edit Mode)***/
    switch ($('#repetition_frequency_unit').val()) {
      case 'week':
        $('.crm-core-form-recurringentity-block-start_action_condition').show();
        $('.crm-core-form-recurringentity-block-repeats_by td').hide();
        break;
      case 'month':
        $('.crm-core-form-recurringentity-block-repeats_by td').show();
        $('.crm-core-form-recurringentity-block-start_action_condition').hide();
        break;
      default:
        $('.crm-core-form-recurringentity-block-start_action_condition').hide();
        $('.crm-core-form-recurringentity-block-repeats_by td').hide();
        break;
    }
    $("#repeats-every-text").html($('#repetition_frequency_unit').val()+'(s)');

    /***********On Load Set Ends Value (Edit Mode) **********/
    switch ($('input:radio[name=ends]:checked').val()) {
      case '1':
        $('#start_action_offset').prop('disabled', false);
        $('#repeat_absolute_date_display').prop('disabled', true).val('');
        break;
      case '2':
        $('#repeat_absolute_date_display').prop('disabled', false);
        $('#start_action_offset').prop('disabled', true).val('');
        break;
      default:
        $('#start_action_offset').prop('disabled', true);
        $('#repeat_absolute_date_display').prop('disabled', true);
        break;
    }

    /******On Load set Repeats by section******************/
    switch ($('input:radio[name=repeats_by]:checked').val()) {
      case '1':
        $('#limit_to').prop('disabled', false);
        $('#entity_status_1, #entity_status_2').prop('disabled', true);
        break;
      case '2':
        $('#entity_status_1, #entity_status_2').prop('disabled', false);
        $('#limit_to').prop('disabled', true);
        break;
      default:
          //Just in-case block shows up, disable it
          $('#limit_to, #entity_status_1, #entity_status_2').prop('disabled', true);
        break;
    }

    $('#repetition_frequency_unit').change(function () {
      switch ($(this).val()) {
        case 'hour':
          $('#repeats-every-text').html($(this).val()+'(s)');
          $('.crm-core-form-recurringentity-block-start_action_condition').hide();
          $('.crm-core-form-recurringentity-block-repeats_by td').hide();
          break;
        case 'day':
          $('#repeats-every-text').html($(this).val()+'(s)');
          $('.crm-core-form-recurringentity-block-start_action_condition').hide();
          $('.crm-core-form-recurringentity-block-repeats_by td').hide();
          break;
        case 'week':
          $('#repeats-every-text').html($(this).val()+'(s)');
          //Show "Repeats On" block when week is selected
          $('.crm-core-form-recurringentity-block-start_action_condition').show();
          $('.crm-core-form-recurringentity-block-repeats_by td').hide();
          break;
        case 'month':
          $('#repeats-every-text').html($(this).val()+'(s)');
          $('.crm-core-form-recurringentity-block-start_action_condition').hide();
          //Show "Repeats By" block when month is selected
          $('.crm-core-form-recurringentity-block-repeats_by td').show();
          break;
        case 'year':
          $('#repeats-every-text').html($(this).val()+'(s)');
          $('.crm-core-form-recurringentity-block-start_action_condition').hide();
          $('.crm-core-form-recurringentity-block-repeats_by td').hide();
          break;
      }
    });

    // For "Ends" block
    $('input:radio[name=ends]').click(function() {
      switch ($(this).val()) {
        case '1':
          $('#start_action_offset').prop('disabled', false);
          $('#repeat_absolute_date_display').val('');
          break;
        case '2':
          $('#repeat_absolute_date_display').prop('disabled', false);
          $('#start_action_offset').val('');
          break;
        default:
          $('#repeat_absolute_date_display').prop('disabled', true);
          break;
      }
    });

    //For "Repeats By" block
    $('input:radio[name=repeats_by]').click(function() {
      $('#limit_to').prop('disabled', $(this).val() != 1);
      $('#entity_status_1, #entity_status_2').prop('disabled', $(this).val() != 2);
    });

    //Select all options in selectbox before submitting
    $(this).submit(function() {
      $('#exclude_date_list option').attr('selected',true);

      //Check form for values submitted
      if ($('input[name=ends]:checked').val() == 1) {
        if ($('#start_action_offset').val() == "") {
          if (!$('span#start_action_offset-error').length) {
            $('#start_action_offset').after('<span id ="start_action_offset-error" class="crm-error"> This is a required field.</span>');
            //Check if other message already present, hide it
            $('span#repeat_absolute_date_display-error').toggle();
          }
          return false;
        }
      } else if ($('input[name=ends]:checked').val() == 2) {
        if ($('#repeat_absolute_date_display').val() == "") {
          if (!$('span#repeat_absolute_date_display-error').length) {
            $('#repeat_absolute_date_display').after('<span id="repeat_absolute_date_display-error" class="crm-error"> This is a required field.</span>');
            //Check if other message already present, hide it
            $('span#start_action_offset-error').toggle();
          }
          return false;
        }
      }

    });

    //Detect changes in Repeat configuration field
    var unsavedChanges = false;
    $('div.crm-core-form-recurringentity-block').on('change', function() {
      unsavedChanges = true;
    });

    //If there are changes in repeat configuration, enable save button
    //Dialog for preview repeat Configuration dates
    $('#preview-dialog').dialog({ autoOpen: false });
    function previewDialog() {
      $('#exclude_date_list option').attr('selected', true);
      //Copy exclude dates
      var dateTxt=[];
      $('#exclude_date_list option:selected').each(function() {
        dateTxt.push($(this).text());
      });
      var completeDateText = dateTxt.join(',');
      $('#copyExcludeDates').val(completeDateText);

      $('#generated_dates').html('').html('<div class="crm-loading-element"><span class="loading-text">{/literal}{ts escape='js'}Just a moment, generating dates{/ts}{literal}...</span></div>');
      $('#preview-dialog').dialog('open');
      $('#preview-dialog').dialog({
        title: 'Confirm dates',
        width: '650',
        position: 'center',
        //draggable: false,
        buttons: {
          Ok: function() {
            $(this).dialog( "close" );
            $('form#Repeat, form#Activity').submit();
          },
          Cancel: function() { //cancel
            $(this).dialog( "close" );
          }
        }
      });
      var ajaxurl = CRM.url("civicrm/ajax/recurringentity/generate-preview");
      var entityID = parseInt('{/literal}{$currentEntityId}{literal}');
      var entityTable = '{/literal}{$entityTable}{literal}';
      if (entityTable != "") {
        ajaxurl += "?entity_table="+entityTable;
      }
      if (entityID != "") {
        ajaxurl += "&entity_id="+entityID;
      }
      var formData = $('form').serializeArray();
      $.ajax({
        dataType: "json",
        type: "POST",
        data: formData,
        url:  ajaxurl,
        success: function (result) {
          if (Object.keys(result).length > 0) {
            var errors = [];
            var participantData = [];
            var html = 'Based on your repeat configuration, here is the list of dates. Do you wish to create a recurring set with these dates?<br/><table id="options" class="display"><thead><tr><th></th><th>Start date</th><th id="th-end-date">End date</th></tr><thead>';
            var count = 1;
            for(var i in result) {
              if (i != 'errors') {
                if (i == 'participantData') {
                  participantData = result.participantData;
                  break;
                }
                var start_date = result[i].start_date;
                var end_date = result[i].end_date;

                var end_date_text = '';
                if (end_date !== undefined) {
                  end_date_text = '<td>'+end_date+'</td>';
                }
                html += '<tr><td>'+count+'</td><td>'+start_date+'</td>'+end_date_text+'</tr>';
                count = count + 1;
              } else {
                errors = result.errors;
              }
            }
            html += '</table>';
            var warningHtml = '';
            if (Object.keys(participantData).length > 0) {
              warningHtml += '<div class="messages status no-popup"><div class="icon inform-icon"></div>&nbsp;There are registrations for the repeating events already present in the set, continuing with the process would unlink them and repeating events without registration would be trashed. </div><table id="options" class="display"><thead><tr><th>Event ID</th><th>Event</th><th>Participant Count</th></tr><thead>';
              for (var id in participantData) {
                for(var data in participantData[id]) {
                  warningHtml += '<tr><td>'+id+'</td><td> <a href="{/literal}{crmURL p="civicrm/event/manage/settings" q="reset=1&action=update&id="}{literal}'+id+'{/literal}{literal}">'+data+'</a></td><td><a href="{/literal}{crmURL p='civicrm/event/search' q="reset=1&force=1&status=true&event="}{literal}'+id+'{/literal}{literal}">'+participantData[id][data]+'</a></td></tr>';
                }
              }
              warningHtml += '</table><br/>';
            }
            if (errors.length > 0) {
              html = '';
              for (var j = 0; j < errors.length; j++) {
                html += '<span class="crm-error">*&nbsp;' + errors[j] + '</span><br/>';
              }
            }
            if (warningHtml != "") {
              $('#generated_dates').append(warningHtml).append(html);
            } else {
              $('#generated_dates').html(html);
            }
            if (end_date_text == "") {
              $('#th-end-date').hide();
            }
            if ($("#preview-dialog").height() >= 300) {
              $('#preview-dialog').css('height', '300');
              $('#preview-dialog').css('overflow-y', 'auto');
            }
          } else {
            $('div.ui-dialog-buttonset button span:contains(Ok)').hide();
            $('#generated_dates').append("<span class='crm-error'>Sorry, no dates could be generated for the given criteria!</span>");
          }
        },
        complete: function() {
          $('div.crm-loading-element').hide();
        }
      });
      return false;
    }

    $('#_qf_Repeat_submit-top, #_qf_Repeat_submit-bottom').click( function () {
      return previewDialog();
    });

    $('#_qf_Activity_upload-top, #_qf_Activity_upload-bottom').click( function () {
      //Process this only when repeat is configured. We need to do this test here as there is a common save for activity.
      var isRepeatConfigured = '{/literal}{$scheduleReminderId}{literal}';
      if (isRepeatConfigured) {
        if (unsavedChanges) {
          $('#allowRepeatConfigToSubmit').val('1');
          //Set this variable to decide which dialog box to show
          $.data( document.body, "preview-dialog", true );
          return previewDialog();
        }
        else {
          $.data( document.body, "preview-dialog", false );
          return false;
        }
      }
      else {
        if (unsavedChanges) {
          $('#allowRepeatConfigToSubmit').val('1');
          return previewDialog();
        }
      }
    });

    //Build Summary
    var finalSummary = '';
    var numberText = '';
    var interval = $('#repetition_frequency_interval').val() + ' ';
    if ($('#repetition_frequency_interval').val() == 1) {
      interval = '';
    } else {
      numberText = 's';
    }
    finalSummary = "Every " + interval + $('#repetition_frequency_unit option:selected').val() + numberText;

    //Case Week
    var dayOfWeek = [];
    if ($('#repetition_frequency_unit option:selected').val() == "week") {
      $("input[name^='start_action_condition']:checked").each(function() {
        var tempArray = [];
        var thisID = $(this).attr('id');
        tempArray = thisID.split('_');
        dayOfWeek.push(tempArray[3].substr(0, 1).toUpperCase() + tempArray[3].substr(1).toLowerCase());
      });
      finalSummary += ' on ' + dayOfWeek.join();
    }

    //Case Monthly
    if ($('#repetition_frequency_unit option:selected').val() == "month") {
      if ($('input:radio[name=repeats_by]:checked').val() == 1) {
        finalSummary += ' on day ' + $('#limit_to').val();
      }
      if ($('input:radio[name=repeats_by]:checked').val() == 2) {
        finalSummary += ' on ' + $('#entity_status_1').val().substr(0, 1).toUpperCase() + $('#entity_status_1').val().substr(1).toLowerCase() + ' ' + $('#entity_status_2').val().substr(0, 1).toUpperCase() + $('#entity_status_2').val().substr(1).toLowerCase();
      }
    }

    //Case Ends
    if ($('input:radio[name=ends]:checked').val() == 1) {
      var timeText = ''
      if ($('#start_action_offset').val() != 1) {
        timeText = $('#start_action_offset').val() + ' times';
      } else {
        timeText = ' once';
      }
      finalSummary += ', ' + timeText;
    }
    if ($('input:radio[name=ends]:checked').val() == 2) {
      var monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
      var date = new Date($('#repeat_absolute_date_display').val());
      function addOrdinal(d) {
        if (d>3 && d<21) return 'th';
        switch (d % 10) {
          case 1:  return "st";
          case 2:  return "nd";
          case 3:  return "rd";
          default: return "th";
        }
      }
      var newDate = monthNames[(date.getMonth())] + ' ' + date.getDate()+ addOrdinal() + ' ' +  date.getFullYear();
      finalSummary += ', untill '+ newDate;
    }

    //Build/Attach final Summary
    $('#rec-summary').html(finalSummary);

  });

  //Exclude list function
  function addToExcludeList(val) {
    if (val !== "") {
      var exists = false;
      for(var i = 0, opts = document.getElementById('exclude_date_list').options; i < opts.length; ++i) {
        if (opts[i].text == val) {
          exists = true;
          break;
        }
      }
      if (exists == false) {
        cj('#exclude_date_list').append('<option>'+val+'</option>');
      }
    }
  }

  function removeFromExcludeList(sourceID) {
    var src = document.getElementById(sourceID);
    for(var count= src.options.length-1; count >= 0; count--) {
      if (src.options[count].selected == true) {
        try{
          src.remove(count, null);
        }catch(error) {
          src.remove(count);
        }
      }
    }
  }
</script>
{/literal}
{*Hide Summary*}
{if empty($scheduleReminderId)}
{literal}
  <script type="text/javascript">
    CRM.$(function($) {
      if ($('#rec-summary').length) {
        $('#rec-summary').parent().parent().hide();
      }
    });
  </script>
{/literal}
{/if}
