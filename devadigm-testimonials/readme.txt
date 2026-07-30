=== Devadigm Testimonials ===
Contributors: devadigm
Tags: testimonials, reviews, social proof, block, slider
Requires at least: 6.7
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Testimonials with six display layouts, six quotation-mark treatments, and a settings screen for colours, fonts and icons.

== Description ==

A block-first testimonials plugin. It renders on the server, hydrates with the
WordPress Interactivity API, and ships no jQuery and no bundled slider library.

**Six layouts**

* Spotlight - one large testimonial
* Row - a few side by side, equal height
* Slideshow - one at a time, keyboard and swipe
* Wall - filterable masonry
* Marquee - continuous drift with a pause control
* Inline proof - a single pull-quote for beside a call to action

**Six quotation-mark treatments**

* Ledger - oversized mark hanging at the left
* Watermark - huge mark bled off the corner
* Hairline badge - small mark inside a ring
* Drop-cap fusion - mark set with the first letter
* Bracket rules - two rules, no glyph
* Filled slab - knocked-out mark on a solid block

The mark is drawn from a CSS custom property and never enters the markup, so
copying a quote copies only the words, the indexed text stays clean, and screen
readers do not announce "left double quotation mark" before every testimonial.

**Everything is configurable**

Colours, typefaces, the quotation glyph, rating icons, avatar shape and the
slideshow arrows all read from custom properties. Each one can come from the
active theme, from the plugin's design screen, or from an individual block, in
that order of precedence. Leave a field on "Inherit from theme" and the plugin
stays invisible to the theme, which is what makes it portable between sites.

**Accessibility**

The slideshow follows the ARIA carousel pattern with labelled controls, a live
region and arrow-key support. The marquee has a pause control, as WCAG requires
for motion running past five seconds. Ratings are announced as text. Dots are
24px targets. `prefers-reduced-motion` is honoured throughout. The blocks pass
axe with no violations at WCAG 2.2 AA.

== Frequently Asked Questions ==

= Why is review schema switched off by default? =

Google treats `Review` and `AggregateRating` markup on your own business pages
as self-serving and will not render stars for it, however correct the markup is.
Marking up imported third-party reviews as first-party breaks their policy
outright. Turn it on only for pages that review a specific product or service.

= Why can I set an accent colour and an accent text colour separately? =

Because a colour that reads well as a mark or a solid fill is often too light to
use as text. Metallics and pastels usually are. Keeping them apart means you can
have the accent you want without failing contrast on the small text.

= Does it need a build step? =

No. The editor script is written against `wp.element.createElement` and the
front-end script is a plain ES module, so the plugin runs straight from the zip.

== Changelog ==

= 1.0.0 =
* First release: post type, meta and admin columns; consent and provenance
  fields; six layouts; six quotation-mark treatments; design settings screen for
  colours, typography, quotation marks and icons; Interactivity API slideshow
  and marquee.
