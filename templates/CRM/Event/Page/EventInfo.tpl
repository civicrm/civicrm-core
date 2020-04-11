{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for displaying event information *}

{if $registerClosed }
<div class="spacer"></div>
<div class="messages status no-popup">
  <i class="crm-i fa-info-circle"></i>
     &nbsp;{ts}Registration is closed for this event{/ts}
  </div>
{/if}
{if call_user_func(array('CRM_Core_Permission','check'), 'access CiviEvent')}
<div class="crm-actions-ribbon crm-event-manage-tab-actions-ribbon">
  <ul id="actions">
{if call_user_func(array('CRM_Core_Permission','check'), 'edit all events') && !empty($manageEventLinks)}
  <li>
    <div id="crm-event-links-wrapper">
      <span id="crm-event-configure-link" class="crm-hover-button">
        <span title="{ts}Configure this event.{/ts}" class="crm-i fa-wrench"></span>
      </span>
      <div class="ac_results" id="crm-event-links-list" style="margin-left: -25px;">
        <div class="crm-event-links-list-inner">
          <ul>
            {foreach from=$manageEventLinks item='link'}
              <li>
                {* Schedule Reminders requires a different query string. *}
                {if $link.url EQ 'civicrm/event/manage/reminder'}
                  <a href="{crmURL p=$link.url q="reset=1&action=browse&setTab=1&id=`$event.id`" fb=1}">{$link.title}</a>
                {else}
                  <a href="{crmURL p=$link.url q="reset=1&action=update&id=`$event.id`" fb=1}">{$link.title}</a>
                {/if}
              </li>
            {/foreach}
          </ul>
        </div>
      </div>
    </div>
  </li>
{/if}
  <li>
    <div id="crm-participant-wrapper">
      <span id="crm-participant-links" class="crm-hover-button">
        <span title="{ts}Participant listing links.{/ts}" class="crm-i fa-search"></span>
      </span>
      <div class="ac_results" id="crm-participant-list" style="margin-left: -25px;">
        <div class="crm-participant-list-inner">
          <ul>
            {if $findParticipants.statusCounted}
              <li><a class="crm-participant-counted" href="{crmURL p='civicrm/event/search' q="reset=1&force=1&event=`$event.id`&status=true" fb=1}"><b>{ts}List counted participants{/ts}</b> ({$findParticipants.statusCounted|replace:'/':', '})</a></li>
            {/if}

            {if $findParticipants.statusNotCounted}
              <li><a class="crm-participant-not-counted" href="{crmURL p='civicrm/event/search' q="reset=1&force=1&event=`$event.id`&status=false" fb=1}"><b>{ts}List uncounted participants{/ts}</b> ({$findParticipants.statusNotCounted|replace:'/':', '})</a>
              </li>
            {/if}
            {if $participantListingURL}
              <li><a class="crm-participant-listing" href="{$participantListingURL}">{ts}Public Participant Listing{/ts}</a></li>
            {/if}
          </ul>
        </div>
      </div>
    </div>
  </li>
  </ul>
  <div class="clear"></div>
