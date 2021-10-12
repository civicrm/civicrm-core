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
    <p>{ts}Please help us and let your friends, colleagues and followers know about our page{/ts}{if $title}: <strong><a href="{$pageURL}">{$title}</a></strong>{else}.{/if}</p>
    {if $emailMode eq true}
        <a href="https://twitter.com/share?url={$url|escape:'url'}&amp;text={$title|escape:'url'}" class="btn btn-default" role="button" target="_blank">{ts}Tweet{/ts}</a>
        <a href="https://facebook.com/sharer/sharer.php?u={$url|escape:'url'}" target="_blank" class="btn btn-default" role="button">{ts}Share on Facebook{/ts}</a>
        <a href="https://www.linkedin.com/shareArticle?mini=true&amp;url={$url|escape:'url'}&amp;title={$title|escape:'url'}" target="_blank" rel="noopener" class="btn btn-default">{ts}Share on LinkedIn{/ts}</a>
    {else}
        <button onclick="window.open('https://twitter.com/intent/tweet?url={$url|escape:'url'}&amp;text={$title|escape:'url'}','_blank')" type="button" class="btn btn-default crm-button" id="crm-tw"><i aria-hidden="true" class="crm-i fa-twitter"></i>&nbsp;&nbsp;{ts}Tweet{/ts}</button>
        <button onclick="window.open('https://facebook.com/sharer/sharer.php?u={$url|escape:'url'}','_blank')" type="button" class="btn btn-default crm-button" role="button" id="crm-fb"><i aria-hidden="true" class="crm-i fa-facebook"></i>&nbsp;&nbsp;{ts}Share on Facebook{/ts}</button>
        <button onclick="window.open('https://www.linkedin.com/shareArticle?mini=true&amp;url={$url|escape:'url'}&amp;title={$title|escape:'url'}','_blank')" type="button" rel="noopener" class="btn btn-default crm-button" id="crm-li"><i aria-hidden="true" class="crm-i fa-linkedin"></i>&nbsp;&nbsp;{ts}Share on LinkedIn{/ts}</button>
        <button onclick="window.open('mailto:?subject={$title|escape:'quotes'}&amp;body={$url|escape:'url'}','_self')" type="button" rel="noopener" class="btn btn-default crm-button" id="crm-email"><i aria-hidden="true" class="crm-i fa-envelope"></i>&nbsp;&nbsp;{ts}Email{/ts}</button>
    {/if}
    {if $pageURL}
    <p class="clear">
    <br/><strong>{ts}You can also share the below link in an email or on your website:{/ts}</strong><br />
    <a href="{$pageURL}">{$pageURL}</a></p>
    {else}
    <div class="clear"></div>
    {/if}
</div>
