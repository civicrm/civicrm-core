{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
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
{if $products}
  <div id="premiums" class="premiums-group">
    {if $context EQ "makeContribution"}
      <fieldset class="crm-group premiums_select-group">
      {if $premiumBlock.premiums_intro_title}
        <legend>{$premiumBlock.premiums_intro_title}</legend>
      {/if}
      {if $premiumBlock.premiums_intro_text}
        <div id="premiums-intro" class="crm-section premiums_intro-section">
          {$premiumBlock.premiums_intro_text}
        </div> 
      {/if}
    {/if}

    {if $context EQ "confirmContribution" OR $context EQ "thankContribution"}
    <div class="crm-group premium_display-group">
      <div class="header-dark">
        {if $premiumBlock.premiums_intro_title}
          {$premiumBlock.premiums_intro_title}
        {else}
          {ts}Your Premium Selection{/ts}
        {/if}
      </div>
    {/if}

    {if $preview}
      {assign var="showSelectOptions" value="1"}
    {/if}

    {strip}
      <div id="premiums-listings">
      {if $showPremium AND !$preview AND $premiumBlock.premiums_nothankyou_position EQ 1}
        <div class="premium premium-no_thanks" id="premium_id-no_thanks" min_contribution="0">
          <div class="premium-short">
            <input type="checkbox" disabled="disabled" /> {$premiumBlock.premiums_nothankyou_label}
          </div>
          <div class="premium-full">
            <input type="checkbox" checked="checked" disabled="disabled" /> {$premiumBlock.premiums_nothankyou_label}
          </div>
        </div>
      {/if}          
      {foreach from=$products item=row}
        <div class="premium {if $showPremium}premium-selectable{/if}" id="premium_id-{$row.id}" min_contribution="{$row.min_contribution}">
          <div class="premium-short">
            {if $row.thumbnail}<div class="premium-short-thumbnail"><img src="{$row.thumbnail}" alt="{$row.name}" /></div>{/if}
            <div class="premium-short-content">{$row.name}</div>
            <div style="clear:both"></div>
          </div>

          <div class="premium-full">
            <div class="premium-full-image">{if $row.image}<img src="{$row.image}" alt="{$row.name}" />{/if}</div>
            <div class="premium-full-content">
              <div class="premium-full-title">{$row.name}</div>
              <div class="premium-full-disabled">
                {ts 1=$row.min_contribution|crmMoney}You must contribute at least %1 to get this item{/ts}<br/>
                <input type="button" value="{ts 1=$row.min_contribution|crmMoney}Contribute %1 Instead{/ts}" amount="{$row.min_contribution}" />
              </div>
              <div class="premium-full-description">
                {$row.description}
              </div>
              {if $showSelectOptions }
                {assign var="pid" value="options_"|cat:$row.id}
                {if $pid}
                  <div class="premium-full-options">
                    <p>{$form.$pid.html}</p>
                  </div>
                {/if}
              {else}
                <div class="premium-full-options">
                  <p><strong>{$row.options}</strong></p> 
                </div>
              {/if}
              {if ( ($premiumBlock.premiums_display_min_contribution AND $context EQ "makeContribution") OR $preview EQ 1) AND $row.min_contribution GT 0 }
                <div class="premium-full-min">{ts 1=$row.min_contribution|crmMoney}Minimum: %1{/ts}</div>
              {/if}
            <div style="clear:both"></div>
            </div>
          </div>
          <div style="clear:both"></div>
        </div>
      {/foreach}
      {if $showPremium AND !$preview AND $premiumBlock.premiums_nothankyou_position EQ 2}
        <div class="premium premium-no_thanks" id="premium_id-no_thanks" min_contribution="0">
          <div class="premium-short">
            <input type="checkbox" disabled="disabled" /> {$premiumBlock.premiums_nothankyou_label}
          </div>
          <div class="premium-full">
            <input type="checkbox" checked="checked" disabled="disabled" /> {$premiumBlock.premiums_nothankyou_label}
          </div>
        </div>
      {/if}          
      </div>
    {/strip}

    {if $context EQ "makeContribution"}
      </fieldset>
    {elseif ! $preview} {* Close premium-display-group div for Confirm and Thank-you pages *}
      </div>
    {/if}
  </div>
  
  {if $context EQ "makeContribution"}
    {literal}
    <script> 
      cj(function($){
        var is_separate_payment = {/literal}{if $membershipBlock.is_separate_payment}{$membershipBlock.is_separate_payment}{else}0{/if}{literal};

        // select a new premium
        function select_premium(premium_id) { 
          if(cj(premium_id).length) {
            // hide other active premium
            cj('.premium-full').hide();
            cj('.premium-short').show();
            // show this one
            cj('.premium-short', cj(premium_id)).hide();
            cj('.premium-full', cj(premium_id)).show();
            // record this one
            var id_parts = premium_id.split('-');
            cj('#selectProduct').val(id_parts[1]);
          }
        }

        // click premium to select
        cj('.premium-short').click(function(){
          select_premium( '#'+cj(cj(this).parent()).attr('id') );
        });

        // select the default premium
        var premium_id = cj('#selectProduct').val();
        if(premium_id == '') premium_id = 'no_thanks';
        select_premium('#premium_id-'+premium_id);

        // get the current amount
        function get_amount() {
          var amount;

          // see if other amount exists and has a value
          if(cj('.other_amount-content input').length) {
            amount = Number(cj('.other_amount-content input').val());
            if(isNaN(amount))
              amount = 0;
          }

          function check_price_set(price_set_radio_buttons) {
            if(!amount) {
              cj(price_set_radio_buttons).each(function(){
                if(cj(this).attr('checked')) {
                  amount = cj(this).attr('data-amount');
                  if(amount) {
                    amount = Number(amount);
                    if(isNaN(amount))
                      amount = 0;
                  }
                }
              });
            }
          }

          // check for additional contribution
          var additional_amount = 0;
          if(is_separate_payment) {
            additional_amount = amount;
            amount = 0;
          }

          // next, check for contribution amount price sets
          check_price_set('.contribution_amount-content input[type="radio"]');

          // next, check for membership level price set
          check_price_set('.membership_amount-content input[type="radio"]'); 

          // make sure amount is a number at this point
          if(!amount) amount = 0;

          // account for is_separate_payment
          if(is_separate_payment && additional_amount) {
            amount += additional_amount;
          }

          return amount;
        }

        // update premiums
        function update_premiums() {
          var amount = get_amount();

          cj('.premium').each(function(){
            var min_contribution = cj(this).attr('min_contribution');
            if(amount < min_contribution) {
              cj(this).addClass('premium-disabled');
            } else {
              cj(this).removeClass('premium-disabled');
            }
          });
        }
        cj('.other_amount-content input').change(update_premiums);
        cj('.contribution_amount-content input[type="radio"]').click(update_premiums);
        cj('.membership_amount-content input[type="radio"]').click(update_premiums);
        update_premiums();

        // build a list of price sets
        var amounts = [];
        var price_sets = {};
        cj('#priceset input[type="radio"]').each(function(){
          var amount = Number(cj(this).attr('data-amount'));
          if(!isNaN(amount)) {
            amounts.push(amount);

            var id = cj(this).attr('id');
            price_sets[amount] = '#'+id;
          }
        });
        amounts.sort(function(a,b){return a - b});

        // make contribution instead buttons work
        cj('.premium-full-disabled input').click(function(){
          var amount = Number(cj(this).attr('amount'));
          if(price_sets[amount]) {
            cj(price_sets[amount]).click();
          } else {
            // is there an other amount input box?
            if(cj('.other_amount-section input').length) {
              // is this a membership form with separate payment?
              if(is_separate_payment) {
                var current_amount = 0;
                if(cj('#priceset input[type="radio"]:checked').length) {
                  current_amount = Number(cj('#priceset input[type="radio"]:checked').attr('data-amount'));
                  if(!current_amount) current_amount = 0;
                }
                var new_amount = amount - current_amount;
                cj('.other_amount-section input').val(new_amount.toFixed(2));
              } else {
                cj('.other_amount-section input').click();
                cj('.other_amount-section input').val(cj(this).attr('amount'));
              }
            } else {
              // find the next best price set
              var selected_price_set = false;
              for(var i in amounts) {
                if(amounts[i] >= amount) {
                  selected_price_set = amounts[i];
                  break;
                }
              }
              if(!selected_price_set) {
                selected_price_set = amounts[amounts.length-1];
              }
              cj(price_sets[selected_price_set]).click();
            }
          }
          update_premiums();
        });

        // validation of premiums
        var error_message = '{/literal}{ts escape="js"}You must contribute more to get that item{/ts}{literal}';
        cj.validator.addMethod('premiums', function(value, element, params){
          var premium_id = cj('#selectProduct').val();
          var premium$ = cj('#premium_id-'+premium_id);
          if(premium$.length) {
            if(premium$.hasClass('premium-disabled')) {
              return false;
            }
          }
          return true;
        }, error_message);
        
        // add validation rules
        CRM.validate.functions.push(function(){
          cj('#selectProduct').rules('add', 'premiums');
        });
        
        // need to use jquery validate's ignore option, so that it will not ignore hidden fields
        CRM.validate.params['ignore'] = '.ignore';
      });
    </script>
    {/literal}

  {else}
    {literal}
    <script>
      cj(function(){
        cj('.premium-short').hide();
        cj('.premium-full').show();
      });
    </script>
    {/literal}
  {/if}
{/if}

