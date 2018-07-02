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
{strip}{literal}
<style type="text/css">
  #civicrm-news-feed {
    border: 0 none;
  }
  #civicrm-news-feed .collapsed .crm-accordion-header {
    text-overflow: ellipsis;
    text-wrap: none;
    white-space: nowrap;
    overflow: hidden;
  }
  #civicrm-news-feed .crm-news-feed-item-preview {
    color: #8d8d8d;
    display: none;
  }
  #civicrm-news-feed .collapsed .crm-news-feed-item-preview {
    display: inline;
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
      <div class="crm-accordion-wrapper collapsed">
        <div class="crm-accordion-header">
          <span class="crm-news-feed-item-title">{$article.title}</span>
          <span class="crm-news-feed-item-preview"> - {if function_exists('mb_substr')}{$article.description|strip_tags|mb_substr:0:100}{else}{$article.description|strip_tags}{/if}</span>
        </div>
        <div class="crm-accordion-body">
          <div>{$article.description}</div>
          <p class="crm-news-feed-item-link"><a target="_blank" href="{$article.link}" title="{$article.title|escape}"><i class="crm-i fa-external-link"></i> {ts}read more{/ts}…</a></p>
        </div>
      </div>
    {/foreach}
    </div>
  {/foreach}
  {if !$feeds}
    <div class="messages status no-popup">
      <div class="icon inform-icon"></div>
      {ts}Sorry but we are not able to provide this at the moment.{/ts}
    </div>
  {/if}
</div>
  
</div>
{literal}<script type="text/javascript">
  (function($, _) {
    $(function() {
      $('#civicrm-news-feed').tabs();
      if (window.localStorage) {
        var opened = localStorage.newsFeed ? JSON.parse(localStorage.newsFeed) : {};
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
                $('.crm-news-feed-item-title', this).css('font-weight', 'bold');
                ++count;
                $(this).one('crmAccordion:open', function () {
                  $('.crm-news-feed-item-title', this).css('font-weight', '');
                  $('em', $tab).text(--count);
                  if (!count) {
                    $('em', $tab).remove();
                  }
                  opened[key].push(itemKey);
                  localStorage.newsFeed = JSON.stringify(opened);
                });
              }
            });
            if (count) {
              $tab.html($tab.text() + ' <em>' + count + '</em>');
            }
            // Remove items from localstorage that are no longer in the current feed
            $.each(opened[key], function(i, itemKey) {
              if (!$('a[href="' + itemKey + '"]', $content).length) {
                opened[key][i] = null;
              }
            });
            _.remove(opened[key], function(n) {return !n});
          }
        });
      }
    });
  })(CRM.$, CRM._);
</script>{/literal}
{/strip}