</div>
{/if}
<div class="vevent crm-event-id-{$event.id} crm-block crm-event-info-form-block">
  <div class="event-info">
  {* Display top buttons only if the page is long enough to merit duplicate buttons *}
  {if $event.summary or $event.description}
    <div class="crm-actionlinks-top">
      {crmRegion name="event-page-eventinfo-actionlinks-top"}
        {if $allowRegistration}
          <div class="action-link section register_link-section register_link-top">
            <a href="{$registerURL}" title="{$registerText|escape:'html'}" class="button crm-register-button"><span>{$registerText}</span></a>
          </div>
        {/if}
      {/crmRegion}
    </div>
  {/if}

  {if $event.summary}
      <div class="crm-section event_summary-section">
        {$event.summary}
      </div>
  {/if}
  {if $event.description}
      <div class="crm-section event_description-section summary">
          {$event.description}
      </div>
  {/if}
  <div class="clear"></div>
  <div class="crm-section event_date_time-section">
      <div class="label">{ts}When{/ts}</div>
      <div class="content">
            <abbr class="dtstart" title="{$event.event_start_date|crmDate}">
            {$event.event_start_date|crmDate}</abbr>
            {if $event.event_end_date}
                &nbsp; {ts}through{/ts} &nbsp;
                {* Only show end time if end date = start date *}
                {if $event.event_end_date|date_format:"%Y%m%d" == $event.event_start_date|date_format:"%Y%m%d"}
                    <abbr class="dtend" title="{$event.event_end_date|crmDate:0:1}">
                    {$event.event_end_date|crmDate:0:1}
                    </abbr>
                {else}
                    <abbr class="dtend" title="{$event.event_end_date|crmDate}">
                    {$event.event_end_date|crmDate}
                    </abbr>
                {/if}
            {/if}
        </div>
    <div class="clear"></div>
  </div>

  {if $isShowLocation}

        {if $location.address.1}
            <div class="crm-section event_address-section">
                <div class="label">{ts}Location{/ts}</div>
                <div class="content">{$location.address.1.display|nl2br}</div>
                <div class="clear"></div>
            </div>
        {/if}

      {if ( $event.is_map && $config->mapProvider &&
          ( is_numeric($location.address.1.geo_code_1)  ||
          ( $location.address.1.city AND $location.address.1.state_province ) ) ) }
          <div class="crm-section event_map-section">
              <div class="content">
                    {assign var=showDirectly value="1"}
                    {include file="CRM/Contact/Form/Task/Map/`$config->mapProvider`.tpl" fields=$showDirectly}
                    <br /><a href="{$mapURL}" title="{ts}Show large map{/ts}">{ts}Show large map{/ts}</a>
              </div>
              <div class="clear"></div>
          </div>
      {/if}

  {/if}{*End of isShowLocation condition*}


  {if $location.phone.1.phone || $location.email.1.email}
      <div class="crm-section event_contact-section">
          <div class="label">{ts}Contact{/ts}</div>
          <div class="content">
              {* loop on any phones and emails for this event *}
              {foreach from=$location.phone item=phone}
                  {if $phone.phone}
                      {if $phone.phone_type_id}{$phone.phone_type_display}{else}{ts}Phone{/ts}{/if}:
                          <span class="tel">{$phone.phone} {if $phone.phone_ext}&nbsp;{ts}ext.{/ts} {$phone.phone_ext}{/if} </span> <br />
                      {/if}
              {/foreach}

              {foreach from=$location.email item=email}
                  {if $email.email}
                      {ts}Email:{/ts} <span class="email"><a href="mailto:{$email.email}">{$email.email}</a></span>
                  {/if}
              {/foreach}
          </div>
          <div class="clear"></div>
      </div>
  {/if}

  {if $event.is_monetary eq 1 && $feeBlock.value}
      <div class="crm-section event_fees-section">
          <div class="label">{$event.fee_label}</div>
          <div class="content">
              <table class="form-layout-compressed fee_block-table">
                  {foreach from=$feeBlock.value name=fees item=value}
                      {assign var=idx value=$smarty.foreach.fees.iteration}
                      {* Skip price field label for quick_config price sets since it duplicates $event.fee_label *}
                      {if $feeBlock.lClass.$idx}
                          {assign var="lClass" value=$feeBlock.lClass.$idx}
                      {else}
                          {assign var="lClass" value="fee_level-label"}
                      {/if}
                      {if $isQuickConfig && $lClass EQ "price_set_option_group-label"}
                        {* Skip price field label for quick_config price sets since it duplicates $event.fee_label *}
                      {else}
                      <tr>
                          <td class="{$lClass} crm-event-label">{$feeBlock.label.$idx}</td>
                          {if $isPriceSet & $feeBlock.isDisplayAmount.$idx}
            <td class="fee_amount-value right">
                              {if isset($feeBlock.tax_amount.$idx)}
          {$feeBlock.value.$idx}
                              {else}
                {$feeBlock.value.$idx|crmMoney}
                              {/if}
            </td>
                          {/if}
                      </tr>
                      {/if}
                  {/foreach}
              </table>
          </div>
          <div class="clear"></div>
      </div>
  {/if}


    {include file="CRM/Custom/Page/CustomDataView.tpl"}

    <div class="crm-actionlinks-bottom">
      {crmRegion name="event-page-eventinfo-actionlinks-bottom"}
        {if $allowRegistration}
          <div class="action-link section register_link-section register_link-bottom">
            <a href="{$registerURL}" title="{$registerText|escape:'html'}" class="button crm-register-button"><span>{$registerText}</span></a>
          </div>
        {/if}
      {/crmRegion}
    </div>
    {if $event.is_public }
        <br />{include file="CRM/Event/Page/iCalLinks.tpl"}
    {/if}

    {if $event.is_share }
        {capture assign=eventUrl}{crmURL p='civicrm/event/info' q="id=`$event.id`&amp;reset=1" a=1 fe=1 h=1}{/capture}
        {include file="CRM/common/SocialNetwork.tpl" url=$eventUrl title=$event.title pageURL=$eventUrl}
    {/if}
    </div>
</div>
{literal}
<script type="text/javascript">

cj('body').click(function() {
    cj('#crm-event-links-list').hide();
    cj('#crm-participant-list').hide();
});

cj('#crm-event-configure-link').click(function(event) {
    cj('#crm-event-links-list').toggle();
    cj('#crm-participant-list').hide();
    event.stopPropagation();
});

cj('#crm-participant-links').click(function(event) {
    cj('#crm-participant-list').toggle();
    cj('#crm-event-links-list').hide();
    event.stopPropagation();
});

</script>
{/literal}
