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
{* Block displays key event info for Registration Confirm and Thankyou pages *}
<table class="form-layout">
  <tr>
    <td colspan="2">
    {if $context EQ 'ThankYou'} {* Provide link back to event info page from Thank-you page *}
        <a href="{crmURL p='civicrm/event/info' q="reset=1&id=`$event.id`"}"title="{ts}View complete event information.{/ts}"><strong>{$event.event_title}</strong></a>
    {else}
        <strong>{$event.event_title}</strong>
    {/if}
    </td>
  </tr>
  <tr><td><label>{ts}When{/ts}</label></td>
      <td width="90%">
        {$event.event_start_date|crmDate}
        {if $event.event_end_date}
            &nbsp; {ts}through{/ts} &nbsp;
            {* Only show end time if end date = start date *}
            {if $event.event_end_date|date_format:"%Y%m%d" == $event.event_start_date|date_format:"%Y%m%d"}
                {$event.event_end_date|crmDate:0:1}
            {else}
                {$event.event_end_date|crmDate}
            {/if}
        {/if}
      </td>
  </tr>

  {if $isShowLocation}
    {if $location.address.1}
      <tr><td><label>{ts}Location{/ts}</label></td>
          <td>
            {$location.address.1.display|nl2br}
            {if ( $event.is_map &&
            $config->mapProvider &&
      ( ( !empty($location.address.1.geo_code_1) && is_numeric($location.address.1.geo_code_1) )  ||
        ( !empty($location.address.1.city) AND !empty($location.address.1.state_province) ) ) ) }
              <br/><a href="{crmURL p='civicrm/contact/map/event' q="reset=1&eid=`$event.id`"}" title="{ts}Map this Address{/ts}" target="_blank">{ts}Map this Location{/ts}</a>
            {/if}
          </td>
      </tr>
    {/if}
  {/if}{*End of isShowLocation condition*}

  {if $location.phone.1.phone || $location.email.1.email}
    <tr><td><label>{ts}Contact{/ts}</label></td>
        <td>
        {* loop on any phones and emails for this event *}
           {foreach from=$location.phone item=phone}
             {if $phone.phone}
                {if $phone.phone_type}{$phone.phone_type_display}{else}{ts}Phone{/ts}{/if}: {$phone.phone} {if $phone.phone_ext}&nbsp;{ts}ext.{/ts} {$phone.phone_ext}{/if}
                <br />
            {/if}
           {/foreach}

           {foreach from=$location.email item=email}
              {if $email.email}
                {ts}Email:{/ts} <a href="mailto:{$email.email}">{$email.email}</a>
              {/if}
            {/foreach}
        </td>
    </tr>
   {/if}
</table>
