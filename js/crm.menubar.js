// https://civicrm.org/licensing
(function($, _) {
  "use strict";
  var templates, initialized,
    ENTER_KEY = 13,
    SPACE_KEY = 32;
  CRM.menubar = _.extend({
    data: null,
    settings: {collapsibleBehavior: 'accordion'},
    position: 'over-cms-menu',
    attachTo: (CRM.menubar && CRM.menubar.position === 'above-crm-container') ? '#crm-container' : 'body',
    initialize: function() {
      var cache = CRM.cache.get('menubar');
      if (cache && cache.code === CRM.menubar.cacheCode && cache.locale === CRM.config.locale && cache.cid === CRM.config.cid && localStorage.civiMenubar) {
        CRM.menubar.data = cache.data;
        insert(localStorage.civiMenubar);
      } else {
        $.getJSON(CRM.url('civicrm/ajax/navmenu', {code: CRM.menubar.cacheCode, locale: CRM.config.locale, cid: CRM.config.cid}))
          .done(function(data) {
            var markup = getTpl('tree')(data);
            CRM.cache.set('menubar', {code: CRM.menubar.cacheCode, locale: CRM.config.locale, cid: CRM.config.cid, data: data});
            CRM.menubar.data = data;
            localStorage.setItem('civiMenubar', markup);
            insert(markup);
          });
      }

      // Wait for crm-container present on the page as it's faster than document.ready
      function insert(markup) {
        if ($('#crm-container').length) {
          render(markup);
        } else {
          new MutationObserver(function(mutations, observer) {
            _.each(mutations, function(mutant) {
              _.each(mutant.addedNodes, function(node) {
                if ($(node).is('#crm-container')) {
                  render(markup);
                  observer.disconnect();
                }
              });
            });
          }).observe(document, {childList: true, subtree: true});
        }
      }

      function render(markup) {
        var position = CRM.menubar.attachTo === 'body' ? 'beforeend' : 'afterbegin';
        $(CRM.menubar.attachTo)[0].insertAdjacentHTML(position, markup);
        CRM.menubar.initializePosition();
        $('#civicrm-menu').trigger('crmLoad');
        $(document).ready(function() {
          handleResize();
          $('#civicrm-menu')
            .on('click', 'a[href="#"]', function() {
              // For empty links - keep the menu open and don't jump the page anchor
              return false;
            })
            .on('click', 'a:not([href^="#"])', function(e) {
              if (e.ctrlKey || e.altKey || e.shiftKey || e.metaKey) {
                // Prevent menu closing when link is clicked with a keyboard modifier.
                e.stopPropagation();
              }
            })
            .on('dragstart', function() {
              // Stop user from accidentally dragging menu links
              // This was added because a user noticed they could drag the civi icon into the quicksearch box.
              return false;
            })
            .on('click', 'a[href="#hidemenu"]', function(e) {
              e.preventDefault();
              CRM.menubar.hide(250, true);
            })
            .on('keyup', 'a', function(e) {
              // Simulate a click when spacebar key is pressed
              if (e.which == SPACE_KEY) {
                $(e.currentTarget)[0].click();
              }
            })
            .on('show.smapi', function(e, menu) {
              // Focus menu when opened with an accesskey
              $(menu).siblings('a[accesskey]:not(:hover)').focus();
            })
            .smartmenus(CRM.menubar.settings);
          initialized = true;
          CRM.menubar.initializeResponsive();
          CRM.menubar.initializeSearch();
        });
      }
    },
    destroy: function() {
      $.SmartMenus.destroy();
      $('#civicrm-menu-nav').remove();
      initialized = false;
      $('body[class]').attr('class', function(i, c) {
        return c.replace(/(^|\s)crm-menubar-\S+/g, '');
      });
    },
    show: function(speed) {
      if (typeof speed === 'number') {
        $('#civicrm-menu').slideDown(speed, function() {
          $(this).css('display', '');
        });
      }
      $('body')
        .removeClass('crm-menubar-hidden')
        .addClass('crm-menubar-visible');
    },
    hide: function(speed, showMessage) {
      if (typeof speed === 'number') {
        $('#civicrm-menu').slideUp(speed, function() {
          $(this).css('display', '');
        });
      }
      $('body')
        .addClass('crm-menubar-hidden')
        .removeClass('crm-menubar-visible');
      if (showMessage === true && $('#crm-notification-container').length && initialized) {
        var alert = CRM.alert('<a href="#" id="crm-restore-menu" >' + _.escape(ts('Restore CiviCRM Menu')) + '</a>', ts('Menu hidden'), 'none', {expires: 10000});
        $('#crm-restore-menu')
          .click(function(e) {
            e.preventDefault();
            alert.close();
            CRM.menubar.show(speed);
          });
      }
    },
    open: function(itemName) {
      var $item = $('li[data-name="' + itemName + '"] > a', '#civicrm-menu');
      if ($item.length) {
        $('#civicrm-menu').smartmenus('itemActivate', $item);
        $item[0].focus();
      }
    },
    close: $.SmartMenus.hideAll,
    isOpen: function(itemName) {
      if (itemName) {
        return !!$('li[data-name="' + itemName + '"] > ul[aria-expanded="true"]', '#civicrm-menu').length;
      }
      return !!$('ul[aria-expanded="true"]', '#civicrm-menu').length;
    },
    spin: function(spin) {
      $('.crm-logo-sm', '#civicrm-menu').toggleClass('fa-spin', spin);
    },
    getItem: function(itemName) {
      return traverse(CRM.menubar.data.menu, itemName, 'get');
    },
    addItems: function(position, targetName, items) {
      var list, container, $ul;
      if (position === 'before' || position === 'after') {
        if (!targetName) {
          throw 'Cannot add sibling of main menu';
        }
        list = traverse(CRM.menubar.data.menu, targetName, 'parent');
        if (!list) {
          throw targetName + ' not found';
        }
        var offset = position === 'before' ? 0 : 1;
        position = offset + _.findIndex(list, {name: targetName});
        $ul = $('li[data-name="' + targetName + '"]', '#civicrm-menu').closest('ul');
      } else if (targetName) {
        container = traverse(CRM.menubar.data.menu, targetName, 'get');
        if (!container) {
          throw targetName + ' not found';
        }
        container.child = container.child || [];
        list = container.child;
        var $target = $('li[data-name="' + targetName + '"]', '#civicrm-menu');
        if (!$target.children('ul').length) {
          $target.append('<ul>');
        }
        $ul = $target.children('ul').first();
      } else {
        list = CRM.menubar.data.menu;
      }
      if (position < 0) {
        position = list.length + 1 + position;
      }
      if (position >= list.length) {
        list.push.apply(list, items);
        position = list.length - 1;
      } else {
        list.splice.apply(list, [position, 0].concat(items));
      }
      if (targetName && !$ul.is('#civicrm-menu')) {
        $ul.html(getTpl('branch')({items: list, branchTpl: getTpl('branch')}));
      } else {
        $('#civicrm-menu > li').eq(position).after(getTpl('branch')({items: items, branchTpl: getTpl('branch')}));
      }
      CRM.menubar.refresh();
    },
    removeItem: function(itemName) {
      var item = traverse(CRM.menubar.data.menu, itemName, 'delete');
      if (item) {
        $('li[data-name="' + itemName + '"]', '#civicrm-menu').remove();
        CRM.menubar.refresh();
      }
      return item;
    },
    updateItem: function(item) {
      if (!item.name) {
        throw 'No name passed to CRM.menubar.updateItem';
      }
      var menuItem = CRM.menubar.getItem(item.name);
      if (!menuItem) {
        throw item.name + ' not found';
      }
      _.extend(menuItem, item);
      $('li[data-name="' + item.name + '"]', '#civicrm-menu').replaceWith(getTpl('branch')({items: [menuItem], branchTpl: getTpl('branch')}));
      CRM.menubar.refresh();
    },
    refresh: function() {
      if (initialized) {
        $('#civicrm-menu').smartmenus('refresh');
        handleResize();
      }
    },
    togglePosition: function(persist) {
      $('body').toggleClass('crm-menubar-over-cms-menu crm-menubar-below-cms-menu');
      CRM.menubar.position = CRM.menubar.position === 'over-cms-menu' ? 'below-cms-menu' : 'over-cms-menu';
      handleResize();
      if (persist !== false) {
        CRM.cache.set('menubarPosition', CRM.menubar.position);
      }
    },
    initializePosition: function() {
      if (CRM.menubar.position === 'over-cms-menu' || CRM.menubar.position === 'below-cms-menu') {
        $('#civicrm-menu')
          .on('click', 'a[href="#toggle-position"]', function(e) {
            e.preventDefault();
            CRM.menubar.togglePosition();
          })
          .append('<li id="crm-menubar-toggle-position"><a href="#toggle-position" title="' + ts('Adjust menu position') + '"><i class="crm-i fa-arrow-up"></i></a>');
        CRM.menubar.position = CRM.cache.get('menubarPosition', CRM.menubar.position);
      }
      $('body').addClass('crm-menubar-visible crm-menubar-' + CRM.menubar.position);
    },
    initializeResponsive: function() {
      var $mainMenuState = $('#crm-menubar-state');
      // hide mobile menu beforeunload
      $(window).on('beforeunload unload', function() {
        CRM.menubar.spin(true);
        if ($mainMenuState[0].checked) {
          $mainMenuState[0].click();
        }
      })
        .on('resize', function() {
          if ($(window).width() >= 768 && $mainMenuState[0].checked) {
            $mainMenuState[0].click();
          }
          handleResize();
        });
      $mainMenuState.click(function() {
        // Use absolute position instead of fixed when open to allow scrolling menu
        var open = $(this).is(':checked');
        if (open) {
          window.scroll({top: 0});
        }
        $('#civicrm-menu-nav')
          .css('position', open ? 'absolute' : '')
          .parentsUntil('body')
          .css('position', open ? 'static' : '');
      });
    },
    initializeSearch: function() {
      $('input[name=qfKey]', '#crm-qsearch').attr('value', CRM.menubar.qfKey);
      $('#crm-qsearch-input')
        .autocomplete({
          source: function(request, response) {
            //start spinning the civi logo
            CRM.menubar.spin(true);
            var
              option = $('input[name=quickSearchField]:checked'),
              params = {
                name: request.term,
                field_name: option.val()
              };
            CRM.api3('contact', 'getquick', params).done(function(result) {
              var ret = [];
              if (result.values.length > 0) {
                $('#crm-qsearch-input').autocomplete('widget').menu('option', 'disabled', false);
                $.each(result.values, function(k, v) {
                  ret.push({value: v.id, label: v.data});
                });
              } else {
                $('#crm-qsearch-input').autocomplete('widget').menu('option', 'disabled', true);
                var label = option.closest('label').text();
                var msg = ts('%1 not found.', {1: label});
                // Remind user they are not searching by contact name (unless they enter a number)
                if (params.field_name !== 'sort_name' && !(/[\d].*/.test(params.name))) {
                  msg += ' ' + ts('Did you mean to search by Name/Email instead?');
                }
                ret.push({value: '0', label: msg});
              }
              response(ret);
              //stop spinning the civi logo
              CRM.menubar.spin(false);
              CRM.menubar.close();
            });
          },
          focus: function (event, ui) {
            return false;
          },
          select: function (event, ui) {
            if (ui.item.value > 0) {
              document.location = CRM.url('civicrm/contact/view', {reset: 1, cid: ui.item.value});
            }
            return false;
          },
          create: function() {
            $(this).autocomplete('widget').addClass('crm-quickSearch-results');
          }
        })
        .on('keyup change', function() {
          $(this).toggleClass('has-user-input', !!$(this).val());
        })
        .keyup(function(e) {
          CRM.menubar.close();
          if (e.which === ENTER_KEY) {
            if (!$(this).val()) {
              CRM.menubar.open('QuickSearch');
            }
          }
        });
      $('#crm-qsearch > a').keyup(function(e) {
        if ($(e.target).is(this)) {
          $('#crm-qsearch-input').focus();
          CRM.menubar.close();
        }
      });
      $('#crm-qsearch form[name=search_block]').on('submit', function() {
        if (!$('#crm-qsearch-input').val()) {
          return false;
        }
        var $menu = $('#crm-qsearch-input').autocomplete('widget');
        if ($('li.ui-menu-item', $menu).length === 1) {
          var cid = $('li.ui-menu-item', $menu).data('ui-autocomplete-item').value;
          if (cid > 0) {
            document.location = CRM.url('civicrm/contact/view', {reset: 1, cid: cid});
            return false;
          }
        }
      });
      $('#civicrm-menu').on('show.smapi', function(e, menu) {
        if ($(menu).parent().attr('data-name') === 'QuickSearch') {
          $('#crm-qsearch-input').focus();
        }
      });
      function setQuickSearchValue() {
        var $selection = $('.crm-quickSearchField input:checked'),
          label = $selection.parent().text(),
          value = $selection.val();
        // These fields are not supported by advanced search
        if (!value || value === 'first_name' || value === 'last_name') {
          value = 'sort_name';
        }
        $('#crm-qsearch-input').attr({name: value, placeholder: '\uf002 ' + label});
      }
      $('.crm-quickSearchField').click(function() {
        var input = $('input', this);
        // Wait for event - its default was prevented by our link handler which interferes with checking the radio input
        window.setTimeout(function() {
          input.prop('checked', true);
          CRM.cache.set('quickSearchField', input.val());
          setQuickSearchValue();
          $('#crm-qsearch-input').focus().autocomplete("search");
        }, 1);
      });
      $('.crm-quickSearchField input[value="' + CRM.cache.get('quickSearchField', 'sort_name') + '"]').prop('checked', true);
      setQuickSearchValue();
      $('#civicrm-menu').on('activate.smapi', function(e, item) {
        return !$('ul.crm-quickSearch-results').is(':visible:not(.ui-state-disabled)');
      });
    },
    treeTpl:
      '<nav id="civicrm-menu-nav">' +
        '<input id="crm-menubar-state" type="checkbox" />' +
        '<label class="crm-menubar-toggle-btn" for="crm-menubar-state">' +
          '<span class="crm-menu-logo"></span>' +
          '<span class="crm-menubar-toggle-btn-icon"></span>' +
          '<%- ts("Toggle main menu") %>' +
        '</label>' +
        '<ul id="civicrm-menu" class="sm sm-civicrm">' +
          '<%= searchTpl({items: search}) %>' +
          '<%= branchTpl({items: menu, branchTpl: branchTpl}) %>' +
        '</ul>' +
      '</nav>',
    searchTpl:
      '<li id="crm-qsearch" data-name="QuickSearch">' +
        '<a href="#"> ' +
          '<form action="<%= CRM.url(\'civicrm/contact/search/advanced\') %>" name="search_block" method="post">' +
            '<div>' +
              '<input type="text" id="crm-qsearch-input" name="sort_name" placeholder="\uf002" accesskey="q" />' +
              '<input type="hidden" name="hidden_location" value="1" />' +
              '<input type="hidden" name="hidden_custom" value="1" />' +
              '<input type="hidden" name="qfKey" />' +
              '<input type="hidden" name="_qf_Advanced_refresh" value="Search" />' +
            '</div>' +
          '</form>' +
        '</a>' +
        '<ul>' +
          '<% _.forEach(items, function(item) { %>' +
            '<li><a href="#" class="crm-quickSearchField"><label><input type="radio" value="<%= item.key %>" name="quickSearchField"> <%- item.value %></label></a></li>' +
          '<% }) %>' +
        '</ul>' +
      '</li>',
    branchTpl:
      '<% _.forEach(items, function(item) { %>' +
        '<li <%= attr("li", item) %>>' +
          '<a <%= attr("a", item) %>>' +
            '<% if (item.icon) { %>' +
              '<i class="<%- item.icon %>"></i>' +
            '<% } %>' +
            '<% if (item.label) { %>' +
              '<span><%- item.label %></span>' +
            '<% } %>' +
          '</a>' +
          '<% if (item.child) { %>' +
            '<ul><%= branchTpl({items: item.child, branchTpl: branchTpl}) %></ul>' +
          '<% } %>' +
        '</li>' +
      '<% }) %>'
  }, CRM.menubar || {});

  function getTpl(name) {
    if (!templates) {
      templates = {
        branch: _.template(CRM.menubar.branchTpl, {imports: {_: _, attr: attr}}),
        search: _.template(CRM.menubar.searchTpl, {imports: {_: _, ts: ts, CRM: CRM}})
      };
      templates.tree = _.template(CRM.menubar.treeTpl, {imports: {branchTpl: templates.branch, searchTpl: templates.search, ts: ts}});
    }
    return templates[name];
  }

  function handleResize() {
    if ($(window).width() >= 768 && $('#civicrm-menu').height() > 50) {
      $('body').addClass('crm-menubar-wrapped');
    } else {
      $('body').removeClass('crm-menubar-wrapped');
    }
  }

  function traverse(items, itemName, op) {
    var found;
    _.each(items, function(item, index) {
      if (item.name === itemName) {
        found = (op === 'parent' ? items : item);
        if (op === 'delete') {
          items.splice(index, 1);
        }
        return false;
      }
      if (item.child) {
        found = traverse(item.child, itemName, op);
        if (found) {
          return false;
        }
      }
    });
    return found;
  }

  function attr(el, item) {
    var ret = [], attr = _.cloneDeep(item.attr || {}), a = ['rel', 'accesskey'];
    if (el === 'a') {
      attr = _.pick(attr, a);
      attr.href = item.url || "#";
    } else {
      attr = _.omit(attr, a);
      attr['data-name'] = item.name;
      if (item.separator) {
        attr.class = (attr.class ? attr.class + ' ' : '') + 'crm-menu-border-' + item.separator;
      }
    }
    _.each(attr, function(val, name) {
      ret.push(name + '="' + val + '"');
    });
    return ret.join(' ');
  }

  CRM.menubar.initialize();

})(CRM.$, CRM._);
