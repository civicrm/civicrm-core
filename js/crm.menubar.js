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
    toggleButton: (CRM.config.userFramework != 'Standalone'),
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
        if (document.getElementById('crm-container')) {
          render(markup);
        } else {
          new MutationObserver(function(mutations, observer) {
            if (document.getElementById('crm-container')) {
              observer.disconnect();
              render(markup);
            }
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
              if ($(menu).parent().data('name') === 'Home') {
                $('#crm-menubar-drilldown').focus();
              } else {
                $(menu).siblings('a[accesskey]').focus();
              }
            })
            .smartmenus(CRM.menubar.settings);
          initialized = true;
          CRM.menubar.initializeResponsive();
          CRM.menubar.initializeSearch();
          CRM.menubar.initializeDrill();
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
          handleResize();
        });
      }
      $('body')
        .removeClass('crm-menubar-hidden')
        .addClass('crm-menubar-visible');
      handleResize();
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
      document.documentElement.style.setProperty('--crm-menubar-bottom', '0px');
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
      // Sometimes the logo does not stop spinning (ex: file downloads)
      if (spin) {
        window.setTimeout(function() {
          CRM.menubar.spin(false);
        }, 10000);
      }
    },
    getItem: function(itemName) {
      return traverse(CRM.menubar.data.menu, itemName, 'get');
    },
    findItems: function(searchTerm) {
      return findRecursive(CRM.menubar.data.menu, searchTerm.toLowerCase().replace(/ /g, ''));
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
      if (CRM.menubar.toggleButton && (CRM.menubar.position === 'over-cms-menu' || CRM.menubar.position === 'below-cms-menu')) {
        $('#civicrm-menu')
          .on('click', 'a[href="#toggle-position"]', function(e) {
            e.preventDefault();
            CRM.menubar.togglePosition();
          })
          .append('<li id="crm-menubar-toggle-position"><a href="#toggle-position" title="' + ts('Adjust menu position') + '"><i class="crm-i fa-arrow-up" aria-hidden="true"></i></a>');
        CRM.menubar.position = CRM.cache.get('menubarPosition', CRM.menubar.position);
      }
      $('body').addClass('crm-menubar-visible crm-menubar-' + CRM.menubar.position);
    },
    removeToggleButton: function() {
      $('#crm-menubar-toggle-position').remove();
      CRM.menubar.toggleButton = false;
      if (CRM.menubar.position === 'below-cms-menu') {
        CRM.menubar.togglePosition();
      }
    },
    initializeResponsive: function() {
      var $mainMenuState = $('#crm-menubar-state');
      // hide mobile menu beforeunload
      $(window).on('beforeunload unload', function(e) {
        if (!e.originalEvent.returnValue) {
          CRM.menubar.spin(true);
        }
        if ($mainMenuState[0].checked) {
          $mainMenuState[0].click();
        }
      })
        .on('resize', function() {
          if (!isMobile() && $mainMenuState[0].checked) {
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
                formName: 'crmMenubar',
                fieldName: 'crm-qsearch-input',
                filters: {},
              };
            if (option.val() === 'sort_name') {
              params.input = request.term;
            } else {
              params.filters[option.val()] = request.term;
            }
            // Specialized Autocomplete SearchDisplay: @see ContactAutocompleteProvider
            CRM.api4('Contact', 'autocomplete', params).then(function(result) {
              var ret = [];
              if (result.length > 0) {
                $('#crm-qsearch-input').autocomplete('widget').menu('option', 'disabled', false);
                $.each(result, function(key, item) {
                  // Add extra items from the description (see contact_autocomplete_options setting)
                  let description = (item.description || []).filter((v) => v);
                  let extra = description.length ? ' :: ' + description.join(' :: ') : '';
                  ret.push({value: item.id, label: item.label + extra});
                });
              } else {
                $('#crm-qsearch-input').autocomplete('widget').menu('option', 'disabled', true);
                var label = option.closest('label').text();
                var msg = ts('%1 not found.', {1: label});
                // Remind user they are not searching by contact name (unless they enter a number)
                if (option.val() !== 'sort_name' && !(/[\d].*/.test(params.name))) {
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
            // This is when an item is 'focussed' by keyboard up/down or mouse hover.
            // It is not the same as actually having focus, i.e. it is not :focus
            var lis = $(event.currentTarget).find('li[data-cid="' + ui.item.value + '"]');
            lis.children('div').addClass('ui-state-active');
            lis.siblings().children('div').removeClass('ui-state-active');
            // Returning false leaves the user-entered text as it was.
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
        })
        .autocomplete( "instance" )._renderItem = function( ul, item ) {
          var uiMenuItemWrapper = $("<div class='ui-menu-item-uiMenuItemWrapper'>");
          if (item.value == 0) {
            // "No results"
            uiMenuItemWrapper.text(item.label);
          }
          else {
            uiMenuItemWrapper.append($('<a>')
              .attr('href', CRM.url('civicrm/contact/view', {reset: 1, cid: item.value}))
              .css({ display: 'block' })
              .text(item.label)
              .click(function(e) {
                if (e.ctrlKey || e.shiftKey || e.altKey) {
                  // Special-clicking lets you open several tabs.
                  e.stopPropagation();
                }
                else {
                  // Fall back to original behaviour.
                  e.preventDefault();
                }
              }));
          }

          return $( "<li class='ui-menu-item' data-cid=" + item.value + ">" )
            .append(uiMenuItemWrapper)
            .appendTo( ul );
        };
      $('#crm-qsearch > a').keyup(function(e) {
        if ($(e.target).is(this)) {
          $('#crm-qsearch-input').focus();
          CRM.menubar.close();
        }
      });
      $('#crm-qsearch form[name=search_block]').on('submit', function() {
        const searchValue = $('#crm-qsearch-input').val();
        const searchkey = $('#crm-qsearch-input').attr('name');
        if (!searchValue) {
          return false;
        }
        var $menu = $('#crm-qsearch-input').autocomplete('widget');
        // If only one contact was returned, go directly to that contact page
        if ($('li.ui-menu-item', $menu).length === 1) {
          var cid = $('li.ui-menu-item', $menu).data('ui-autocomplete-item').value;
          if (cid > 0) {
            document.location = CRM.url('civicrm/contact/view', {reset: 1, cid: cid});
            return false;
          }
        }
        // Form redirects to Advanced Search, which does not automatically search with wildcards,
        // aside from contact name.
        // To get comparable results, append wildcard to the search term.
        else if (searchkey !== 'sort_name' && searchkey !== 'id') {
          $('#crm-qsearch-input').val(searchValue + '%');
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
          // Set name because the mini-form submits directly to adv search
          value = $selection.data('advSearchLegacy') || $selection.val();
        $('#crm-qsearch-input').attr({name: value, placeholder: '\ud83d\udd0d ' + label, title: label});
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
      var savedDefault = CRM.cache.get('quickSearchField');
      if (savedDefault) {
        $('.crm-quickSearchField input[value="' + savedDefault + '"]').prop('checked', true);
      } else {
        $('.crm-quickSearchField:first input').prop('checked', true);
      }
      setQuickSearchValue();
      $('#civicrm-menu').on('activate.smapi', function(e, item) {
        return !$('ul.crm-quickSearch-results').is(':visible:not(.ui-state-disabled)');
      });
    },
    initializeDrill: function() {
      $('#civicrm-menu').on('keyup', '#crm-menubar-drilldown', function() {
        var term = $(this).val(),
          results = term ? CRM.menubar.findItems(term).slice(0, 20) : [];
        $(this).parent().next('ul').html(getTpl('branch')({items: results, branchTpl: getTpl('branch'), drillTpl: _.noop}));
        $('#civicrm-menu').smartmenus('refresh').smartmenus('itemActivate', $(this).closest('a'));
      });
    },
    treeTpl:
      '<nav id="civicrm-menu-nav">' +
        '<input id="crm-menubar-state" type="checkbox" />' +
        '<label class="crm-menubar-toggle-btn" for="crm-menubar-state">' +
          '<span class="crm-menu-logo"></span>' +
          '<span class="crm-menubar-toggle-btn-icon"></span>' +
          '<span class="sr-only"><%- ts("Toggle main menu") %></span>' +
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
              '<input type="text" id="crm-qsearch-input" name="sort_name" placeholder="\ud83d\udd0d" accesskey="q" />' +
              '<input type="hidden" name="hidden_location" value="1" />' +
              '<input type="hidden" name="hidden_custom" value="1" />' +
              '<input type="hidden" name="qfKey" />' +
              '<input type="hidden" name="_qf_Advanced_refresh" value="Search" />' +
            '</div>' +
          '</form>' +
        '</a>' +
        '<ul>' +
          '<% _.forEach(items, function(item) { %>' +
            '<li><a href="#" class="crm-quickSearchField"><label><input type="radio" value="<%= item.key %>" name="quickSearchField" data-adv-search-legacy="<%= item.adv_search_legacy %>"> <%- item.value %></label></a></li>' +
          '<% }) %>' +
        '</ul>' +
      '</li>',
    drillTpl:
      '<li class="crm-menu-border-bottom" data-name="MenubarDrillDown">' +
        '<a href="#"><input type="text" id="crm-menubar-drilldown" placeholder="' + _.escape(ts('Find menu item...')) + '"></a>' +
        '<ul></ul>' +
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
            '<ul>' +
              '<% if (item.name === "Home") { %><%= drillTpl() %><% } %>' +
              '<%= branchTpl({items: item.child, branchTpl: branchTpl}) %>' +
            '</ul>' +
          '<% } %>' +
        '</li>' +
      '<% }) %>'
  }, CRM.menubar || {});

  function getTpl(name) {
    if (!templates) {
      templates = {
        drill: _.template(CRM.menubar.drillTpl, {}),
        search: _.template(CRM.menubar.searchTpl, {imports: {_: _, ts: ts, CRM: CRM}})
      };
      templates.branch = _.template(CRM.menubar.branchTpl, {imports: {_: _, attr: attr, drillTpl: templates.drill}});
      templates.tree = _.template(CRM.menubar.treeTpl, {imports: {branchTpl: templates.branch, searchTpl: templates.search, ts: ts}});
    }
    return templates[name];
  }

  function handleResize() {
    if (!isMobile() && ($('#civicrm-menu').height() >= (2 * $('#civicrm-menu > li').height()))) {
      $('body').addClass('crm-menubar-wrapped');
    } else {
      $('body').removeClass('crm-menubar-wrapped');
    }
    document.documentElement.style.setProperty('--crm-menubar-bottom', ($('#civicrm-menu').height() + $('#civicrm-menu').position().top) + 'px');
  }

  // Figure out if we've hit the mobile breakpoint, based on the rule in crm-menubar.css
  function isMobile() {
    return $('.crm-menubar-toggle-btn', '#civicrm-menu-nav').css('top') !== '-99999px';
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

  function findRecursive(collection, searchTerm) {
    var items = _.filter(collection, function(item) {
      return item.label && _.includes(item.label.toLowerCase().replace(/ /g, ''), searchTerm);
    });
    _.each(collection, function(item) {
      if (_.isPlainObject(item) && item.child) {
        var childMatches = findRecursive(item.child, searchTerm);
        if (childMatches.length) {
          Array.prototype.push.apply(items, childMatches);
        }
      }
    });
    return items;
  }

  function attr(el, item) {
    var ret = [], attr = _.cloneDeep(item.attr || {}), a = ['rel', 'accesskey', 'target'];
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
