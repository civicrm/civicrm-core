{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Adds social networking buttons (Facebook, Bluesky, LinkedIn, email) to public pages (online contributions, event info) *}

<div class="crm-section crm-socialnetwork alert alert-success status crm-ok" role="alert">
    <h2>{ts}Help spread the word{/ts}</h2>
    <p>
    {if $title}
      {ts 1=$pageURL 2=$title|smarty:nodefaults|purify}Please help us and let your friends, colleagues and followers know about: <strong><a
          href="%1">%2</a></strong>{/ts}
    {else}
      {ts}Please help us and let your friends, colleagues and followers know about our page{/ts}.
    {/if}
    </p>
    <div class="socialnetwork-links">
    {if $emailMode eq true}
        <a href="https://bsky.app/intent/compose?text={$title|escape:'url'}%0A%0A{$url|escape:'url'}" target="_blank" rel="noopener noreferrer" class="btn btn-default bsky">Share on Bluesky</a>
        <a href="https://facebook.com/sharer/sharer.php?u={$url|escape:'url'}" target="_blank" class="btn btn-default facebook" role="button" title="{ts escape='htmlattribute'}Share{/ts}">{ts}Facebook{/ts}</a>
        <a href="https://www.linkedin.com/shareArticle?mini=true&amp;url={$url|escape:'url'}&amp;title={$title|escape:'url'}" target="_blank" rel="noopener" class="btn btn-default linkedin" title="{ts escape='htmlattribute'}Share{/ts}">{ts}LinkedIn{/ts}</a>
        <a href="https://share.joinmastodon.org/#text={$title|escape:'url'}%0A%0A{$url|escape:'url'}" target="_blank" rel="noopener noreferrer" class="btn btn-default mastodon">Share on Mastodon</a>
        <a href="https://twitter.com/share?url={$url|escape:'url'}&amp;text={$title|escape:'url'}" class="btn btn-default twitter" role="button" target="_blank" title="{ts escape='htmlattribute'}Tweet{/ts}">{ts}Twitter{/ts}</a>
    {else}
        <button onclick="window.open('https://bsky.app/intent/compose?text={$title|escape:'url'}%0A%0A{$url|escape:'url'}','_blank')" type="button" class="btn btn-default crm-button bsky" id="crm-bs" title="{ts escape='htmlattribute'}Share{/ts}"><i class="crm-i fa-bluesky" role="img" aria-hidden="true"></i>&nbsp;&nbsp;{ts}Bluesky{/ts}</button>
        <button onclick="window.open('https://facebook.com/sharer/sharer.php?u={$url|escape:'url'}','_blank')" type="button" class="btn btn-default crm-button facebook" role="button" id="crm-fb" title="{ts escape='htmlattribute'}Share{/ts}"><i class="crm-i fa-facebook" role="img" aria-hidden="true"></i>&nbsp;&nbsp;{ts}Facebook{/ts}</button>
        <button onclick="window.open('https://www.linkedin.com/shareArticle?mini=true&amp;url={$url|escape:'url'}&amp;title={$title|escape:'url'}','_blank')" type="button" rel="noopener" class="btn btn-default crm-button linkedin" id="crm-li" title="{ts escape='htmlattribute'}Share{/ts}"><i class="crm-i fa-linkedin" role="img" aria-hidden="true"></i>&nbsp;&nbsp;{ts}LinkedIn{/ts}</button>
        <button onclick="window.open('https://share.joinmastodon.org/#text={$title|escape:'url'}%0A%0A{$url|escape:'url'}','_blank')" type="button" class="btn btn-default crm-button mastodon" id="crm-mastodon" title="{ts escape='htmlattribute'}Share{/ts}"><i class="crm-i fa-mastodon" role="img" aria-hidden="true"></i>&nbsp;&nbsp;{ts}Mastodon{/ts}</button>
        <button onclick="window.open('https://twitter.com/intent/tweet?url={$url|escape:'url'}&amp;text={$title|escape:'url'}','_blank')" type="button" class="btn btn-default crm-button twitter" id="crm-tw" title="{ts escape='htmlattribute'}Tweet{/ts}"><i class="crm-i fa-twitter" role="img" aria-hidden="true"></i>&nbsp;&nbsp;{ts}Twitter{/ts}</button>
        <button onclick="window.open('mailto:?subject={$title|escape:'quotes'}&amp;body={$url|escape:'url'}','_self')" type="button" rel="noopener" class="btn btn-default crm-button email" id="crm-email"><i class="crm-i fa-envelope" title="{ts escape='htmlattribute'}Email{/ts}" role="img" aria-hidden="true"></i>&nbsp;&nbsp;{ts}Email{/ts}</button>
    {/if}
    </div>
    {if $pageURL}
    <p class="clear">
    <br/><strong>{ts}You can also share the below link in an email or on your website:{/ts}</strong><br />
    <a href="{$pageURL}">{$pageURL}</a></p>
    {else}
    <div class="clear"></div>
    {/if}
</div>
{literal}
<script type="text/javascript">
CRM.$(function($) {
  CRM.api4('OptionValue', 'get', {
    select: ["value"],
    where: [["option_group_id:name", "=", "social_network_sharing"], ["is_active", "=", true]],
    orderBy: {"weight":"ASC"},
    limit: 25
  }).then((optionValues) => {
    $('.crm-socialnetwork .socialnetwork-links a').hide();
    $('.crm-socialnetwork .socialnetwork-links button').hide();
    var linksEl = $('.crm-socialnetwork .socialnetwork-links').first();
    for (let i = 0; i < optionValues.length; i++) {
      var myClass = optionValues[i].value;
      $('.crm-socialnetwork .'+myClass).show().appendTo(linksEl).after(" ");
    }
    $('.crm-socialnetwork .email').show().appendTo(linksEl);
  }, (failure) => {
    console.log("no optionValues");
  });
});
</script>
{/literal}
