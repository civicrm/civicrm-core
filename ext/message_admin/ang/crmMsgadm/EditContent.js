(function (angular, $) {

  // A rich-text editor edits a content-editable fragment, not a full document - browsers
  // themselves drop <html>/<head>/<body> when such markup is fed into one, since those tags
  // are only meaningful as a document root. Some existing message templates (notably a couple
  // of core's own bundled samples) predate CiviMail's header/footer feature and still carry a
  // full document, so this is used to detect that up front and steer clear of the WYSIWYG
  // editor, rather than letting it silently strip the wrapper.
  function looksLikeFullHtmlDocument(html) {
    return /<!DOCTYPE\s+html|<html[\s>]|<head[\s>]|<body[\s>]/i.test(html || '');
  }

  // Copies each live rich text editor's content into its ng-model. Editors update the model
  // on their own events, which can lag behind a click (Summernote only updates on blur,
  // CKEditor 4 misses toolbar changes and token inserts until a delayed blur) - so this is
  // called before anything reads the model: save, diff, preview, or switching editor mode.
  // Searches the whole document, since the "Open large editor" dialog is a separate DOM subtree.
  angular.module('crmMsgadm').factory('crmMsgadmFlushRichText', () => () => {
    document.querySelectorAll('textarea.crm-msgadm-richtext.crm-wysiwyg-enabled').forEach((el) => {
      const ngModel = angular.element(el).controller('ngModel');
      const val = CRM.wysiwyg.getVal(el);
      if (ngModel && val !== ngModel.$viewValue) {
        ngModel.$setViewValue(val);
      }
    });
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
        // Flush any live rich-text editor first - otherwise this reads a stale model
        // value if the editor hasn't updated the model yet.
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
          beforeClose: crmMsgadmFlushRichText,
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

})(angular, CRM.$);
