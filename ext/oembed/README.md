# oembed (Developmental)

The [oEmbed protocol](https://oembed.com/) allows you to share content between websites:

> oEmbed is a format for allowing an embedded representation of a URL on
> third party sites.  The simple API allows a website to display embedded
> content (such as photos or videos) when a user posts a link to that
> resource, without having to parse the resource directly.

With this extension, CiviCRM acts as an oEmbed provider. It is under development.

oEmbed providers and oEmbed clients exchange small snippets of HTML to represent the embedded content.
While the protocol theoretically allows many tags, prudent behavior on the open Internet is to limit
the range of tags. For content which needs rich information, the typical approach is to convey
an `<iframe>` tag.

Consequently, the `oembed` extension relies heavily on the `iframe` extension.

## "Share" Links

The oEmbed protocol tends to assume that there is only one way to embed a piece of content.  If you want to embed
similar content in two different ways, then you need two URLs. For example, with a contribution page, you might
have both these possibilities:

* Paste a link to the contribution-page. Display a widget with fine-tuned call-to-action.
* Paste a link to the contribution-page. Display a full copy of the page (wherein the user can actually donate).

To support this distinction, we can generate alternate URL's specifically to feed oEmbed clients. The extension includes
a generic/configurable adapter, `civicrm/oembed`. By constructing a `civicrm/oembed` link, you can describe how the
embedded element will behave.
