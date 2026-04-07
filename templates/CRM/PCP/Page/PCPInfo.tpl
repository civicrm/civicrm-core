{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $owner.viewAdminLinks}
<div class="messages status no-popup">
  <p>{icon icon="fa-info-circle"}{/icon} {ts 1="href='$parentURL'" 2=$pageName}This is a Personal Campaign Page in support of <a %1>%2</a>.{/ts} {ts 1=$owner.status}The current status of your page is: %1.{/ts}</p>
  {if $pcp.status_id NEQ 2}<p class="description">{ts}You will receive an email notification when your page is Approved and you can begin promoting your campaign.{/ts}</p>{/if}
  {if $pcp.page_type EQ 'event'}
    {if $owner.registration_start_date and $owner.registration_end_date}
      {assign var=startDateFormatted value=$owner.registration_start_date|truncate:10:''|crmDate}
      {assign var=endDateFormatted value=$owner.registration_end_date|truncate:10:''|crmDate}
      <p>{ts 1=$startDateFormatted 2=$endDateFormatted}People can register for this event starting on %1 until %2.{/ts}</p>
    {elseif $owner.registration_start_date}
      {assign var=startDateFormatted value=$owner.registration_start_date|truncate:10:''|crmDate}
      <p>{ts 1=$startDateFormatted}People can register for this event starting on %1.{/ts}</p>
    {/if}
  {elseif $owner.start_date and $owner.end_date}
    {assign var=startDateFormatted value=$owner.start_date|truncate:10:''|crmDate}
    {assign var=endDateFormatted value=$owner.end_date|truncate:10:''|crmDate}
    <p>{ts 1=$startDateFormatted 2=$endDateFormatted}This campaign is active from %1 until %2.{/ts}</p>
  {elseif $owner.start_date}
    {assign var=startDateFormatted value=$owner.start_date|truncate:10:''|crmDate}
    <p>{ts 1=$startDateFormatted}This campaign is active from %1.{/ts}</p>
  {/if}
  <p>{ts}You can:{/ts}</p>
  <table class="form-layout-compressed">
    <tr>
    </tr>
    {foreach from=$links key=k item=v}
      <tr>
        <td><a href="{crmURL p=$v.url q=$v.qs|replace:'%%pcpId%%':$replace.id|replace:'%%pageComponent%%':$replace.pageComponent|replace:'%%pcpBlock%%':$replace.block}" title="{$v.title|escape:'html'}" {if $v.extra}{$v.extra}{/if}><strong><i class="crm-i fa-chevron-right" role="img" aria-hidden="true"></i> {$v.name}</strong></a></td>
        <td>&nbsp;<cite>{$hints.$k}</cite></td>
      </tr>
    {/foreach}
  </table>
  <i class="crm-i fa-lightbulb-o" role="img" aria-hidden="true"></i>
  <strong>{ts}Tip{/ts}</strong> - <span class="description">{ts}You must be logged in to your account to access the editing options above. (If you visit this page without logging in, you will be viewing the page in "live" mode - as your visitors and friends see it.){/ts}</span>
</div>
{/if}
<div class="campaign">
  {crmRegion name="pcp-page-pcpinfo"}
    <div class="pcp-page-pcpinfo-intro">
      {if $image}
        <div class="pcp-image">{$image}</div>
      {/if}
      <div class="pcp-intro-text">{$pcp.intro_text|purify}</div>
      <div class="pcp-page-text">{$pcp.page_text}</div>
    </div>
    <div class="pcp-widgets">
      {if $validDate && $contributeURL}
        {* Show link to PCP contribution if configured for online contribution *}
        <div class="pcp-donate"><a href={$contributeURL} class="button contribute-button pcp-contribute-button"><span>{$contributionText}</span></a></div>
      {/if}
      {if $pcp.is_thermometer}
        <div class="thermometer-wrapper">
          <div class="pcp-amount-goal">{ts}Goal{/ts} <span class="goal-amount crmMoney">{$pcp.goal_amount|crmMoney:$currency}</span></div>
          <div class="thermometer-fill-wrapper">
            <div style="height: {$achieved}%;" class="thermometer-fill">
              <div class="thermometer-pointer"><span class="pcp-percent-raised">{$achieved}%</span> {ts}towards our goal{/ts}</div>
            </div>
          </div>
          <div class="pcp-amount-raised">
            <span class="raised-amount crmMoney">{$total|crmMoney:$currency}</span> {ts}raised{/ts}
          </div>
        </div>
      {/if}
      {if $pcp.is_honor_roll}
        <div class="honor-roll-wrapper">
          <div class="honor-roll-title">{ts}HONOR ROLL{/ts}</div>
          <div class="honor_roll">
            <marquee behavior="scroll" direction="up" id="pcp_roll"  scrolldelay="200"  height="200" bgcolor="#fafafa">
              {foreach from = $honor item = v}
                <div class="pcp_honor_roll_entry">
                  <div class="pcp-honor_roll-nickname">{$v.nickname}</div>
                  <div class="pcp-honor_roll-total_amount">{$v.total_amount}</div>
                  <div class="pcp-honor_roll-personal_note">{$v.personal_note}</div>
                </div>
              {/foreach}
            </marquee>
          </div>
          <div class="description">[<a href="#" onclick="roll_start_stop(); return false;" id="roll" title="{ts escape='htmlattribute'}Stop scrolling{/ts}">{ts}Stop{/ts}</a>]</div>
        </div>
      {/if}
      <div style="clear: both;"></div>
    </div>
    <div style="clear: both;"></div>
    {if $linkText}
      <div class="pcp-create-your-own">
        <a href={$linkTextUrl} class="pcp-create-link"><span>{$linkText}</span></a>
      </div>
    {/if}
  {/crmRegion}
</div>
{literal}
<script type="text/javascript">
  var start = true;
  function roll_start_stop() {
    if (start) {
      document.getElementById('roll').innerHTML = "{/literal}{ts escape='js'}Start{/ts}{literal}";
      document.getElementById('roll').title = "{/literal}{ts escape='js'}Start scrolling{/ts}{literal}";
      document.getElementById('pcp_roll').stop();
      start = false;
    }
    else {
      document.getElementById('roll').innerHTML = "{/literal}{ts escape='js'}Stop{/ts}{literal}";
      document.getElementById('roll').title = "{/literal}{ts escape='js'}Stop scrolling{/ts}{literal}";
      document.getElementById('pcp_roll').start();
      start = true;
    }
  }
</script>
{/literal}
