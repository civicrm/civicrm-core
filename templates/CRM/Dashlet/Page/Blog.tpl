{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{strip}{literal}
<style type="text/css">
  #civicrm-news-feed {
    border: 0 none;
    font-family: inherit; // Stops JQueryUI's attempt to make this section Verdana
  }
  #civicrm-news-feed .crm-news-feed-unread .crm-news-feed-item-title {
    font-weight: bold;
  }
  #civicrm-news-feed details:not([open]) summary {
    text-overflow: ellipsis;
    text-wrap: none;
    white-space: nowrap;
    overflow: hidden;
  }
  #civicrm-news-feed .crm-news-feed-item-preview {
    display: inline;
    color: #8d8d8d;
  }
  #civicrm-news-feed details[open] .crm-news-feed-item-preview {
    display: none;
  }
  #civicrm-news-feed .crm-news-feed-item-link {
    margin-bottom: 0;
  }
</style>
{/literal}
<div id="civicrm-news-feed">
  <ul>
    {foreach from=$feeds item="channel"}
      <li class="ui-corner-all crm-tab-button" title="{$channel.description|escape}">
        <a href="#civicrm-news-feed-{$channel.name}">{$channel.title}</a>
      </li>
    {/foreach}
  </ul>

  {foreach from=$feeds item="channel"}
    <div id="civicrm-news-feed-{$channel.name}">
    {foreach from=$channel.items item=article}
      <details class="crm-accordion-bold">
        <summary>
          <span class="crm-news-feed-item-title">{$article.title|smarty:nodefaults|purify}</span>
          <span class="crm-news-feed-item-preview"> - {$article.description|smarty:nodefaults|strip_tags|mb_substr:0:150}</span>
        </summary>
        <div class="crm-accordion-body">
          <div>{$article.description|smarty:nodefaults|purify}</div>
          <p class="crm-news-feed-item-link"><a target="_blank" href="{$article.link|smarty:nodefaults|purify}" title="{$article.title|smarty:nodefaults|escape}"><i class="crm-i fa-external-link" aria-hidden="true"></i> {ts}read more{/ts}…</a></p>
        </div>
      </details>
    {/foreach}
    </div>
  {/foreach}
  {if !$feeds}
    <div class="messages status no-popup">
      {icon icon="fa-info-circle"}{/icon}
      {ts}Sorry but we are not able to provide this at the moment.{/ts}
    </div>
  {/if}
</div>

</div>
{literal}<script type="text/javascript">
  (function($, _) {
    $(function() {
      $('#civicrm-news-feed').tabs();
      var opened = CRM.cache.get('newsFeed', {});
      $('#civicrm-news-feed ul.ui-tabs-nav a').each(function() {
        var
          $tab = $(this),
          href = $tab.attr('href'),
          $content = $(href),
          $items = $('.crm-accordion-wrapper', $content),
          key = href.substring(19),
          count = 0;
        if (!opened[key]) opened[key] = [];
        if ($items.length) {
          $items.each(function () {
            var itemKey = $('.crm-news-feed-item-link a', this).attr('href');
            if ($.inArray(itemKey, opened[key]) < 0) {
              $(this).addClass('crm-news-feed-unread');
              ++count;
              $(this).one('click', function () {
                $(this).removeClass('crm-news-feed-unread');
                $('em', $tab).text(--count || '');
                opened[key].push(itemKey);
                CRM.cache.set('newsFeed', opened);
              });
            }
          });
          if (count) {
            $tab.html($tab.text() + ' <em>' + count + '</em>');
          }
          // Remove items from localstorage that are no longer in the current feed
          _.remove(opened[key], function(itemKey) {
            return !$('a[href="' + itemKey + '"]', $content).length;
          });
        }
      });
    });
  })(CRM.$, CRM._);
</script>{/literal}
{/strip}
