{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Adds social networking buttons (Facebook, Twitter, LinkedIn, email) to public pages (online contributions, event info) *}

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
    {if $emailMode eq true}
        <a href="https://twitter.com/share?url={$url|escape:'url'}&amp;text={$title|escape:'url'}" class="btn btn-default" role="button" target="_blank" title="{ts escape='htmlattribute'}Tweet{/ts}">{ts}Twitter{/ts}</a>
        <a href="https://facebook.com/sharer/sharer.php?u={$url|escape:'url'}" target="_blank" class="btn btn-default" role="button" title="{ts escape='htmlattribute'}Share{/ts}">{ts}Facebook{/ts}</a>
        <a href="https://www.linkedin.com/shareArticle?mini=true&amp;url={$url|escape:'url'}&amp;title={$title|escape:'url'}" target="_blank" rel="noopener" class="btn btn-default" title="{ts escape='htmlattribute'}Share{/ts}">{ts}LinkedIn{/ts}</a>
    {else}
        <button onclick="window.open('https://twitter.com/intent/tweet?url={$url|escape:'url'}&amp;text={$title|escape:'url'}','_blank')" type="button" class="btn btn-default crm-button" id="crm-tw" title="{ts escape='htmlattribute'}Tweet{/ts}"><i class="crm-i fa-twitter" role="img" aria-hidden="true"></i>&nbsp;&nbsp;{ts}Twitter{/ts}</button>
        <button onclick="window.open('https://facebook.com/sharer/sharer.php?u={$url|escape:'url'}','_blank')" type="button" class="btn btn-default crm-button" role="button" id="crm-fb" title="{ts escape='htmlattribute'}Share{/ts}"><i class="crm-i fa-facebook" role="img" aria-hidden="true"></i>&nbsp;&nbsp;{ts}Facebook{/ts}</button>
        <button onclick="window.open('https://www.linkedin.com/shareArticle?mini=true&amp;url={$url|escape:'url'}&amp;title={$title|escape:'url'}','_blank')" type="button" rel="noopener" class="btn btn-default crm-button" id="crm-li" title="{ts escape='htmlattribute'}Share{/ts}"><i class="crm-i fa-linkedin" role="img" aria-hidden="true"></i>&nbsp;&nbsp;{ts}LinkedIn{/ts}</button>
        <button onclick="window.open('mailto:?subject={$title|escape:'quotes'}&amp;body={$url|escape:'url'}','_self')" type="button" rel="noopener" class="btn btn-default crm-button" id="crm-email"><i class="crm-i fa-envelope" title="{ts escape='htmlattribute'}Email{/ts}" role="img" aria-hidden="true"></i>&nbsp;&nbsp;{ts}Email{/ts}</button>
    {/if}
    {if $pageURL}
    <p class="clear">
    <br/><strong>{ts}You can also share the below link in an email or on your website:{/ts}</strong><br />
    <a href="{$pageURL}">{$pageURL}</a></p>
    {else}
    <div class="clear"></div>
    {/if}
</div>
