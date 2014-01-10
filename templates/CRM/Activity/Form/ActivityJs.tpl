{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
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
{* added onload javascript for source contact*}
{literal}
<script type="text/javascript">
  var assignee_contact = '';

  {/literal}
  {if $assignee_contact}
  var assignee_contact = {$assignee_contact};
  {/if}

  {literal}
  var assignee_contact_id = null;
  //loop to set the value of cc and bcc if form rule.
  var toDataUrl = "{/literal}{crmURL p='civicrm/ajax/checkemail' q='id=1&noemail=1' h=0 }{literal}"; {/literal}
  {foreach from=","|explode:"assignee" key=key item=element}
  {assign var=currentElement value=`$element`_contact_id}
  {if $form.$currentElement.value}
    {literal} var {/literal}{$currentElement}{literal} = cj.ajax({ url: toDataUrl + "&cid={/literal}{$form.$currentElement.value}{literal}", async: false }).responseText;{/literal}
      {/if}
    {/foreach}
    {literal}

  if ( assignee_contact_id ) {
    eval( 'assignee_contact = ' + assignee_contact_id );
  }

  cj(function( ) {
    {/literal}
    {if $source_contact and $admin and $action neq 4}
      {literal} cj( '#source_contact_id' ).val( "{/literal}{$source_contact}{literal}");{/literal}
      {/if}
      {literal}

    var sourceDataUrl = "{/literal}{$dataUrl}{literal}";
    var tokenDataUrl_assignee  = "{/literal}{$tokenUrl}&context={$tokenContext}_assignee{literal}";

    var hintText = "{/literal}{ts escape='js'}Start typing a name or email address.{/ts}{literal}";
    cj( "#assignee_contact_id").tokenInput( tokenDataUrl_assignee, { prePopulate: assignee_contact, theme: 'facebook', hintText: hintText });
    cj( 'ul.token-input-list-facebook, div.token-input-dropdown-facebook' ).css( 'width', '450px' );
    cj('#source_contact_id').autocomplete( sourceDataUrl, { width : 180, selectFirst : false, hintText: hintText, matchContains: true, minChars: 1, max: {/literal}{crmSetting name="search_autocomplete_count" group="Search Preferences"}{literal}
    }).result( function(event, data, formatted) { cj( "#source_contact_qid" ).val( data[1] );
      }).bind( 'click', function( ) { if (!cj("#source_contact_id").val()) { cj( "#source_contact_qid" ).val(''); } });
  });

  /**
   * Function to check activity status in relavent to activity date
   *
   * @param element message JSON object.
   */
  function activityStatus(message) {
    var d = new Date(), time = [], i;
    var currentDateTime = d.getTime()
    var activityTime = cj("input#activity_date_time_time").val().replace(":", "");

    //chunk the time in bunch of 2 (hours,minutes,ampm)
    for (i = 0; i < activityTime.length; i += 2) {
      time.push(activityTime.slice(i, i + 2));
    }
    var activityDate = new Date(cj("input#activity_date_time_hidden").val());

    d.setFullYear(activityDate.getFullYear());
    d.setMonth(activityDate.getMonth());
    d.setDate(activityDate.getDate());
    var hours = time['0'];
    var ampm = time['2'];

    if (ampm == "PM" && hours != 0 && hours != 12) {
      // force arithmetic instead of string concatenation
      hours = hours * 1 + 12;
    }
    else {
      if (ampm == "AM" && hours == 12) {
        hours = 0;
      }
    }
    d.setHours(hours);
    d.setMinutes(time['1']);

    var activity_date_time = d.getTime();

    var activityStatusId = cj('#status_id').val();

    if (activityStatusId == 2 && currentDateTime < activity_date_time) {
      if (!confirm(message.completed)) {
        return false;
      }
    }
    else {
      if (activity_date_time && activityStatusId == 1 && currentDateTime >= activity_date_time) {
        if (!confirm(message.scheduled)) {
          return false;
        }
      }
    }
  }

</script>
{/literal}
