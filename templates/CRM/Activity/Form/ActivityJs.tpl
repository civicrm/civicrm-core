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
    var date =  cj("#activity_date_time_display").datepicker('getDate');
    if (date) {
      var
        now = new Date(),
        time = cj("#activity_date_time_time").timeEntry('getTime') || date,
        activityStatusId = cj('#status_id').val(),
        d = date.toString().split(' '),
        activityDate = new Date(d[0] + ' ' + d[1] + ' ' + d[2] + ' ' + d[3] + ' ' + time.toTimeString());
      if (activityStatusId == 2 && now < activityDate) {
        return confirm(message.completed);
      }
      else if (activityStatusId == 1 && now >= activityDate) {
        return confirm(message.scheduled);
      }
    }
  }

</script>
{/literal}
