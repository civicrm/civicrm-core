{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Summary tab from Contact Summary screen *}

{if $hookContentPlacement !== 3}

  {if $hookContent && $hookContentPlacement eq 2}
    {include file="CRM/Contact/Page/View/SummaryHook.tpl"}
  {/if}

  <div class="contactTopBar contact_panel">
    <div class="contactCardLeft">
      {crmRegion name="contact-basic-info-left"}
        <div class="crm-summary-contactinfo-block">
          <div class="crm-summary-block" id="contactinfo-block">
            {include file="CRM/Contact/Page/Inline/ContactInfo.tpl"}
          </div>
        </div>
      {/crmRegion}
    </div>
    <div class="contactCardRight">
      {crmRegion name="contact-basic-info-right"}
      {if $imageURL}
        <div id="crm-contact-thumbnail">
          {include file="CRM/Contact/Page/ContactImage.tpl"}
        </div>
      {/if}
        <div class="{if $imageURL} float-left{/if}">
          <div class="crm-summary-basic-block crm-summary-block">
            {include file="CRM/Contact/Page/Inline/Basic.tpl"}
          </div>
        </div>
      {/crmRegion}
    </div>
  </div>
  <div class="contact_details">
    <div class="contact_panel">
      <div class="contactCardLeft">
        {crmRegion name="contact-details-left"}
          <div >
            {if $showEmail}
              <div class="crm-summary-email-block crm-summary-block" id="email-block">
                {include file="CRM/Contact/Page/Inline/Email.tpl"}
              </div>
            {/if}
            {if $showWebsite}
              <div class="crm-summary-website-block crm-summary-block" id="website-block">
                {include file="CRM/Contact/Page/Inline/Website.tpl"}
              </div>
            {/if}
          </div>
        {/crmRegion}
      </div><!-- #contactCardLeft -->

      <div class="contactCardRight">
        {crmRegion name="contact-details-right"}
          <div>
            {if $showPhone}
              <div class="crm-summary-phone-block crm-summary-block" id="phone-block">
                {include file="CRM/Contact/Page/Inline/Phone.tpl"}
              </div>
            {/if}
            {if $showIM}
              <div class="crm-summary-im-block crm-summary-block" id="im-block">
                {include file="CRM/Contact/Page/Inline/IM.tpl"}
              </div>
            {/if}
            {if $showOpenID}
              <div class="crm-summary-openid-block crm-summary-block" id="openid-block">
                {include file="CRM/Contact/Page/Inline/OpenID.tpl"}
              </div>
            {/if}
          </div>
        {/crmRegion}
      </div><!-- #contactCardRight -->

      <div class="clear"></div>
    </div><!-- #contact_panel -->
    {if $showAddress}
      <div class="contact_panel">
        {crmRegion name="contact-addresses"}
        {assign var='locationIndex' value=1}
        {if $address}
          {foreach from=$address item=add key=locationIndex}
            <div class="{if $locationIndex is odd}contactCardLeft{else}contactCardRight{/if} crm-address_{$locationIndex} crm-address-block crm-summary-block">
              {include file="CRM/Contact/Page/Inline/Address.tpl"}
            </div>
          {/foreach}
          {assign var='locationIndex' value=$locationIndex+1}
        {/if}
          {* add new link *}
        {if $permission EQ 'edit'}
          {assign var='add' value=0}
          <div class="{if $locationIndex is odd}contactCardLeft{else}contactCardRight{/if} crm-address-block crm-summary-block">
            {include file="CRM/Contact/Page/Inline/Address.tpl"}
          </div>
        {/if}
        {/crmRegion}
      </div> <!-- end of contact panel -->
    {/if}
    <div class="contact_panel">
      {if $showCommunicationPreferences}
        <div class="contactCardLeft">
          {crmRegion name="contact-comm-pref"}
            <div class="crm-summary-comm-pref-block">
              <div class="crm-summary-block" id="communication-pref-block" >
                {include file="CRM/Contact/Page/Inline/CommunicationPreferences.tpl"}
              </div>
            </div>
          {/crmRegion}
        </div> <!-- contactCardLeft -->
      {/if}
      {if $contact_type eq 'Individual' AND $showDemographics}
        <div class="contactCardRight">
          {crmRegion name="contact-demographic"}
            <div class="crm-summary-demographic-block">
              <div class="crm-summary-block" id="demographic-block">
                {include file="CRM/Contact/Page/Inline/Demographics.tpl"}
              </div>
            </div>
          {/crmRegion}
        </div> <!-- contactCardRight -->
      {/if}
      <div class="clear"></div>
      <div class="separator"></div>
    </div> <!-- contact panel -->
  </div><!--contact_details-->

  {if $showCustomData}
    <div id="customFields">
      <div class="contact_panel">
        <div class="contactCardLeft">
          {include file="CRM/Contact/Page/View/CustomDataView.tpl" side='1' skipTitle=false}
        </div><!--contactCardLeft-->
        <div class="contactCardRight">
          {include file="CRM/Contact/Page/View/CustomDataView.tpl" side='0' skipTitle=false}
        </div>

        <div class="clear"></div>
      </div>
    </div>
  {/if}

  {if $hookContent && $hookContentPlacement eq 1}
    {include file="CRM/Contact/Page/View/SummaryHook.tpl"}
  {/if}
{else}
  {include file="CRM/Contact/Page/View/SummaryHook.tpl"}
{/if}
