{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
*}// http://civicrm.org/licensing
{capture assign=menuMarkup}{strip}
  <ul id="civicrm-menu">
    {if call_user_func(array('CRM_Core_Permission','giveMeAllACLs'))}
      <li id="crm-qsearch" class="menumain">
        <form action="{crmURL p='civicrm/contact/search/advanced' h=0 }" name="search_block" id="id_search_block" method="post">
          <div id="quickSearch">
            <input type="text" class="form-text" id="sort_name_navigation" placeholder="{ts}Contacts{/ts}" name="sort_name" style="width: 6em;" />
            <input type="text" id="sort_contact_id" style="display: none" />
            <input type="hidden" name="hidden_location" value="1" />
            <input type="hidden" name="qfKey" value="" />
            <div style="height:1px; overflow:hidden;"><input type="submit" value="{ts}Go{/ts}" name="_qf_Advanced_refresh" class="crm-form-submit default" /></div>
          </div>
        </form>
        <ul>
          <li><label class="crm-quickSearchField"><input type="radio" data-tablename="cc" checked="checked" value="" name="quickSearchField">{if $includeEmail}{ts}Name/Email{/ts}{else}{ts}Name{/ts}{/if}</label></li>
          <li><label class="crm-quickSearchField"><input type="radio" data-tablename="cc" value="contact_id" name="quickSearchField">{ts}Contact ID{/ts}</label></li>
          <li><label class="crm-quickSearchField"><input type="radio" data-tablename="cc" value="external_identifier" name="quickSearchField">{ts}External ID{/ts}</label></li>
          <li><label class="crm-quickSearchField"><input type="radio" data-tablename="cc" value="first_name" name="quickSearchField">{ts}First Name{/ts}</label></li>
          <li><label class="crm-quickSearchField"><input type="radio" data-tablename="cc" value="last_name" name="quickSearchField">{ts}Last Name{/ts}</label></li>
          <li><label class="crm-quickSearchField"><input type="radio" data-tablename="eml" value="email" name="quickSearchField">{ts}Email{/ts}</label></li>
          <li><label class="crm-quickSearchField"><input type="radio" data-tablename="phe" value="phone_numeric" name="quickSearchField">{ts}Phone{/ts}</label></li>
          <li><label class="crm-quickSearchField"><input type="radio" data-tablename="sts" value="street_address" name="quickSearchField">{ts}Street Address{/ts}</label></li>
          <li><label class="crm-quickSearchField"><input type="radio" data-tablename="sts" value="city" name="quickSearchField">{ts}City{/ts}</label></li>
          <li><label class="crm-quickSearchField"><input type="radio" data-tablename="sts" value="postal_code" name="quickSearchField">{ts}Postal Code{/ts}</label></li>
          <li><label class="crm-quickSearchField"><input type="radio" data-tablename="cc" value="job_title" name="quickSearchField">{ts}Job Title{/ts}</label></li>
        </ul>
      </li>
    {/if}
    {$navigation}
  </ul>
{/strip}{/capture}// <script> Generated {$smarty.now|date_format:'%d %b %Y %H:%M:%S'}
{literal}
(function($) {
  var menuMarkup = {/literal}{$menuMarkup|@json_encode};
{if $config->userFramework neq 'Joomla'}{literal}
  $('body').append(menuMarkup);

  //Track Scrolling
  $(window).scroll(function () {
    var scroll = document.documentElement.scrollTop || document.body.scrollTop;
    $('#civicrm-menu').css({top: "scroll", position: "fixed", top: "0px"});
    $('div.sticky-header').css({top: "23px", position: "fixed"});
  });

  if ($('#edit-shortcuts').length > 0) {
    $('#civicrm-menu').css({'width': '97%'});
  }
{/literal}{else}{* Special menu hacks for Joomla *}{literal}
  // below div is present in older version of joomla 2.5.x
  var elementExists = $('div#toolbar-box div.m').length;
  if (elementExists > 0) {
    $('div#toolbar-box div.m').html(menuMarkup);
  }
  else {
    $("#crm-nav-menu-container").html(menuMarkup).css({'padding-bottom': '10px'});
  }
{/literal}{/if}{literal}
  // CRM-15493 get the current qfKey
  $("input[name=qfKey]", "#quickSearch").val($('#civicrm-navigation-menu').data('qfkey'));

$('#civicrm-menu').ready(function() {
  $('#root-menu-div .outerbox').css({'margin-top': '6px'});
  $('#root-menu-div .menu-ul li').css({'padding-bottom': '2px', 'margin-top': '2px'});
  $('img.menu-item-arrow').css({top: '4px'});
  $("#civicrm-menu >li").each(function(i){
    $(this).attr("tabIndex",i+2);
  });

  $('#sort_name_navigation')
    .autocomplete({
      source: function(request, response) {
        var
          option = $('input[name=quickSearchField]:checked'),
          params = {
            name: request.term,
            field_name: option.val(),
            table_name: option.attr("data-tablename")
          };
        CRM.api3('contact', 'getquick', params).done(function(result) {
          var ret = [];
          if (result.values) {
            $.each(result.values, function(k, v) {
              ret.push({value: v.id, label: v.data});
            })
          }
          response(ret);
        })
      },
      focus: function (event, ui) {
        return false;
      },
      select: function (event, ui) {
        document.location = CRM.url('civicrm/contact/view', {reset: 1, cid: ui.item.value});
        return false;
      },
      create: function() {
        // Place menu in front
        $(this).autocomplete('widget').css('z-index', $('#civicrm-menu').css('z-index'));
      }
    })
    .keydown(function() {
      $.Menu.closeAll();
    })
    .on('focus', function() {
      if ($(this).attr('style').indexOf('14em') < 0) {
        $(this).animate({width: '14em'});
      }
    })
    .on('blur', function() {
      // Shrink if no input and menu is not open
      if (!$(this).val().length && $(this).attr('style').indexOf('6em') < 0 && !$('.crm-quickSearchField:visible', '#root-menu-div').length) {
        $(this).animate({width: '6em'});
      }
    });
  $('.crm-hidemenu').click(function(e) {
    $('#civicrm-menu').slideUp();
    if ($('#crm-notification-container').length) {
      var alert = CRM.alert({/literal}'<a href="#" id="crm-restore-menu" style="text-align: center; margin-top: -8px;">{ts escape='js'}Restore CiviCRM Menu{/ts}</a>'{literal}, '', 'none', {expires: 10000});
      $('#crm-restore-menu')
        .button({icons: {primary: 'fa-undo'}})
        .click(function(e) {
          e.preventDefault();
          alert.close();
          $('#civicrm-menu').slideDown();
        })
        .parent().css('text-align', 'center').find('.ui-button-text').css({'padding-top': '4px', 'padding-bottom': '4px'})
      ;
    }
    e.preventDefault();
  });
  $('.crm-quickSearchField').click(function() {
    var label = $(this).text();
    var value = $('input', this).val();
    // These fields are not supported by advanced search
    if (value === 'first_name' || value === 'last_name') {
      value = 'sort_name';
    }
    $('#sort_name_navigation').attr({name: value, placeholder: label}).focus();
  });
  // redirect to view page if there is only one contact
  $('#id_search_block').on('submit', function() {
    var $menu = $('#sort_name_navigation').autocomplete('widget');
    if ($('li.ui-menu-item', $menu).length === 1) {
      var cid = $('li.ui-menu-item', $menu).data('ui-autocomplete-item').value;
      document.location = CRM.url('civicrm/contact/view', {reset: 1, cid: cid});
      return false;
    }
  });
  // Close menu after selecting an item
  $('#root-menu-div').on('click', 'a', $.Menu.closeAll);
});
$('#civicrm-menu').menuBar({arrowSrc: CRM.config.resourceBase + 'packages/jquery/css/images/arrow.png'});
})(CRM.$);{/literal}
