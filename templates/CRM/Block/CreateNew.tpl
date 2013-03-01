{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
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
<div class="block-civicrm crm-container">
<div id="crm-create-new-wrapper">
  <a id="crm-create-new-link" class="button" href="#"><span><div class="icon dropdown-icon"></div>{ts}Create New{/ts}</span></a>
    <div id="crm-create-new-list" class="ac_results">
      <div class="crm-create-new-list-inner">
      <ul>
      {foreach from=$shortCuts item=short}
            <li><a href="{$short.url}" class="crm-{$short.ref}">{$short.title}</a></li>
          {/foreach}
      </ul>
      </div>
    </div>
  </div>
</div>
<div class='clear'></div>
{literal}
<script>

cj('body').click(function() {
     cj('#crm-create-new-list').hide();
     });

   cj('#crm-create-new-list').click(function(event){
       event.stopPropagation();
     });

cj('#crm-create-new-list li').hover(
  function(){ cj(this).addClass('ac_over');},
  function(){ cj(this).removeClass('ac_over');}
  );

cj('#crm-create-new-link').click(function(event) {
  cj('#crm-create-new-list').toggle();
  event.stopPropagation();
  return false;
  });

</script>

{/literal}
