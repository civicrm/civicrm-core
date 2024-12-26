{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div id="membership" class="crm-group membership-group">
  <div id="priceset">
    <fieldset>
      {if $renewal_mode}
        {if $membershipBlock.renewal_title}
          <legend>{$membershipBlock.renewal_title}</legend>
        {/if}
        {if $membershipBlock.renewal_text}
          <div id="membership-intro" class="crm-section membership_renewal_intro-section">
            {$membershipBlock.renewal_text}
          </div>
        {/if}
      {else}
        {if array_key_exists('new_title', $membershipBlock) && $membershipBlock.new_title}
          <legend>{$membershipBlock.new_title}</legend>
        {/if}
        {if array_key_exists('new_text', $membershipBlock) && $membershipBlock.new_text}
          <div id="membership-intro" class="crm-section membership_new_intro-section">
            {$membershipBlock.new_text}
          </div>
        {/if}
      {/if}
      {if !empty($membershipTypes)}
        {foreach from=$membershipTypes item=row}
          {if array_key_exists( 'current_membership', $row )}
            <div class='help'>
              {* Lifetime memberships have no end-date so current_membership array key exists but is NULL *}
              {if $row.current_membership}
                {if $row.current_membership|crmDate:"%Y%m%d" LT $smarty.now|crmDate:"%Y%m%d"}
                  {ts 1=$row.current_membership|crmDate 2=$row.name}Your <strong>%2</strong> membership expired on %1.{/ts}<br />
                {else}
                  {ts 1=$row.current_membership|crmDate 2=$row.name}Your <strong>%2</strong> membership expires on %1.{/ts}<br />
                {/if}
              {else}
                {ts 1=$row.name}Your <strong>%1</strong> membership does not expire (you do not need to renew that membership).{/ts}<br />
              {/if}
            </div>
          {/if}
        {/foreach}
      {/if}
      {include file="CRM/Price/Form/PriceSet.tpl" extends="Membership" hideTotal=$quickConfig}
    </fieldset>
  </div>
  {literal}
  <script type="text/javascript">

  </script>
{/literal}
</div>

{if $isPaymentOnExistingContribution && $membershipBlock && $is_quick_config}
    {* This code is hit only when ccid is in the url to pay on an existing contribution and it is quick config *}
    {* Removing the contents of this if does not result in any apparent change & it may be removable *}
    {strip}
      <table id="membership-listings">
          {foreach from=$membershipTypes item=row}
            <tr class="odd-row" valign="top">
                {if $showRadio}
                    {* unreachable - show radio is never true *}
                    {assign var="pid" value=$row.id}
                  <td style="width: 1em;">{$form.selectMembership.$pid.html}</td>
                {else}
                  <td>&nbsp;</td>
                {/if}
              <td style="width: auto;">
                <span class="bold">{$row.name} &nbsp;
                {if ($membershipBlock.display_min_fee) AND $row.minimum_fee GT 0}
                    {if $is_separate_payment OR ! $form.amount.label}
                      &ndash; {$row.minimum_fee|crmMoney}
                    {else}
                        {ts 1=$row.minimum_fee|crmMoney}(contribute at least %1 to be eligible for this membership){/ts}
                    {/if}
                {/if}
                </span><br />
                  {$row.description} &nbsp;
              </td>

              <td style="width: auto;">
                  {* Check if there is an existing membership of this type (current_membership NOT empty) and if the end-date is prior to today. *}
                  {if array_key_exists( 'current_membership', $row)}
                      {if $row.current_membership}
                          {if $row.current_membership|crmDate:"%Y%m%d" LT $smarty.now|crmDate:"%Y%m%d"}
                            <br /><em>{ts 1=$row.current_membership|crmDate 2=$row.name}Your <strong>%2</strong> membership expired on %1.{/ts}</em>
                          {else}
                            <br /><em>{ts 1=$row.current_membership|crmDate 2=$row.name}Your <strong>%2</strong> membership expires on %1.{/ts}</em>
                          {/if}
                      {else}
                          {ts 1=$row.name}Your <strong>%1</strong> membership does not expire (you do not need to renew that membership).{/ts}<br />
                      {/if}
                  {else}
                    &nbsp;
                  {/if}
              </td>
            </tr>

          {/foreach}
          {if $form.auto_renew}
            <tr id="allow_auto_renew">
              <td style="width: auto;">{$form.auto_renew.html}</td>
              <td style="width: auto;">
                  {$form.auto_renew.label}
              </td>
            </tr>
          {/if}
          {if $showRadio}{* unreachable *}
              {if $showRadioNoThanks} {* Provide no-thanks option when Membership signup is not required - per membership block configuration. *}
                <tr class="odd-row">
                  <td>{$form.selectMembership.no_thanks.html}</td>
                  <td colspan="2"><strong>{ts}No thank you{/ts}</strong></td>
                </tr>
              {/if}
          {/if}
      </table>
    {/strip}
{/if}

{if $membershipBlock}
{literal}
  <script type="text/javascript">
    CRM.$(function($) {
      showHideAutoRenew( null );
    });
    function showHideAutoRenew( memTypeId )
    {
      var priceSetName = "price_"+{/literal}'{$membershipFieldID}'{literal};
      var considerUserInput = {/literal}'{$takeUserSubmittedAutoRenew}'{literal};
      if ( memTypeId ) considerUserInput = false;
      if ( !memTypeId ) memTypeId = cj('input:radio[name='+priceSetName+']:checked').data('membership-type-id');

      //does this page has only one membership type.
      var renewOptions  = {/literal}{$autoRenewMembershipTypeOptions}{literal};
      var currentOption = eval( "renewOptions." + 'autoRenewMembershipType_' + memTypeId );
      if (memTypeId === undefined) {
        currentOption = 0;
      }
      var autoRenew = cj('#auto_renew_section');
      var autoRenewC = cj('input[name="auto_renew"]');
      var forceRenew = cj("#force_renew");

      var readOnly = false;
      var isChecked  = false;
      if (currentOption == 0 ) {
        isChecked = false;
        forceRenew.hide();
        autoRenew.hide();
      }
      if ( currentOption == 1 ) {
        forceRenew.hide();
        autoRenew.show();

        //uncomment me, if we'd like
        //to load auto_renew checked.
        //isChecked = true;
      } else if ( currentOption == 2 || currentOption == 4) {
        autoRenew.hide();
        forceRenew.show();
        isChecked = readOnly = true;
      }

      if ( considerUserInput ) isChecked = autoRenew.prop('checked' );

      //its a normal recur contribution.
      if ( cj( "is_recur" ) &&
        ( cj( 'input:radio[name="is_recur"]:checked').val() == 1 ) ) {
        isChecked = false;
        autoRenew.hide();
        forceRenew.hide();
      }

      autoRenewC.attr( 'readonly', readOnly );
      autoRenewC.prop('checked',  isChecked );
    }

    {/literal}{if $allowAutoRenewMembership}{literal}
    CRM.$(function($) {
      //keep read only always checked.
      cj( "#auto_renew" ).click(function( ) {
        if ( cj(this).attr( 'readonly' ) ) {
          cj(this).prop('checked', true );
        }
      });
    });
    {/literal}{/if}{literal}
  </script>
{/literal}
{/if}
