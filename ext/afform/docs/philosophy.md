# Philosophy, Beliefs, Assumptions

Afform is generally grounded in a few beliefs.

## Leap by extension

Afform represents a major conceptual change in how core forms are developed.  It is incubated as an extension that can
be enabled or disabled to taste.  The aim is to write further extensions which (a) build on afform APIs in order to (b) incrementally override/replace particular forms in core.

## Features and user-experiences evolve in funny workflows

If we could sit down and define one true, correct version of a "Donation" form, then our lives as Civi devlopers would
be easy: draw a mockup, write code, ship, and retire.  But we can't -- because Civi users / integrators / developers engage with
the system creatively.  They aim to optimize conversion-rates, to integrate with donor journerys, to question how each
detail makes sense in their situation, etc.  That means switching between open-ended donation amounts, sliders, radios,
etc; revising the language and layout; maybe adding an informational question or newsletter opt-in.

I believe that features and user-experiences evolve in funny workflows -- because the actual stories behind major
improvements have not fit into a single mold.  A main ambition of `afform` is to allow multiple workflows in developing
a common type of deliverable (*the forms*).  Thus, the architecture anticipates scenarios for developers defining forms
concretely; for users defining forms concretely; for using GUIs or text-editors or IDEs or SCMs; for using
cross-cutting hooks and selectors.  Compatibility with multiple workflows is a primary feature of the design.

This is *not* an argument for maximal customization or maximal decentralization.  As participants in an ecosystem, we
must still communicate and exercise judgment about the best way to approach each problem.  But there are legitimate
instances for each workflow; given that each will be sought, we want them to be safe and somewhat consistent.

The aims are *not* achieved by developing every feature in-house. Rather, this is conceived as an effort to use
existing tools/frameworks while relaxing workflows.

What distinguishes `afform` from the original form architecture (Civi's combination of HTML_Quickform, Smarty and
Profiles)?  Each of those workflows has been given some consideration upfront with the aim of providing a *consistent,
unified model* -- so that the same data-structure can be pushed through any of those common workflows.

## Incremental and measurable strictness

JavaScript, PHP, and HTML are forgiving, loosely-typed systems.  This can be viewed as a strength (wrt to learnability
and prototyping), and it can be viewed as a weakness (allowing a number of common mistakes to go unidentified until a
user runs into them).

Personally, I think that strongly-typed languages are better for large, multi-person projects -- providing a fallback
to protect against some common mistakes that arise people don't fully communicate or understand a change.  However,
adopting a strongly-typed system is a major change, and it's not perfect, and I can respect the arguments in favor of
loosely-typed systems.

A compromise is to phase-in some static analysis incrementally.  For `afform`, this means that the main deliverables
(HTML documents+changesets) should be encoded in an inspectable form -- it must be possible for the system to enumerate
all documents+changesets and audit them (i.e.  identifying which form elements -- CSS classes and Angular directives --
are officially supported or unsupported).  This, in turn, means that we can provide warnings and scores to identify
which customizations are more maintainable or more experimental/suspect.

## Don't reinvent the wheel; do use a wheel that fits

The general philosophy and architecture in `afform` could be used with almost any form system that has a clear
component hierarchy (such as AngularJS's HTML notation, Symfony Form's object graph, or Drupal Form API's array-trees).

It specifically uses AngularJS.  Why?  In order of descending importance:

* It can work across a wide range of existing deployment environments (D7, D8, WordPress, Backdrop, Joomla, Standalone).
* It already exists.
* I have experience with it.
* The main Angular tutorials aimed at generalist web developers are reasonably slick.
* The connection between the code you write and what's displayed in the browser is fairly concrete.

It's by no means a perfect wheel.  Other wheels have strengths, too.  I checked the top-line requirement and grabbed
the closest wheel that fit.

## Fidelity to upstream

The upstream AngularJS project canonically represents a form as an HTML file on disk; thus, `afform` does the same.
The upstream project uses `ng-if` for a conditional element; thus, an `afform` instance should do the same.  The
upstream project uses "directives" for composable building blocks; and `afform`, the same.

This convention (a) helps to reduce bike-shedding, (b) helps us get some benefit out of re-using an existing wheel, and
(c) practices what we preach with regard to "consistency" across the various workflows.

## Generally comparable platform requirements

If you can install CiviCRM on a server today, then you should be able to install `afform` and a personalized mix of
extensions which build on it.  This means that the main workflows facilitated by `afform` can require PHP (and even
MySQL), but they can't require (say) Redis or Ruby or NodeJS.
