=== Devadigm Testimonials ===
Contributors: devadigm
Tags: testimonials, reviews, social proof, block, slider
Requires at least: 6.7
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.5.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Testimonials with four display layouts, an optional slider on any of them, six quotation-mark treatments, and a settings screen for colours, fonts and icons.

== Description ==

A block-first testimonials plugin. It renders on the server, hydrates with the
WordPress Interactivity API, and ships no jQuery and no bundled slider library.

**Four layouts, and columns and sliding apply to three of them**

* Spotlight - one or more large testimonials
* Grid - columns side by side, with an optional Masonry mode that packs cards
  by height instead of aligning them
* Inline proof - one or more pull-quotes for beside a call to action
* Marquee - continuous drift with a pause control

Columns (1 to 6) and an optional slider work the same way on Spotlight, Grid
and Inline alike - layout decides what a card looks like, columns and sliding
decide how many show and whether they page through. At columns: 1 with no
slider, an extra testimonial simply stacks on the row below.

A slider loops by default, can autoplay on a timer with a pause control, and
long quotes get a Read more link that opens the full quote in a dialog rather
than stretching the card, wherever a testimonial actually has others beside
it.

Blocks saved with an earlier version's Row, Wall or Slideshow layout keep
rendering exactly as before - nothing needs re-editing after an upgrade, and
the editor shows their real current columns, masonry and slider state rather
than hiding those controls until the layout is manually switched.

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

The slider follows the ARIA carousel pattern with labelled controls, a live
region and arrow-key support. The marquee has a pause control, as WCAG requires
for motion running past five seconds; so does slider autoplay, and autoplay
never starts at all for visitors who have asked for reduced motion. Ratings are
announced as text. Dots are 24px targets. `prefers-reduced-motion` is honoured
throughout. The blocks pass axe with no violations at WCAG 2.2 AA.

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

See CHANGELOG.md for full detail. Summary:

= 1.5.1 =
* Fixed: "Check again" on Dashboard > Updates did not actually re-check
  GitHub for a new release, because the plugin's own GitHub lookup was
  cached separately from WordPress' own update check and nothing cleared it
  at the same time. A site could see "no update available" for up to twelve
  hours after a new release, no matter how many times it checked again.

= 1.5.0 =
* Added: an "Equal card height" toggle for Marquee, so every card in the
  strip can match the tallest one instead of sizing to its own content.
* Changed: Spotlight uses a tighter gap between its quote and attribution.
* Changed: new installs now recommend the "Heavy" quotation mark glyph
  instead of "Curly", since it draws as a consistently curved, rounded mark
  regardless of the quote font - existing sites keep their own choice and
  can switch from the same Design screen dropdown.
* Fixed: the Ledger quotation mark could visually overlap the star rating
  shown above it, on Grid and Marquee in particular.
* Fixed: on a multi-card Spotlight slider, Ledger's mark could read as
  detached from the quote text sharing its narrower column.

= 1.4.3 =
* Fixed: the Read More dialog's quote wrapped to a narrow column instead of
  using the dialog's full width, on Spotlight in particular - it was
  inheriting the card's own 26-character line-length cap, meant to keep the
  compact card readable, not the wide-open dialog.

= 1.4.2 =
* Fixed: the Read More dialog could render pinned to the left edge with a
  large empty gap on the right, instead of centred, when the active theme's
  own CSS reset for the `dialog` element won the fight for one or two of the
  properties that centre it. The properties that place and size the dialog
  now carry !important so a theme reset can no longer partially override
  them.

= 1.4.1 =
* Fixed: the Read More dialog could render with a huge, wrapped quote and no
  visible box behind it, on a block given a large custom font size, or when
  no Surface colour had been set in Design. Both were real bugs in how the
  dialog inherited font-size and background - not the same issue v1.3.0
  already fixed, a separate one found afterwards.

= 1.4.0 =
* Added: the plugin now checks GitHub for a newer release and offers it
  through the normal Plugins > Update now flow, instead of needing a zip
  uploaded by hand each time.

= 1.3.0 =
* Added a block toolbar alignment control (left/center/right), matching core
  blocks and applying to every layout.
* Read More is now universal - it depends only on the block's own Read More
  toggle, no longer only appearing for side-by-side arrangements. A single
  large Spotlight or Inline testimonial can now trim long quotes too.
* Fixed: the Read More dialog could render with overlapping, misplaced
  content when a theme's CSS reset overrode the browser's default dialog
  positioning. It now carries its own explicit positioning rules.
* Fixed: the Read More link ignored the block's alignment due to a hardcoded
  `align-self` override.
* Fixed: the Ledger and Slab quotation marks did not centre or right-align
  correctly (a CSS Grid limitation with wrapped text); they now use a
  stacked layout under those alignments.
* Fixed: the Dropcap mark and centered/right-aligned text conflicted (a
  floated first letter versus centered text); Dropcap now falls back to a
  plain inline mark under those alignments.
* Hardened Marquee's Read More against being unreliable to click mid-drift:
  hover/focus now pauses the strip via the Interactivity API directly instead
  of relying on CSS `:hover` alone.

= 1.2.0 =
* Columns and sliding now work on Spotlight and Inline as well as Grid - not
  just Grid as in 1.1.0 - so a plain Spotlight or Inline block can become a
  2- or 3-column arrangement, sliding or static.
* One Columns setting drives both roles: how many sit in a row when static,
  and how many a slider shows per page. The separate "slides per view"
  control from 1.1.0 is gone.
* Fixed: the Slider toggle and Columns control were invisible for any block
  still carrying a pre-1.1.0 layout name (Row, Wall, Slideshow) until the
  layout was manually re-picked from the dropdown.
* Fixed: the "Default columns" Design screen setting silently did nothing
  unless a block's own Columns control had already been touched once.
* Fixed: the site-wide Loop and Read More toggles were being ignored by the
  editor due to a WordPress data-passing quirk that stringifies settings
  values - a switched-off setting was read as still on.

= 1.1.0 =
* Row and Wall merged into one Grid layout with a Masonry toggle - they only
  ever differed in one switch, not two layouts.
* Sliding is now a property of Spotlight or Grid rather than its own layout,
  which makes a 2-column or 3-column slider possible for the first time.
* Read more for long quotes in Grid and Marquee cards, opening the full quote
  in a dialog.
* Slider looping (on by default) and optional autoplay with a pause control.
* Ready-made inserter variations for the common combinations.
* A version number and an automatic upgrade routine.
* Content saved under the old layout names keeps rendering unchanged.

= 1.0.0 =
* First release: post type, meta and admin columns; consent and provenance
  fields; six layouts; six quotation-mark treatments; design settings screen for
  colours, typography, quotation marks and icons; Interactivity API slideshow
  and marquee.

== Upgrade Notice ==

= 1.2.0 =
Columns and sliding now work on Spotlight and Inline too, not just Grid, and
several bugs are fixed - most notably the Slider toggle and Columns control
not appearing for blocks saved under a pre-1.1.0 layout name. Existing
content is unaffected either way.

= 1.1.0 =
Row, Wall and Slideshow are replaced by Grid (with a Masonry option) and a
slider property on Spotlight or Grid. Existing blocks keep their current
appearance automatically - only newly added blocks see the new layout names.
