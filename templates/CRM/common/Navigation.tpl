{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
      <li id="crm-qsearch" class="menumain crm-link-home">
        <form action="{crmURL p='civicrm/contact/search/advanced' h=0 }" name="search_block" id="id_search_block" method="post">
          <div id="quickSearch">
            <input type="text" class="form-text" id="sort_name_navigation" placeholder="{ts}Find Contacts{/ts}" name="sort_name" style="width: 12em;" />
            <input type="text" id="sort_contact_id" style="display: none" />
            <input type="hidden" name="hidden_location" value="1" />
            <input type="hidden" name="qfKey" value="{crmKey name='CRM_Contact_Controller_Search' addSequence=1}" />
            <div style="height:1px; overflow:hidden;"><input type="submit" value="{ts}Go{/ts}" name="_qf_Advanced_refresh" class="form-submit default" /></div>
          </div>
        </form>
        <ul>
          <li><label class="crm-quickSearchField"><input type="radio" data-tablename="cc" checked="checked" value="" name="quickSearchField">{ts}Name/Email{/ts}</label></li>
          <li><label class="crm-quickSearchField"><input type="radio" data-tablename="cc" value="contact_id" name="quickSearchField">{ts}CiviCRM ID{/ts}</label></li>
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
{/strip}{/capture}// <script> Generated {$timeGenerated}
{literal}
(function($) {
  var menuMarkup = {/literal}{$menuMarkup|@json_encode};
{if $config->userFramework neq 'Joomla'}{literal}
  $('body').prepend(menuMarkup);

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
$('#civicrm-menu').ready(function() {
  $('#root-menu-div .outerbox').css({'margin-top': '6px'});
  $('#root-menu-div .menu-ul li').css({'padding-bottom': '2px', 'margin-top': '2px'});
  $('img.menu-item-arrow').css({top: '4px'});
  $("#civicrm-menu >li").each(function(i){
    $(this).attr("tabIndex",i+2);
  });

  $('#sort_name_navigation')
    .crmAutocomplete({
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
      select: function (event, ui) {
        document.location = CRM.url('civicrm/contact/view', {reset: 1, cid: ui.item.value});
        return false;
      },
      create: function() {
        // Place menu in front
        $(this).crmAutocomplete('widget').css('z-index', (1 + $('#civicrm-menu').css('z-index')));
      }
    })
    .keydown(function() {
      $.Menu.closeAll();
    });
  $('.crm-hidemenu').click(function() {
    $.Menu.closeAll();
    $('#civicrm-menu').slideUp();
    var alert = CRM.alert({/literal}'<a href="#" id="crm-restore-menu">{ts escape='js'}Restore Menu{/ts}</a>', "{ts escape='js'}CiviCRM Menu Hidden{/ts}"{literal});
    $('#crm-notification-container')
      .off('.hideMenu')
      .on('click.hideMenu', '#crm-restore-menu', function() {
        alert.close();
        $('#civicrm-menu').slideDown();
        return false;
      });
    return false;
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
  // check if there is only one contact and redirect to view page
  $('#id_search_block').on('submit', function() {
    var contactId, sortValue = $('#sort_name_navigation').val();
    if (sortValue && $('#sort_name_navigation').attr('name') == 'sort_name') {
      {/literal}{*
      // FIXME: async:false == bad,
      // we should just check the autocomplete results instead of firing a new request
      // when we fix this, the civicrm/ajax/contact server-side callback can be removed as well
      // also that would fix the fact that this only works with sort_name search
      // (and we can remove the above conditional)
      *}{literal}
      var dataUrl = {/literal}"{crmURL p='civicrm/ajax/contact' h=0 q='name='}"{literal} + sortValue;
      contactId = $.ajax({
        url: dataUrl,
        async: false
      }).responseText;
    }
    if (contactId && !isNaN(parseInt(contactId))) {
      var url = {/literal}"{crmURL p='civicrm/contact/view' h=0 q='reset=1&cid='}"{literal} + contactId;
      this.action = url;
    }
  });
});
$('#civicrm-menu').menuBar({arrowSrc: CRM.config.resourceBase + 'packages/jquery/css/images/arrow.png'});
})(cj);{/literal}
