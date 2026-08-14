(function (angular, $, _) {

  // A rich-text editor edits a content-editable fragment, not a full document - browsers
  // themselves drop <html>/<head>/<body> when such markup is fed into one, since those tags
  // are only meaningful as a document root. Some existing message templates (notably a couple
  // of core's own bundled samples) predate CiviMail's header/footer feature and still carry a
  // full document, so this is used to detect that up front and steer clear of the WYSIWYG
  // editor, rather than letting it silently strip the wrapper.
  function looksLikeFullHtmlDocument(html) {
    return /<!DOCTYPE\s+html|<html[\s>]|<head[\s>]|<body[\s>]/i.test(html || '');
  }

  // <civi-rich-text-input> only syncs its live CKEditor content back into the ng-model
  // when its own Save button is clicked - reading the model (to save, diff, preview, or
  // switch editor mode) while a session is still open would otherwise silently use stale
  // data. Shared between EditContent.js and Edit.js, and searches the whole document (not
  // just a local $element) since it's also called from the "Open large editor" modal,
  // which is a separate DOM subtree.
  angular.module('crmMsgadm').factory('crmMsgadmFlushRichText', function() {
    return function flushOpenRichTextEditor() {
      var openEl = document.querySelector('civi-rich-text-input[editing]');
      if (openEl && typeof openEl.saveAndCloseEditor === 'function') {
        openEl.saveAndCloseEditor();
      }
    };
  });

  angular.module('crmMsgadm').component('crmMsgadmEditContent', {
    bindings: {
      onPreview: '&',
      tokenList: '<',
      disabled: '<',
      isWorkflow: '<',
      original: '=',
      savedMain: '<',
      msgtpl: '='
    },
    templateUrl: '~/crmMsgadm/EditContent.html',
    controller: function ($scope, $element, crmStatus, crmUiAlert, dialogService, $rootScope, crmMsgadmFlushRichText) {
      const ts = $scope.ts = CRM.ts('crmMsgadm');
      const $ctrl = this;

      $ctrl.isDisabled = function() {
        return $ctrl.disabled;
      };

      // System Workflow templates always use the raw-HTML Monaco editor (matching the
      // classic form's own "system message -> textarea" rule) - there's no toggle for them.
      // User-Driven templates default to a WYSIWYG editor, since they're typically composed
      // by non-technical users, but can switch to raw HTML via the toggle button.
      $ctrl.htmlEditorMode = 'richtext';
      $ctrl.isMsgHtmlEditing = false;
      $ctrl.isFullHtmlDocument = false;

      // Re-checked whenever $ctrl.msgtpl points at a different record (e.g. switching between
      // the Current/Draft/Original tabs, each backed by a separate loaded record) - but not on
      // every keystroke, since typing mutates the same record's msg_html in place rather than
      // replacing the object, and the point here is to catch what was loaded, not flip the
      // editor mode back and forth as the user edits.
      $scope.$watch(function() { return $ctrl.msgtpl; }, function(msgtpl) {
        $ctrl.isFullHtmlDocument = !!(msgtpl && looksLikeFullHtmlDocument(msgtpl.msg_html));
        if ($ctrl.isFullHtmlDocument) {
          $ctrl.htmlEditorMode = 'monaco';
        }
      });

      $ctrl.canToggleRichText = function(fld) {
        return fld === 'msg_html' && !$ctrl.isWorkflow;
      };

      $ctrl.isRichText = function(fld) {
        return $ctrl.canToggleRichText(fld) && $ctrl.htmlEditorMode === 'richtext';
      };

      $ctrl.toggleHtmlEditorMode = function() {
        if ($ctrl.htmlEditorMode === 'richtext') {
          crmMsgadmFlushRichText();
        }
        $ctrl.htmlEditorMode = $ctrl.htmlEditorMode === 'richtext' ? 'monaco' : 'richtext';
      };

      // System Workflow templates diff against the reserved-default "Original" revision.
      // User-Driven templates have no such thing, so they diff against a snapshot of the
      // record as it was last loaded/saved instead - i.e. "what have I changed since then".
      $ctrl.getDiffBaseRecord = function() {
        return $ctrl.isWorkflow ? $ctrl.original : $ctrl.savedMain;
      };

      $ctrl.hasDiffBase = function() {
        return !!$ctrl.getDiffBaseRecord();
      };

      $ctrl.monacoOptions = function (opts) {
        return angular.extend({}, {
          wordWrap: 'wordWrapColumn',
          wordWrapColumn: 100,
          wordWrapMinified: false,
          wrappingIndent: 'indent'
        }, opts);
      };

      $ctrl.openFull = function(title, fld, monacoOptions, isDiff = false) {
        // Flush any live rich-text session first - otherwise this reads a stale model
        // value if the user hasn't clicked the rich-text editor's own Save button yet.
        crmMsgadmFlushRichText();
        const model = {
          title: title,
          monacoOptions: $ctrl.monacoOptions(angular.extend({crmHeightPct: 0.80}, monacoOptions)),
          openPreview: function (options) {
            return $ctrl.openPreview(options);
          },
          record: $ctrl.msgtpl,
          field: fld,
          tokenList: $ctrl.tokenList,
          // Inserting a token doesn't make sense while comparing two versions of the content.
          isDiff: isDiff,
          // See $ctrl.getDiffBaseRecord() - System Workflow templates diff against the
          // reserved default; User-Driven templates diff against their own last-saved state.
          original: (isDiff && $ctrl.getDiffBaseRecord()) ? $ctrl.getDiffBaseRecord()[fld] : '',
          // A diff is a raw-text comparison, so it doesn't make sense in rich-text mode -
          // force Monaco and hide the toggle for that case, regardless of the shared mode.
          canToggleRichText: !isDiff && $ctrl.canToggleRichText(fld),
          isRichText: function() { return !isDiff && $ctrl.isRichText(fld); },
          toggleRichText: function() { $ctrl.toggleHtmlEditorMode(); }
        };
        var options = CRM.utils.adjustDialogDefaults({
          // show: {effect: 'slideDown'},
          dialogClass: 'crm-msgadm-dialog',
          autoOpen: false,
          height: '90%',
          width: '90%'
        });
        return dialogService.open('expandedEditDlg', '~/crmMsgadm/ExpandedEdit.html', model, options)
          // Nothing to do but hide warnings. The field was edited live.
          .then(function(){}, function(){});
      };

      $ctrl.openPreview = function(options) {
        // Same staleness risk as openFull() above - the preview reads the live model too.
        crmMsgadmFlushRichText();
        $rootScope.$emit('previewMsgTpl', options);
      };

    }
  });

  // Two-way binds whether the wrapped <civi-rich-text-input>'s CKEditor session is
  // actively open (as opposed to its collapsed preview state), so callers can gate things
  // like token insertion on there actually being a live cursor to insert at. Used by both
  // the inline editor (EditContent.html) and the "Open large editor" modal (ExpandedEdit.html).
  // civi-rich-text-input toggles its "editing" attribute from plain native event handlers
  // (open on click/keydown, close via its own Cancel/Save buttons), not through Angular, so
  // a $watch alone won't pick up the change until something else happens to trigger a digest -
  // a MutationObserver is used instead to react to the attribute directly, and $applyAsync to
  // sync it into Angular's scope.
  angular.module('crmMsgadm').directive('crmMsgadmRichtextEditing', function($parse) {
    return {
      link: function(scope, element, attrs) {
        const setter = $parse(attrs.crmMsgadmRichtextEditing).assign;
        function sync() {
          const isEditing = element[0].hasAttribute('editing');
          if (setter) {
            scope.$applyAsync(function() {
              setter(scope, isEditing);
            });
          }
        }
        const observer = new MutationObserver(sync);
        observer.observe(element[0], {attributes: true, attributeFilter: ['editing']});
        sync();
        scope.$on('$destroy', function() {
          observer.disconnect();
        });
      }
    };
  });

  // Inserts a token into the wrapped <civi-rich-text-input>'s live editor session on the
  // named broadcast event, mirroring crmMonacoInsertRx's insert-at-cursor for the Monaco
  // editor. Since only one of the two editors is ever present at a time (toggled via
  // ng-if), both can listen for the exact same event name without conflict.
  angular.module('crmMsgadm').directive('crmMsgadmRichtextInsertRx', function() {
    return {
      link: function(scope, element, attrs) {
        scope.$on(attrs.crmMsgadmRichtextInsertRx, function(e, token) {
          CRM.wysiwyg.insert(element[0].input, token);
        });
      }
    };
  });

})(angular, CRM.$, CRM._);
