# Replacing phpQuery

(*Discussion piece. The two classes here compile-by-eye only — they are not wired
up, not called from anywhere, and not tested.*)

CiviCRM rewrites Angular partials and Afform markup through `rubobaquero/phpquery`.
That package last saw a commit in May 2022, carries 6,621 lines, and includes a call
to `create_function()` — removed in PHP 8.0 — in `phpQuery/phpQuery/Callback.php`.
PHP 8.6's deprecation of the mbregex family is the first breakage to land in a code
path we actually execute; it will not be the last.

`HtmlDocument` and `NodeSet` cover everything core asks of phpQuery in about 315
lines.

## What core uses

Fourteen methods across ten files (`Civi/Angular/ChangeSet.php`, `Coder.php`, and
the Afform/search_kit/civi_mail/recaptcha metadata injectors):

| phpQuery | Uses | Here |
|---|---|---|
| `newDocument($html, 'text/html')` | 4 | `HtmlDocument::parse()` |
| `pq($element)` | 23 | `NodeSet::of()` |
| `pq($selector, $doc)` | 5 | `$doc->find()` |
| `pq('<tag></tag>')` | 1 | `$doc->createNode()` |
| `find()` | 7 | `find()` |
| `attr($k)` | 9 | `getAttr()` |
| `attr($k, $v)` | 11 | `setAttr()` |
| `parents()` | 4 | `ancestor()` |
| `is()` | 1 | `matches()` |
| `html()` / `html($markup)` | 3 | `getHtml()` / `setHtml()` |
| `append()` | 2 | `append()` |
| `insertAfter()` | 1 | `insertAfter()` |
| `each()` | 3 | `each()` |
| `get(0)` | 1 | `get()` |
| `markupOuter()` | 1 | `saveHtml()` |
| `->document` | 1 | `getDomDocument()` |

Every selector core passes is plain CSS, so `symfony/css-selector` covers them. It
is already in `composer.lock` by way of dompdf, and would want promoting to a direct
`require`.

## Why not symfony/dom-crawler

`dom-crawler` is a read-only API, and 15 of core's ~43 calls are mutations. Adding
`wa72/htmlpagedom` on top supplies those, and together they map 11 of the 14 methods.
The three they don't:

- **`parents()` is not `closest()`.** phpQuery's `parents()` starts the walk at the
  parent; jQuery's and Symfony's `closest()` start at the node itself. Core's four
  sites look up `[af-fieldset]`/`[af-join]` from an `af-field`, which never carries
  those attributes, so the two agree today — and would quietly diverge the first time
  anyone nests one. Hence `ancestor()` here, named so nobody "fixes" it later.
  (`->ancestors()->filter()` is *not* the translation: Crawler's `filter()` searches
  descendants of the current set, so it returns nothing.)
- **`pq('crm-recaptcha2')` with no document** resolves against phpQuery's global
  current-document registry. Nothing else has an equivalent, and nothing should.
- **Fragment parse/serialize** — see below. No package does this for us.

That leaves two new direct dependencies, a broken type hint for the extensions
below, and the fiddly part still hand-written.

## The fragment round-trip

This is the only part that is delicate, and it is why the facade sits on
`DOMDocument` rather than on a parser of its own choosing.

Angular partials arrive as bare fragments. `HtmlDocument::parse()` wraps one in an
HTML 4.01 Transitional doctype plus `<html><head><meta charset><body>`, hands it to
`DOMDocument::loadHTML()`, and `saveHtml()` slices the scaffolding back off between
`<body>` and `</body>`. That is what `DOMDocumentWrapper::documentFragmentLoadMarkup()`
and `documentFragmentToMarkup()` do today, down to the doctype and the string
surgery.

Keeping that doctype is load-bearing: it decides how DOMDocument escapes attribute
values, which `Civi\Angular\Coder::cleanupAttribute()` then unwinds, and which
`Civi\Angular\PartialSyntaxTest::basicConsistencyExamples()` pins to exact strings.
Swapping in an HTML5 parser moves all of that — note that `Crawler::__construct()`
defaults `$useHtml5Parser = true`, reaching for `masterminds/html5` where phpQuery
uses `DOMDocument::loadHTML()`.

## Keeping extensions working

