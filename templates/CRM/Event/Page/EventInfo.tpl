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

{if $registerClosed}
<div class="spacer"></div>
<div class="messages status no-popup">
  <i class="crm-i fa-info-circle" aria-hidden="true"></i>
     &nbsp;{ts}Registration is closed for this event{/ts}
  </div>
{/if}
{crmPermission has='access CiviEvent'}
<div class="crm-actions-ribbon crm-event-manage-tab-actions-ribbon">
  <ul id="actions">
{crmPermission has='edit all events'}
{if !empty($manageEventLinks)}
  <li>
    <div id="crm-event-links-wrapper">
      <span id="crm-event-configure-link" class="crm-hover-button">
        <span title="{ts}Configure this event.{/ts}" class="crm-i fa-wrench" aria-hidden="true"></span>
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
{/crmPermission}
  <li>
    <div id="crm-participant-wrapper">
      <span id="crm-participant-links" class="crm-hover-button">
        <span title="{ts}Participant listing links.{/ts}" class="crm-i fa-search" aria-hidden="true"></span>
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
{/crmPermission}
<div class="vevent crm-event-id-{$event.id} crm-block crm-event-info-form-block">
  <div class="event-info">
  {* Display top buttons only if the page is long enough to merit duplicate buttons *}
  {if (array_key_exists('summary', $event) && $event.summary) or array_key_exists('description', $event) && $event.description}
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

  {if array_key_exists('summary', $event) && $event.summary}
      <div class="crm-section event_summary-section">
        {$event.summary|purify}
      </div>
  {/if}

  {if array_key_exists('description', $event) && $event.description}
      <div class="crm-section event_description-section summary">
          {$event.description|purify}
      </div>
  {/if}
  <div class="clear"></div>
  <div class="crm-section event_date_time-section">
      <div class="label">{ts}When{/ts}</div>
      <div class="content">
        {strip}
            {if $event.event_start_date && array_key_exists('event_end_date', $event) && $event.event_end_date && ($event.event_end_date|crmDate:'%Y%m%d':0 == $event.event_start_date|crmDate:'%Y%m%d':0)}
              {$event.event_start_date|crmDate:'Full':0}
              &nbsp;{ts}from{/ts}&nbsp;
              {$event.event_start_date|crmDate:0:1}
            {else}
              {$event.event_start_date|crmDate}
            {/if}
            {if array_key_exists('event_end_date', $event) && $event.event_end_date}
                &nbsp;{ts}to{/ts}&nbsp;
                {* Only show end time if end date = start date *}
                {if $event.event_end_date|crmDate:"%Y%m%d":0 == $event.event_start_date|crmDate:"%Y%m%d":0}
                    {$event.event_end_date|crmDate:0:1}
                {else}
                    {$event.event_end_date|crmDate}
                {/if}
            {/if}
        {/strip}
      </div>
    <div class="clear"></div>
  </div>

  {if $isShowLocation}

        {if array_key_exists(1, $location.address) && $location.address.1}
            <div class="crm-section event_address-section">
                <div class="label">{ts}Location{/ts}</div>
                <div class="content">{$location.address.1.display|purify|nl2br}</div>
                <div class="clear"></div>
            </div>
        {/if}

      {if ($event.is_map && $config->mapProvider &&
          array_key_exists('address', $location)  && (is_numeric($location.address.1.geo_code_1)))}
          <div class="crm-section event_map-section">
              <div class="content">
                    {assign var=showDirectly value="1"}
                    {include file="CRM/Contact/Form/Task/Map/`$config->mapProvider`.tpl" fields=$showDirectly profileGID=false}
                    <a href="{$mapURL}" title="{ts}Show large map{/ts}">{ts}Show large map{/ts}</a>
              </div>
              <div class="clear"></div>
          </div>
      {/if}

  {/if}{*End of isShowLocation condition*}


  {if (array_key_exists(1, $location.phone) && $location.phone.1.phone)
    || (array_key_exists(1, $location.email) && $location.email.1.email)}
      <div class="crm-section event_contact-section">
          <div class="label">{ts}Contact{/ts}</div>
          <div class="content">
            {if array_key_exists('phone', $location)}
              {foreach from=$location.phone item=phone}
                  {if $phone.phone}
                    <div class="crm-eventinfo-contact-phone">
                      {* @todo This should use "{ts 1=$phone.phone_type_display 2=$phone}%1: %2{/ts}" because some language have nbsp before column *}
                      {if $phone.phone_type_id}{$phone.phone_type_display}:{else}{ts}Phone:{/ts}{/if}
                      <span class="tel">{$phone.phone|escape}{if array_key_exists('phone_ext', $phone)}&nbsp;{ts}ext.{/ts}&nbsp;{$phone.phone_ext|escape}{/if}</span>
                    </div>
                  {/if}
              {/foreach}
            {/if}
            {if array_key_exists('email', $location)}
              {foreach from=$location.email item=email}
                  {if $email.email}
                    <div class="crm-eventinfo-contact-email">
                      {ts}Email:{/ts} <span class="email"><a href="mailto:{$email.email|purify}">{$email.email|escape}</a></span>
                    </div>
                  {/if}
              {/foreach}
            {/if}
          </div>
          <div class="clear"></div>
      </div>

  {/if}

  {if $event.is_monetary eq 1 && $feeBlock.value}
      <div class="crm-section event_fees-section">
          <div class="label">{$event.fee_label|escape}</div>
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
                            {if $feeBlock.tax_amount && $feeBlock.tax_amount.$idx}
                              {$feeBlock.value.$idx}
                            {else}
                              {$feeBlock.value.$idx|crmMoney:$eventCurrency}
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


    {include file="CRM/Custom/Page/CustomDataView.tpl" groupId = false}

    <div class="crm-actionlinks-bottom">
      {crmRegion name="event-page-eventinfo-actionlinks-bottom"}
        {if $allowRegistration}
          <div class="action-link section register_link-section register_link-bottom">
            <a href="{$registerURL}" title="{$registerText|escape:'html'}" class="button crm-register-button"><span>{$registerText}</span></a>
          </div>
        {/if}
      {/crmRegion}
    </div>
    {if $event.is_public and $event.is_show_calendar_links}
        <div class="action-link section iCal_links-section">
          {include file="CRM/Event/Page/iCalLinks.tpl"}
        </div>
    {/if}

    {if $event.is_share}
        {capture assign=eventUrl}{crmURL p='civicrm/event/info' q="id=`$event.id`&amp;reset=1" a=1 fe=1 h=1}{/capture}
        {include file="CRM/common/SocialNetwork.tpl" url=$eventUrl title=$event.title pageURL=$eventUrl emailMode=true}
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
