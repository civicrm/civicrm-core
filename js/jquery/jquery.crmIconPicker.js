// https://civicrm.org/licensing
(function($, _) {
  "use strict";
  /* jshint validthis: true */
  const icons = [];
  const aliases = {};
  let loaded;

  $.fn.crmIconPicker = function() {

    function loadIcons() {
      if (!loaded) {
        loaded = $.Deferred();
        // Load iconPicker stylesheet
        let stylesheet = document.createElement('link');
        stylesheet.rel = 'stylesheet';
        stylesheet.type = 'text/css';
        stylesheet.href = CRM.config.resourceBase + 'css/crm-iconPicker.css';
        document.head.appendChild(stylesheet);
        // Load icons
        $.get(CRM.config.resourceBase + 'bower_components/font-awesome/css/all.css').done(function(data) {
          let match;
          let prev;
          let last;
          const regex = /\.(fa-[-a-zA-Z0-9]+):+before\s*\{\s*content:\s*"([^"]+)"/g;
          while((match = regex.exec(data)) !== null) {
            // If icon is same as previous class, it's an alias
            if (match[2] !== prev) {
              icons.push(match[1]);
              last = match[1];
            } else if (last) {
              aliases[last] = aliases[last] || [];
              aliases[last].push(match[1].replace(/-/g, ''));
            }
            prev = match[2];
          }
          loaded.resolve();
        });
      }
      return loaded;
    }

    return this.each(function() {
      if ($(this).hasClass('iconpicker-widget')) {
        return;
      }

      let $input = $(this),
        classes = ($input.attr('class') || '').replace('crm-icon-picker', ''),
        $button = $('<a class="crm-icon-picker-button" href="#" />').button().removeClass('ui-corner-all').attr('title', $input.attr('title')),
        $style = $('<select class="crm-form-select"></select>').addClass(classes),
        options = [
          {key: 'fa-rotate-90', value: ts('Rotate right')},
          {key: 'fa-rotate-270', value: ts('Rotate left')},
          {key: 'fa-rotate-180', value: ts('Rotate 180')},
          {key: 'fa-flip-horizontal', value: ts('Flip horizontal')},
          {key: 'fa-flip-vertical', value: ts('Flip vertical')}
        ];

      function formatButton() {
        let val = $input.val().replace('fa ', '');
        val = val.replace('crm-i ', '');
        const split = val.split(' ');
        $button.button('option', {
          label: split[0] || ts('None'),
          icons: {primary: val ? val + ' crm-i' : 'fa-'}
        });
        $style.toggle(!!split[0]).val(split[1] || '');
      }

      $input.hide().addClass('iconpicker-widget').after($style).after('&nbsp;').after($button).change(formatButton);

      CRM.utils.setOptions($style, options, ts('Normal'));

      formatButton();

      $style.change(function() {
        if ($input.val()) {
          const split = $input.val().split(' '),
            style = $style.val();
          $input.val(split[0] + (style ? ' ' + style : '')).change();
        }
      });

      $button.click(function(e) {
        let dialog;

        function displayIcons() {
          const term = $('input[name=search]', dialog).val().replace(/-/g, '').toLowerCase();
          const $place = $('div.icons', dialog).html('');
          let matches = [];
          if (term.length) {
            // Match icon classes
            matches = icons.filter((i) => (i.replace(/-/g, '').indexOf(term) > -1));
            // Match icon aliases
            for (let [icon, iconAliases] of Object.entries(aliases)) {
              if (iconAliases.filter((i) => (i.replace(/-/g, '').indexOf(term) > -1)).length) {
                matches.push(icon);
              }
            }
          }
          $.each(icons, function(i, icon) {
            if (!term.length || matches.indexOf(icon) > -1) {
              const item = $('<a href="#" title="' + icon + '"/>').button({
                icons: {primary: 'crm-i ' + icon + ' ' + $style.val()}
              });
              $place.append(item);
            }
          });
        }

        function displayDialog() {
          dialog.append(
            '<div class="icon-ctrls crm-clearfix">' +
            '<input class="crm-form-text" name="search" placeholder="&#x1f50d;"/>' +
            '<select class="crm-form-select"></select>' +
            // Add "No Icon" button unless field is required
            ($input.is('[required]') ? '' : '<button type="button" class="cancel" title=""><i class="crm-i fa-ban" aria-hidden="true"></i> ' + ts('No icon') + '</button>') +
            '</div>' +
            '<div class="icons"></div>'
          );
          let $styleSelect = $('.icon-ctrls select', dialog);
          CRM.utils.setOptions($styleSelect, options, ts('Normal'));
          $styleSelect.val($style.val());
          $styleSelect.change(function() {
            $style.val($styleSelect.val());
            displayIcons();
          });
          $('.icon-ctrls button', dialog).click(pickIcon);
          displayIcons();
          dialog.unblock();
        }

        function pickIcon(e) {
          const newIcon = $(this).attr('title'),
            style = newIcon ? $style.val() : '';
          $input.val(newIcon + (style ? ' ' + style : '')).change();
          dialog.dialog('close');
          e.preventDefault();
        }

        dialog = $('<div id="crmIconPicker"/>').dialog(CRM.utils.adjustDialogDefaults({
          title: $input.attr('title'),
          width: '80%',
          height: '90%',
          modal: true
        })).block()
          .on('click', 'a', pickIcon)
          .on('keyup', 'input[name=search]', displayIcons)
          .on('dialogclose', function() {
            $(this).dialog('destroy').remove();
          });
        loadIcons().done(displayDialog);
        e.preventDefault();
      });

    });
  };

  $(document)
    .on('crmLoad', function(e) {
      $('.crm-icon-picker', e.target).not('.iconpicker-widget').crmIconPicker();
    });
}(CRM.$, CRM._));
