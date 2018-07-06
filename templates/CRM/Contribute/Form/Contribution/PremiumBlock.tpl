{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
            {if $row.thumbnail}<div class="premium-short-thumbnail"><img src="{$row.thumbnail}" alt="{$row.name|escape}" /></div>{/if}
            <div class="premium-short-content">{$row.name}</div>
            <div style="clear:both"></div>
          </div>

          <div class="premium-full">
            <div class="premium-full-image">{if $row.image}<img src="{$row.image}" alt="{$row.name|escape}" />{/if}</div>
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
      CRM.$(function($) {
        var is_separate_payment = {/literal}{if $membershipBlock.is_separate_payment}{$membershipBlock.is_separate_payment}{else}0{/if}{literal};

        // select a new premium
        function select_premium(premium_id) {
          if($(premium_id).length) {
            // hide other active premium
            $('.premium-full').hide();
            $('.premium-short').show();
            // show this one
            $('.premium-short', $(premium_id)).hide();
            $('.premium-full', $(premium_id)).show();
            // record this one
            var id_parts = premium_id.split('-');
            $('#selectProduct').val(id_parts[1]);
          }
        }

        // click premium to select
        $('.premium-short').click(function(){
          select_premium( '#'+$($(this).parent()).attr('id') );
        });

        // select the default premium
        var premium_id = $('#selectProduct').val();
        if(premium_id == '') premium_id = 'no_thanks';
        select_premium('#premium_id-'+premium_id);

        // get the current amount
        function get_amount() {
          var amount;

          if (typeof totalfee !== "undefined") {
            return totalfee;
          }

          // see if other amount exists and has a value
          if($('.other_amount-content input').length) {
            amount = Number($('.other_amount-content input').val());
            if(isNaN(amount))
              amount = 0;
          }

          function check_price_set(price_set_radio_buttons) {
            if (!amount) {
              $(price_set_radio_buttons).each(function(){
                if ($(this).prop('checked')) {
                  amount = $(this).attr('data-amount');
                  if (typeof amount !== "undefined") {
                    amount = Number(amount);
                  }
                  else {
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

          // make sure amount is a number at this point
          if(!amount) amount = 0;

          // next, check for membership/contribution level price set
          check_price_set('#priceset input[type="radio"]');

          // account for is_separate_payment
          if(is_separate_payment && additional_amount) {
            amount += additional_amount;
          }

          return amount;
        }

        // update premiums
        function update_premiums() {
          var amount = get_amount();

          $('.premium').each(function(){
            var min_contribution = $(this).attr('min_contribution');
            if(amount < min_contribution) {
              $(this).addClass('premium-disabled');
            } else {
              $(this).removeClass('premium-disabled');
            }
          });
        }
        $('.other_amount-content input').change(update_premiums);
        $('input, #priceset').change(update_premiums);
        update_premiums();

        // build a list of price sets
        var amounts = [];
        var price_sets = {};
        $('input, #priceset  select,#priceset').each(function(){
          if (this.tagName == 'SELECT') {
            var selectID = $(this).attr('id');
            var selectvalues = JSON.parse($(this).attr('price'));
            Object.keys(selectvalues).forEach(function (key) {
              var option = selectvalues[key].split(optionSep);
              amount = Number(option[0]);
              price_sets[amount] = '#' + selectID + '-' + key;
              amounts.push(amount);
            });
          }
          else {
            var amount = Number($(this).attr('data-amount'));
            if (!isNaN(amount)) {
              amounts.push(amount);

             var id = $(this).attr('id');
             price_sets[amount] = '#'+id;
            }
          }
        });
        amounts.sort(function(a,b){return a - b});

        // make contribution instead buttons work
        $('.premium-full-disabled input').click(function(){
          var amount = Number($(this).attr('amount'));
          if (price_sets[amount]) {
            if (!$(price_sets[amount]).length) {
              var option =  price_sets[amount].split('-');
              $(option[0]).val(option[1]);
              $(option[0]).trigger('change');
            }
            else if ($(price_sets[amount]).attr('type') == 'checkbox') {
               $(price_sets[amount]).prop("checked",true);
               if ((typeof totalfee !== 'undefined') && (typeof display == 'function')) {
                 if (totalfee > 0) {
                   totalfee += amount;
                 }
                 else {
                   totalfee = amount;
                 }
                 display(totalfee);
               }
             }
             else {
               $(price_sets[amount]).click();
               $(price_sets[amount]).trigger('click');
             }
          } else {
            // is there an other amount input box?
            if($('.other_amount-section input').length) {
              // is this a membership form with separate payment?
              if(is_separate_payment) {
                var current_amount = 0;
                if($('#priceset input[type="radio"]:checked').length) {
                  current_amount = Number($('#priceset input[type="radio"]:checked').attr('data-amount'));
                  if(!current_amount) current_amount = 0;
                }
                var new_amount = amount - current_amount;
                $('.other_amount-section input').val(new_amount.toFixed(2));
              } else {
                $('.other_amount-section input').click();
                $('.other_amount-section input').val($(this).attr('amount'));
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

              if (!$(price_sets[selected_price_set]).length) {
                var option =  price_sets[selected_price_set].split('-');
                $(option[0]).val(option[1]);
                $(option[0]).trigger('change');
              }
              else if ($(price_sets[selected_price_set]).attr('type') == 'checkbox') {
                $(price_sets[selected_price_set]).prop("checked",true);
                if ((typeof totalfee !== 'undefined') && (typeof display == 'function')) {
                  if (totalfee > 0) {
                    totalfee += amount;
                  }
                  else {
                    totalfee = amount;
                  }
                  display(totalfee);
                }
             }
             else {
               $(price_sets[selected_price_set]).click();
               $(price_sets[selected_price_set]).trigger('click');
             }
            }
          }
          update_premiums();
        });

        // validation of premiums
        var error_message = '{/literal}{ts escape="js"}You must contribute more to get that item{/ts}{literal}';
        $.validator.addMethod('premiums', function(value, element, params){
          var premium_id = $('#selectProduct').val();
          var premium$ = $('#premium_id-'+premium_id);
          if(premium$.length) {
            if(premium$.hasClass('premium-disabled')) {
              return false;
            }
          }
          return true;
        }, error_message);

        // add validation rules
        CRM.validate.functions.push(function(){
          $('#selectProduct').rules('add', 'premiums');
        });

        // need to use jquery validate's ignore option, so that it will not ignore hidden fields
        CRM.validate.params['ignore'] = '.ignore';
      });
    </script>
    {/literal}

  {else}
    {literal}
    <script>
      CRM.$(function($) {
        cj('.premium-short').hide();
        cj('.premium-full').show();
      });
    </script>
    {/literal}
  {/if}
{/if}

