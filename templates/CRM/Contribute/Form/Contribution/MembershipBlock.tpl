{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
{if !empty($useForMember) AND !$is_quick_config}
  <div id="membership" class="crm-group membership-group">
    {if $context EQ "makeContribution"}
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
            {if $membershipBlock.new_title}
              <legend>{$membershipBlock.new_title}</legend>
            {/if}
            {if $membershipBlock.new_text}
              <div id="membership-intro" class="crm-section membership_new_intro-section">
                {$membershipBlock.new_text}
              </div>
            {/if}
          {/if}
          {if !empty($membershipTypes)}
            {foreach from=$membershipTypes item=row}
              {if array_key_exists( 'current_membership', $row )}
                <div id='help'>
                  {* Lifetime memberships have no end-date so current_membership array key exists but is NULL *}
                  {if $row.current_membership}
                    {if $row.current_membership|date_format:"%Y%m%d" LT $smarty.now|date_format:"%Y%m%d"}
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

          {include file="CRM/Price/Form/PriceSet.tpl" extends="Membership"}
        </fieldset>
      </div>
    {elseif $lineItem and $priceSetID AND !$is_quick_config}
      {assign var="totalAmount" value=$amount}
      <div class="header-dark">
        {ts}Membership Fee{/ts}
      </div>
      <div class="display-block">
        {include file="CRM/Price/Page/LineItem.tpl" context="Membership"}
      </div>
    {/if}
  </div>
{literal}
  <script type="text/javascript">
    CRM.$(function($) {
      //if price set is set we use below below code to show for showing auto renew
      var autoRenewOption =  {/literal}'{$autoRenewOption}'{literal};
      var autoRenew = $("#auto_renew_section");
      var autoRenewCheckbox = $("#auto_renew");
      var forceRenew = $("#force_renew");
      autoRenew.hide();
      forceRenew.hide();
      if ( autoRenewOption == 1 ) {
        autoRenew.show();
      } else if ( autoRenewOption == 2 ) {
        autoRenewCheckbox.prop('checked',  true );
        autoRenewCheckbox.attr( 'readonly', true );
        autoRenew.hide();
        forceRenew.show();
      }
    });
  </script>
{/literal}
{elseif $membershipBlock AND !$is_quick_config}
  <div id="membership" class="crm-group membership-group">
    {if $context EQ "makeContribution"}
    <fieldset>
      {if $renewal_mode }
        {if $membershipBlock.renewal_title}
          <legend>{$membershipBlock.renewal_title}</legend>
        {/if}
        {if $membershipBlock.renewal_text}
          <div id="membership-intro" class="crm-section membership_renewal_intro-section">
            <p>{$membershipBlock.renewal_text}</p>
          </div>
        {/if}

      {else}
        {if $membershipBlock.new_title}
          <legend>{$membershipBlock.new_title}</legend>
        {/if}
        {if $membershipBlock.new_text}
          <div id="membership-intro" class="crm-section membership_new_intro-section">
            <p>{$membershipBlock.new_text}</p>
          </div>
        {/if}
      {/if}
      {/if}
      {if  $context neq "makeContribution" }
        <div class="header-dark">
          {if $renewal_mode }
            {if $membershipBlock.renewal_title}
              {$membershipBlock.renewal_title}
            {else}
              {ts}Select a Membership Renewal Level{/ts}
            {/if}

          {else}
            {if $membershipBlock.new_title}
              {$membershipBlock.new_title}
            {else}
              {ts}Select a Membership Level{/ts}
            {/if}
          {/if}
        </div>
      {/if}

      {if $context EQ "makeContribution"}
    </fieldset>
    {/if}
  </div>

{/if}{* membership block end here *}

{if $membershipBlock AND $is_quick_config}
  {if  $context neq "makeContribution" }
    <div class="header-dark">
      {if $renewal_mode }
        {if $membershipBlock.renewal_title}
          {$membershipBlock.renewal_title}
        {else}
          {ts}Select a Membership Renewal Level{/ts}
        {/if}
      {else}
        {if $membershipBlock.new_title}
          {$membershipBlock.new_title}
        {else}
          {ts}Select a Membership Level{/ts}
        {/if}
      {/if}
    </div>
  {/if}
  {strip}
    <table id="membership-listings">
      {foreach from=$membershipTypes item=row}
        <tr {if $context EQ "makeContribution"}class="odd-row" {/if}valign="top">
          {if $showRadio }
            {assign var="pid" value=$row.id}
            <td style="width: 1em;">{$form.selectMembership.$pid.html}</td>
          {else}
            <td>&nbsp;</td>
          {/if}
          <td style="width: auto;">
                <span class="bold">{$row.name} &nbsp;
                {if ($membershipBlock.display_min_fee AND $context EQ "makeContribution") AND $row.minimum_fee GT 0 }
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
            {if array_key_exists( 'current_membership', $row ) AND $context EQ "makeContribution" }
              {if $row.current_membership}
                {if $row.current_membership|date_format:"%Y%m%d" LT $smarty.now|date_format:"%Y%m%d"}
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
      {if isset($form.auto_renew) }
        <tr id="allow_auto_renew">
          <td style="width: auto;">{$form.auto_renew.html}</td>
          <td style="width: auto;">
            {$form.auto_renew.label}
          </td>
        </tr>
      {/if}
      {if $showRadio}
        {if $showRadioNoThanks } {* Provide no-thanks option when Membership signup is not required - per membership block configuration. *}
          <tr class="odd-row">
            <td>{$form.selectMembership.no_thanks.html}</td>
            <td colspan="2"><strong>{ts}No thank you{/ts}</strong></td>
          </tr>
        {/if}
      {/if}
    </table>
  {/strip}
{/if}
{* Include JS for auto renew membership if priceset is Quick Config*}
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
      if ( !memTypeId ) memTypeId = cj('input:radio[name='+priceSetName+']:checked').attr('membership-type');

      //does this page has only one membership type.
      var singleMembership = {/literal}'{$singleMembership}'{literal};
      if ( !memTypeId && singleMembership ) memTypeId = cj("input:radio[name="+priceSetName+"]").attr('membership-type');
      var renewOptions  = {/literal}{$autoRenewMembershipTypeOptions}{literal};
      var currentOption = eval( "renewOptions." + 'autoRenewMembershipType_' + memTypeId );
      var autoRenew = cj('#auto_renew_section');
      var autoRenewC = cj('input[name="auto_renew"]');
      var forceRenew = cj("#force_renew");

      var readOnly = false;
      var isChecked  = false;
      if ( currentOption == 0 ) {
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
