{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Adds social networking buttons (Facebook like, Twitter tweet, Google +1, LinkedIn) to public pages (online contributions, event info) *}

<div class="crm-section crm-socialnetwork help">
    <h3 class="nobackground">{ts}Help spread the word{/ts}</h3>
    <div class="description">
        {ts}Please help us and let your friends, colleagues and followers know about our page{/ts}{if $title}:
        <span class="bold"><a href="{$pageURL}">{$title}</a></span>
        {else}.{/if}
    </div>
    <div class="crm-fb-tweet-buttons">
        {if $emailMode eq true}
            {*use images for email*}
            <a href="https://twitter.com/share?url={$url|escape:'url'}&amp;text={$title}" id="crm_tweet">
                <img title="Twitter Tweet Button" src="{$config->userFrameworkResourceURL|replace:'https://':'http://'}/i/tweet.png" width="55px" height="20px"  alt="Tweet Button">
            </a>
            <a href="https://www.facebook.com/plugins/like.php?href={$url}" target="_blank">
                <img title="Facebook Like Button" src="{$config->userFrameworkResourceURL|replace:'https://':'http://'}/i/fblike.png" alt="Facebook Button" />
            </a>
        {else}
            {*use advanced buttons for pages*}
            <div class="label">
                <iframe allowtransparency="true" frameborder="0" scrolling="no"
                src="https://platform.twitter.com/widgets/tweet_button.html?text={$title}&amp;url={$url|escape:'url'}"
                style="width:100px; height:20px;">
                </iframe>
            </div>
            <div class="label" style="width:300px;">
                <iframe src="https://www.facebook.com/plugins/like.php?app_id=240719639306341&amp;href={$url|escape:'url'}&amp;send=false&amp;layout=standard&amp;show_faces=false&amp;action=like&amp;colorscheme=light" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:300px; height:30px;" allowTransparency="true">
                </iframe>
            </div>
            <div class="label">
              <script src="https://platform.linkedin.com/in.js" type="text/javascript"></script>
              <script type="IN/Share" data-url={$url} data-counter="right"></script>
            </div>
        {/if}
    </div>
    {if $pageURL}
      {if $emailMode neq true}
        <br/>
      {/if}
      <br/>
      <div class="clear"></div>
      <div>
        <span class="bold">{ts}You can also share the below link in an email or on your website.{/ts}</span>
        <br/>
        <a href="{$pageURL}">{$pageURL}</a>
      </div>
    {else}
      <div class="clear"></div>
    {/if}
</div>


