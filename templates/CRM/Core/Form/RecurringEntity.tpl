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
            <td class="label">{$form.repetition_frequency_unit.label}&nbsp;<span class="crm-marker" title="This field is required.">*</span></td>
            <td>{$form.repetition_frequency_unit.html} {help id="id-repeats" entityType=$entityType file="CRM/Core/Form/RecurringEntity.hlp"}</td>
          </tr>
          <tr class="crm-core-form-recurringentity-block-repetition_frequency_interval">
            <td class="label">{$form.repetition_frequency_interval.label}&nbsp;<span class="crm-marker" title="This field is required.">*</span></td>
            <td>{$form.repetition_frequency_interval.html} &nbsp;<span id="repeats-every-text">hour(s)</span> {help id="id-repeats-every" entityType=$entityType file="CRM/Core/Form/RecurringEntity.hlp"}</td>
            </td>
          </tr>
          <tr class="crm-core-form-recurringentity-block-start_action_condition">
            <td class="label">
                <label for="repeats_on">{$form.start_action_condition.label}: </label>
            </td>
            <td>
                {$form.start_action_condition.html} {help id="id-repeats-on" entityType=$entityType file="CRM/Core/Form/RecurringEntity.hlp"}</td>
            </td>
          </tr>
          <tr class="crm-core-form-recurringentity-block-repeats_by">
            <td class="label">{$form.repeats_by.label}</td>
            <td>{$form.repeats_by.1.html}&nbsp;&nbsp;{$form.limit_to.html} {help id="id-repeats-by-month" entityType=$entityType file="CRM/Core/Form/RecurringEntity.hlp"}
            </td>
          </tr>
          <tr class="crm-core-form-recurringentity-block-repeats_by">
            <td class="label"></td>
            <td>{$form.repeats_by.2.html}&nbsp;&nbsp;{$form.entity_status_1.html}&nbsp;&nbsp;{$form.entity_status_2.html} {help id="id-repeats-by-week" entityType=$entityType file="CRM/Core/Form/RecurringEntity.hlp"}
            </td>
          </tr>
          <tr class="crm-core-form-recurringentity-block-ends">
            <td class="label">{$form.ends.label}&nbsp;<span class="crm-marker" title="This field is required.">*</span></td>
            <td>{$form.ends.1.html}&nbsp;{$form.start_action_offset.html}&nbsp;occurrences&nbsp;{help id="id-ends-after" entityType=$entityType file="CRM/Core/Form/RecurringEntity.hlp"}</td>
          </tr>
          <tr class="crm-core-form-recurringentity-block-absolute_date">
              <td class="label"></td>
              <td>{$form.ends.2.html}&nbsp;{include file="CRM/common/jcalendar.tpl" elementName=repeat_absolute_date} {help id="id-ends-on" entityType=$entityType file="CRM/Core/Form/RecurringEntity.hlp"}
              </td>
          </tr>
          <tr class="crm-core-form-recurringentity-block-exclude_date">
              <td class="label">{$form.exclude_date.label}</td>
              <td>&nbsp;{include file="CRM/common/jcalendar.tpl" elementName=exclude_date}
                  &nbsp;{$form.add_to_exclude_list.html}&nbsp;{$form.remove_from_exclude_list.html}
                  {$form.exclude_date_list.html} {help id="id-exclude-date" entityType=$entityType file="CRM/Core/Form/RecurringEntity.hlp"}
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
  cj(document).ready(function() {
    cj('#repetition_start_date_display').closest("tr").hide();
    /****** On load "Repeats By" and "Repeats On" blocks should be hidden if dropdown value is not week or month****** (Edit Mode)***/
    if (cj('#repetition_frequency_unit').val() == "week") {
      cj('.crm-core-form-recurringentity-block-start_action_condition').show();
      cj('.crm-core-form-recurringentity-block-repeats_by td').hide();
    } else if (cj('#repetition_frequency_unit').val() == "month") {
      cj('.crm-core-form-recurringentity-block-repeats_by td').show();
      cj('.crm-core-form-recurringentity-block-start_action_condition').hide();
    } else {
      cj('.crm-core-form-recurringentity-block-start_action_condition').hide();
      cj('.crm-core-form-recurringentity-block-repeats_by td').hide();
    }
    cj("#repeats-every-text").html(cj('#repetition_frequency_unit').val()+'(s)');

    /***********On Load Set Ends Value (Edit Mode) **********/
    if (cj('input:radio[name=ends]:checked').val() == 1) {
      cj('#start_action_offset').removeAttr('disabled').attr('enabled','enabled');
      cj('#repeat_absolute_date_display').removeAttr("enabled").attr('disabled','disabled');
      cj('#repeat_absolute_date_display').val('');
    } else if (cj('input:radio[name=ends]:checked').val() == 2) {
      cj('#repeat_absolute_date_display').removeAttr("disabled").attr('enabled','enabled');
      cj('#start_action_offset').removeAttr('enabled').attr('disabled','disabled');
      cj('#start_action_offset').val('');
    } else {
      cj('#start_action_offset').removeAttr('enabled').attr('disabled','disabled');
      cj('#repeat_absolute_date_display').removeAttr('enabled').attr('disabled','disabled');
    }

    /******On Load set Repeats by section******************/
    if (cj('input:radio[name=repeats_by]:checked').val() == 1) {
      cj('#limit_to').removeAttr('disabled').attr('enabled','enabled');
      cj('#entity_status_1, #entity_status_2').removeAttr("enabled").attr('disabled','disabled');
    } else if (cj('input:radio[name=repeats_by]:checked').val() == 2) {
      cj('#entity_status_1, #entity_status_2').removeAttr("disabled").attr('enabled','enabled');
      cj('#limit_to').removeAttr('enabled').attr('disabled','disabled');
    } else {
      //Just in-case block shows up, disable it
      cj('#limit_to, #entity_status_1, #entity_status_2').removeAttr('enabled').attr('disabled','disabled');
    }

    cj('#repetition_frequency_unit').change(function () {
      if (cj(this).val()==='hour') {
        cj('#repeats-every-text').html(cj(this).val()+'(s)');
        cj('.crm-core-form-recurringentity-block-start_action_condition').hide();
        cj('.crm-core-form-recurringentity-block-repeats_by td').hide();
      } else if (cj(this).val()==='day') {
        cj('#repeats-every-text').html(cj(this).val()+'(s)');
        cj('.crm-core-form-recurringentity-block-start_action_condition').hide();
        cj('.crm-core-form-recurringentity-block-repeats_by td').hide();
      } else if (cj(this).val()==='week') {
        cj('#repeats-every-text').html(cj(this).val()+'(s)');
        //Show "Repeats On" block when week is selected
        cj('.crm-core-form-recurringentity-block-start_action_condition').show();
        cj('.crm-core-form-recurringentity-block-repeats_by td').hide();
      } else if (cj(this).val()==='month') {
        cj('#repeats-every-text').html(cj(this).val()+'(s)');
        cj('.crm-core-form-recurringentity-block-start_action_condition').hide();
        //Show "Repeats By" block when month is selected
        cj('.crm-core-form-recurringentity-block-repeats_by td').show();
      } else if (cj(this).val()==='year') {
        cj('#repeats-every-text').html(cj(this).val()+'(s)');
        cj('.crm-core-form-recurringentity-block-start_action_condition').hide();
        cj('.crm-core-form-recurringentity-block-repeats_by td').hide();
      }
    });

    // For "Ends" block
    cj('input:radio[name=ends]').click(function() {
      if (cj(this).val() == 1) {
        cj('#start_action_offset').removeAttr('disabled').attr('enabled','enabled');
        cj('#repeat_absolute_date_display').val('');
      } else if (cj(this).val() == 2) {
        cj('#repeat_absolute_date_display').removeAttr('disabled').attr('enabled','enabled');
        cj('#start_action_offset').val('');
      } else {
        cj('#repeat_absolute_date_display').removeAttr('enabled').attr('disabled','disabled');
      }
    });

    //For "Repeats By" block
    cj('input:radio[name=repeats_by]').click(function() {
      if (cj(this).val() == 1) {
        cj('#limit_to').removeAttr('disabled').attr('enabled','enabled');
      } else {
        cj('#limit_to').removeAttr('enabled').attr('disabled','disabled');
      }
      if (cj(this).val() == 2) {
        cj('#entity_status_1').removeAttr('disabled').attr('enabled','enabled');
        cj('#entity_status_2').removeAttr('disabled').attr('enabled','enabled');
      } else {
        cj('#entity_status_1').removeAttr('enabled').attr('disabled','disabled');
        cj('#entity_status_2').removeAttr('enabled').attr('disabled','disabled');
      }
    });

    //Select all options in selectbox before submitting
    cj(this).submit(function() {
      cj('#exclude_date_list option').attr('selected',true);

      //Check form for values submitted
      if (cj('input[name=ends]:checked').val() == 1) {
        if (cj('#start_action_offset').val() == "") {
          if (!cj('span#start_action_offset-error').length) {
              cj('#start_action_offset').after('<span id ="start_action_offset-error" class="crm-error"> This is a required field.</span>');
              //Check if other message already present, hide it
              cj('span#repeat_absolute_date_display-error').toggle();
          }
          return false;
        }
      } else if (cj('input[name=ends]:checked').val() == 2) {
        if (cj('#repeat_absolute_date_display').val() == "") {
          if (!cj('span#repeat_absolute_date_display-error').length) {
            cj('#repeat_absolute_date_display').after('<span id="repeat_absolute_date_display-error" class="crm-error"> This is a required field.</span>');
            //Check if other message already present, hide it
            cj('span#start_action_offset-error').toggle();
          }
          return false;
        }
      }

    });

    //Detect changes in Repeat configuration field
    var unsavedChanges = false;
    cj('div.crm-core-form-recurringentity-block').on('change', function() {
      unsavedChanges = true;
    });

    //If there are changes in repeat configuration, enable save button
    //Dialog for preview repeat Configuration dates
    cj('#preview-dialog').dialog({ autoOpen: false });
    function previewDialog() {
        cj('#exclude_date_list option').attr('selected',true);
        //Copy exclude dates
        var dateTxt=[];
        cj('#exclude_date_list option:selected').each(function() {
            dateTxt.push(cj(this).text());
        });
        var completeDateText = dateTxt.join(',');
        cj('#copyExcludeDates').val(completeDateText);

        cj('#generated_dates').html('').html('<div class="crm-loading-element"><span class="loading-text">{/literal}{ts escape='js'}Just a moment, generating dates{/ts}{literal}...</span></div>');
        cj('#preview-dialog').dialog('open');
        cj('#preview-dialog').dialog({
          title: 'Confirm dates',
          width: '650',
          position: 'center',
          //draggable: false,
          buttons: {
            Ok: function() {
                cj(this).dialog( "close" );
                cj('form#Repeat, form#Activity').submit();
            },
            Cancel: function() { //cancel
                cj(this).dialog( "close" );
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
        var formData = cj('form').serializeArray();
        cj.ajax({
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
                cj('#generated_dates').append(warningHtml).append(html);
              } else {
                cj('#generated_dates').html(html);
              }
              if (end_date_text == "") {
                cj('#th-end-date').hide();
              }
              if (cj("#preview-dialog").height() >= 300) {
                cj('#preview-dialog').css('height', '300');
                cj('#preview-dialog').css('overflow-y', 'auto');
              }
            } else {
              cj('div.ui-dialog-buttonset button span:contains(Ok)').hide();
              cj('#generated_dates').append("<span class='crm-error'>Sorry, no dates could be generated for the given criteria!</span>");
            }
          },
          complete: function() {
            cj('div.crm-loading-element').hide();
          }
        });
        return false;
    }

    cj('#_qf_Repeat_submit-top, #_qf_Repeat_submit-bottom').click( function () {
      return previewDialog();
    });

    cj('#_qf_Activity_upload-top, #_qf_Activity_upload-bottom').click( function () {
      //Process this only when repeat is configured. We need to do this test here as there is a common save for activity.
      var isRepeatConfigured = '{/literal}{$scheduleReminderId}{literal}';
      if (isRepeatConfigured) {
         if (unsavedChanges) {
          cj('#allowRepeatConfigToSubmit').val('1');
          //Set this variable to decide which dialog box to show
          cj.data( document.body, "preview-dialog", true );
          return previewDialog();
        }
        else {
          cj.data( document.body, "preview-dialog", false );
          return false;
        }
      }
      else {
        if (unsavedChanges) {
          cj('#allowRepeatConfigToSubmit').val('1');
          return previewDialog();
        }
      }
    });

    //Build Summary
    var finalSummary = '';
    var numberText = '';
    var interval = cj('#repetition_frequency_interval').val() + ' ';
    if (cj('#repetition_frequency_interval').val() == 1) {
      interval = '';
    } else {
      numberText = 's';
    }
    finalSummary = "Every " + interval + cj('#repetition_frequency_unit option:selected').val() + numberText;

    //Case Week
    var dayOfWeek = new Array();
    if (cj('#repetition_frequency_unit option:selected').val() == "week") {
      cj("input[name^='start_action_condition']:checked").each(function() {
        var tempArray = new Array();
        var thisID = cj(this).attr('id');
        tempArray = thisID.split('_');
        dayOfWeek.push(tempArray[3].substr(0, 1).toUpperCase() + tempArray[3].substr(1).toLowerCase());
      });
      finalSummary += ' on ' + dayOfWeek.join();
    }

    //Case Monthly
    if (cj('#repetition_frequency_unit option:selected').val() == "month") {
      if (cj('input:radio[name=repeats_by]:checked').val() == 1) {
        finalSummary += ' on day ' + cj('#limit_to').val();
      }
      if (cj('input:radio[name=repeats_by]:checked').val() == 2) {
        finalSummary += ' on ' + cj('#entity_status_1').val().substr(0, 1).toUpperCase() + cj('#entity_status_1').val().substr(1).toLowerCase() + ' ' + cj('#entity_status_2').val().substr(0, 1).toUpperCase() + cj('#entity_status_2').val().substr(1).toLowerCase();
      }
    }

    //Case Ends
    if (cj('input:radio[name=ends]:checked').val() == 1) {
      var timeText = ''
      if (cj('#start_action_offset').val() != 1) {
        timeText = cj('#start_action_offset').val() + ' times';
      } else {
        timeText = ' once';
      }
      finalSummary += ', ' + timeText;
    }
    if (cj('input:radio[name=ends]:checked').val() == 2) {
      var monthNames = new Array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
      var date = new Date(cj('#repeat_absolute_date_display').val());
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
    cj('#rec-summary').html(finalSummary);

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
        cj(document).ready(function() {
            if (cj('#rec-summary').length) {
                cj('#rec-summary').parent().parent().hide();
            }
        });
        </script>
    {/literal}
{/if}
