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
{if $action & 1024}
    {include file="CRM/Event/Form/Registration/PreviewHeader.tpl"}
{/if}

{include file="CRM/common/TrackingFields.tpl"}

<div class="crm-event-id-{$event.id} crm-block crm-event-thankyou-form-block">
    {* Don't use "normal" thank-you message for Waitlist and Approval Required registrations - since it will probably not make sense for those situations. dgg *}
    {if $event.thankyou_text AND (not $isOnWaitlist AND not $isRequireApproval)}
        <div id="intro_text" class="crm-section event_thankyou_text-section">
            <p>
            {$event.thankyou_text}
            </p>
        </div>
    {/if}

    {* Show link to Tell a Friend (CRM-2153) *}
    {if $friendText}
        <div id="tell-a-friend" class="crm-section tell_friend_link-section">
            <a href="{$friendURL}" title="{$friendText}" class="button"><span>&raquo; {$friendText}</span></a>
       </div><br /><br />
    {/if}

    {* Add button for donor to create their own Personal Campaign page *}
    {if $pcpLink}
      <div class="crm-section create_pcp_link-section">
            <a href="{$pcpLink}" title="{$pcpLinkText}" class="button"><span>&raquo; {$pcpLinkText}</span></a>
        </div><br /><br />
    {/if}

    <div id="help">
        {if $isOnWaitlist}
            <p>
                <span class="bold">{ts}You have been added to the WAIT LIST for this event.{/ts}</span>
                {ts}If space becomes available you will receive an email with a link to a web page where you can complete your registration.{/ts}
             </p>
        {elseif $isRequireApproval}
            <p>
                <span class="bold">{ts}Your registration has been submitted.{/ts}
                {ts}Once your registration has been reviewed, you will receive an email with a link to a web page where you can complete the registration process.{/ts}</span>
            </p>
        {elseif $is_pay_later and $paidEvent and !$isAmountzero}
            <div class="bold">{$pay_later_receipt}</div>
            {if $is_email_confirm}
                <p>{ts 1=$email}An email with event details has been sent to %1.{/ts}</p>
            {/if}
        {* PayPal_Standard sets contribution_mode to 'notify'. We don't know if transaction is successful until we receive the IPN (payment notification) *}
        {elseif $contributeMode EQ 'notify' and $paidEvent}
            <p>{ts 1=$paymentProcessor.name}Your registration payment has been submitted to %1 for processing. Please print this page for your records.{/ts}</p>
            {if $is_email_confirm}
                <p>{ts 1=$email}A registration confirmation email will be sent to %1 once the transaction is processed successfully.{/ts}</p>
            {/if}
        {else}
            <p>{ts}Your registration has been processed successfully. Please print this page for your records.{/ts}</p>
            {if $is_email_confirm}
                <p>{ts 1=$email}A registration confirmation email has also been sent to %1{/ts}</p>
            {/if}
        {/if}
    </div>
    <div class="spacer"></div>

    <div class="crm-group event_info-group">
        <div class="header-dark">
            {ts}Event Information{/ts}
        </div>
        <div class="display-block">
            {include file="CRM/Event/Form/Registration/EventInfoBlock.tpl" context="ThankYou"}
        </div>
    </div>

    {if $paidEvent}
        <div class="crm-group event_fees-group">
            <div class="header-dark">
                {$event.fee_label}
            </div>
            {if $lineItem}
                {include file="CRM/Price/Page/LineItem.tpl" context="Event"}
            {elseif $amount || $amount == 0}
              <div class="crm-section no-label amount-item-section">
                    {foreach from= $finalAmount item=amount key=level}
                  <div class="content">
                      {$amount.amount|crmMoney}&nbsp;&nbsp;{$amount.label}
                  </div>
                  <div class="clear"></div>
                    {/foreach}
                </div>
                {if $totalAmount}
                 <div class="crm-section no-label total-amount-section">
                    <div class="content bold">{ts}Event Total{/ts}:&nbsp;&nbsp;{$totalAmount|crmMoney}</div>
                    <div class="clear"></div>
                  </div>

                    {if $hookDiscount.message}
                        <div class="crm-section hookDiscount-section">
                            <em>({$hookDiscount.message})</em>
                        </div>
                    {/if}
                {/if}
            {/if}

            {if $receive_date}
                <div class="crm-section no-label receive_date-section">
                    <div class="content bold">{ts}Transaction Date{/ts}: {$receive_date|crmDate}</div>
                  <div class="clear"></div>
                </div>
            {/if}
            {if $contributeMode ne 'notify' AND $trxn_id}
                <div class="crm-section no-label trxn_id-section">
                    <div class="content bold">{ts}Transaction #{/ts}: {$trxn_id}</div>
                <div class="clear"></div>
              </div>
            {/if}
        </div>

    {elseif $participantInfo}
        <div class="crm-group participantInfo-group">
            <div class="header-dark">
                {ts}Additional Participant Email(s){/ts}
            </div>
            <div class="crm-section no-label participant_info-section">
                <div class="content">
                    {foreach from=$participantInfo  item=mail key=no}
                        <strong>{$mail}</strong><br />
                    {/foreach}
                </div>
            <div class="clear"></div>
          </div>
        </div>
    {/if}

    {if $event.participant_role neq 'Attendee' and $defaultRole}
        <div class="crm-group participant_role-group">
            <div class="header-dark">
                {ts}Participant Role{/ts}
            </div>
            <div class="crm-section no-label participant_role-section">
                <div class="content">
                    {$event.participant_role}
                </div>
            <div class="clear"></div>
          </div>
        </div>
    {/if}

    {include file="CRM/Event/Form/Registration/DisplayProfile.tpl"}

    {if $contributeMode ne 'notify' and $paidEvent and ! $is_pay_later and ! $isAmountzero and !$isOnWaitlist and !$isRequireApproval}
        <div class="crm-group billing_name_address-group">
            <div class="header-dark">
                {ts}Billing Name and Address{/ts}
            </div>
          <div class="crm-section no-label billing_name-section">
            <div class="content">{$billingName}</div>
            <div class="clear"></div>
          </div>
          <div class="crm-section no-label billing_address-section">
            <div class="content">{$address|nl2br}</div>
            <div class="clear"></div>
          </div>
        </div>
    {/if}

    {if $contributeMode eq 'direct' and $paidEvent and ! $is_pay_later and !$isAmountzero and !$isOnWaitlist and !$isRequireApproval}
        <div class="crm-group credit_card-group">
            <div class="header-dark">
                {ts}Credit Card Information{/ts}
            </div>
            <div class="crm-section no-label credit_card_details-section">
                <div class="content">{$credit_card_type}</div>
            <div class="content">{$credit_card_number}</div>
            <div class="content">{ts}Expires{/ts}: {$credit_card_exp_date|truncate:7:''|crmDate}</div>
            <div class="clear"></div>
          </div>
        </div>
    {/if}

    {if $event.thankyou_footer_text}
        <div id="footer_text" class="crm-section event_thankyou_footer-section">
            <p>{$event.thankyou_footer_text}</p>
        </div>
    {/if}

    <div class="action-link section event_info_link-section">
        <a href="{crmURL p='civicrm/event/info' q="reset=1&id=`$event.id`"}">&raquo; {ts 1=$event.event_title}Back to "%1" event information{/ts}</a>
    </div>

    {if $event.is_public }
        {include file="CRM/Event/Page/iCalLinks.tpl"}
    {/if}
    {if $event.is_share}
    {capture assign=eventUrl}{crmURL p='civicrm/event/info' q="id=`$event.id`&amp;reset=1" a=1 fe=1 h=1}{/capture}
    {include file="CRM/common/SocialNetwork.tpl" url=$eventUrl title=$event.title pageURL=$eventUrl}
    {/if}
</div>