`hook_civicrm_alterAngular` callbacks are handed a `\phpQueryObject`, and some
type-hint it. Scanning a 772-extension universe checkout turns up five that touch
the API — `mosaico`, `mailingpreviewtext`, `formprotection`,
`com.klangsoft.btrmsgtpl`, `civicrm-recurring-mail` — plus `civicrm-wordpress`.
All of them use `find`/`attr` and nothing more.

A shim can carry them. `phpQueryObject extends HtmlDocument` means one object
satisfies both the old type hint and new core code, so there is no flag day:

```php
class phpQueryObject extends HtmlDocument {
  public \DOMDocument $document;
  public function newNodeSet(array $nodes): NodeSet {
    return new phpQueryNodeSet($this, $nodes);
  }
  public function markupOuter(): string { return $this->saveHtml(); }
}

class phpQueryNodeSet extends NodeSet {
  public function attr(string $name, ?string $value = NULL) {
    return $value === NULL ? $this->getAttr($name) : $this->setAttr($name, $value);
  }
  public function html(?string $markup = NULL) {
    return $markup === NULL ? $this->getHtml() : $this->setHtml($markup);
  }
  public function parents(string $selector): NodeSet { return $this->ancestor($selector); }
  public function is(string $selector): bool { return $this->matches($selector); }
}

function pq($subject, $context = NULL) {
  $doc = $context ?? phpQueryObject::$current;
  if ($subject instanceof HtmlDocument) {
    return $subject->nodes();
  }
  if ($subject instanceof DOMElement) {
    return NodeSet::of($subject);
  }
  return str_starts_with(ltrim($subject), '<')
    ? $doc->createNode($subject)
    : $doc->find($subject);
}
```

Overriding `newNodeSet()` is enough to make every chained call return a
legacy-shaped object. Registered as a composer classmap entry it costs nothing
until an extension touches it.

The shim is quoted here rather than committed as a file: it declares global-namespace
classes, which would collide with vendor phpQuery's `phpQueryObject` for as long as
both are installed.

The bare `pq('selector')` form still needs a current-document static, set by
`ChangeSet` around each `alterHtml` callback. There is no way round it — that form
carries no document reference. It is confined to the shim and dies with it; core's
own instance (recaptcha) can be fixed now by passing `$doc`.

## What the call sites look like

```php
// AfformMetadataInjector.php:62
- $joinName = pq($afField)->parents('[af-join]')->attr('af-join');
+ $joinName = NodeSet::of($afField)->ancestor('[af-join]')->getAttr('af-join');

// AfformAdminInjector.php:38,127
- pq($doc)->find('option[value="km"]')->insertAfter('option[value="miles"]');
- pq($doc)->append($editMenu);
+ $doc->find('option[value="km"]')->insertAfter('option[value="miles"]');
+ $doc->append($editMenu);

// Coder.php:54
- $doc->document->formatOutput = TRUE;
- return $this->cleanup($doc->markupOuter());
+ $doc->getDomDocument()->formatOutput = TRUE;
+ return $this->cleanup($doc->saveHtml());

// recaptcha/AfformReCaptcha2.php:34 — loses the loop and the global-state lookup
- foreach (pq('crm-recaptcha2') as $captcha) {
-   pq($captcha)->attr('recaptchakey', $key);
- }
+ $doc->find('crm-recaptcha2')->setAttr('recaptchakey', $key);
```

## Open questions

- `NodeSet::getHtml()` concatenates `$dom->saveHTML($child)` over the child nodes.
  phpQuery's `DOMDocumentWrapper::markup()` instead builds a scratch document,
  imports the nodes, serializes, and slices the wrapper off. They should agree for
  our inputs; `PartialSyntaxTest` is the place to find out. If they don't,
  `getHtml()` grows the same scratch-document step and nothing else changes.
- `getAttr()` returns NULL for an absent attribute, where `DOMElement::getAttribute()`
  returns `''`. `AfformSearchMetadataInjector` tests `=== NULL`, so the
  `hasAttribute()` guard matters.
- `append()` adopts nodes into the first target and clones into the rest, matching
  phpQuery. Without that a multi-target append moves nodes instead of copying them.
- `insertAfter()` uses the first element matching the selector; phpQuery inserts
  after every match. The one caller has a single `option[value="miles"]`.
