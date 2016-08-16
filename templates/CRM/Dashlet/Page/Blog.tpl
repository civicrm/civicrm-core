{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
  #civicrm-blog-feed .collapsed .crm-accordion-header {
    text-overflow: ellipsis;
    text-wrap: none;
    white-space: nowrap;
    overflow: hidden;
  }
  #civicrm-blog-feed .crm-blog-preview {
    color: #8d8d8d;
    display: none;
  }
  #civicrm-blog-feed .collapsed .crm-blog-preview {
    display: inline;
  }
</style>
{/literal}
<div id="civicrm-blog-feed">
{foreach from=$blog item=article}
  <div class="crm-accordion-wrapper collapsed">
    <div class="crm-accordion-header">
      <span class="crm-blog-title">{$article.title}</span>
      <span class="crm-blog-preview"> - {$article.description|strip_tags|substr:0:100}…</span>
    </div>
    <div class="crm-accordion-body">
      <div>{$article.description}</div>
      <div><a target="_blank" href="{$article.link}" title="{$article.title}"><i class="crm-i fa-external-link"></i> {ts}read more{/ts}…</a></div>
    </div>
  </div>
{/foreach}
</div>
{/strip}
