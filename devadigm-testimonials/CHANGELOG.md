# Changelog

All notable changes to this plugin are documented here. Version numbers follow
[Semantic Versioning](https://semver.org/): MAJOR for breaking changes, MINOR
for new features that stay backward-compatible, PATCH for fixes.

## 1.5.1 - 2026-08-03

### Fixed

- **"Check again" on Dashboard > Updates did not actually check again.**
  WordPress' own update check works by deleting its `update_plugins`
  transient, which is what makes every plugin's update check re-run - but
  this plugin's GitHub lookup was cached in a second, separate transient
  with its own twelve-hour lifetime, and nothing cleared that one when
  WordPress cleared its own. A site sitting on an older version, checking
  again after a new release was tagged, could keep seeing "no update
  available" for up to twelve hours regardless of how many times "check
  again" was clicked. The plugin now clears its own cached result whenever
  WordPress clears its, so a manual check-again (or WordPress' own
  scheduled check) reaches GitHub immediately rather than reusing a
  possibly-stale answer. Verified directly: seeded a stale cached result,
  triggered WordPress' own cache-clear, and confirmed both the plugin's
  cache clears and the very next lookup reaches GitHub and finds the real
  latest tag.

## 1.5.0 - 2026-08-03

### Added

- **Equal card height for Marquee.** A new "Equal card height" toggle in the
  block's Layout panel (Marquee only) makes every card in the strip match
  the height of the tallest one. Off by default, since a strip of cards each
  sized to their own content is the more common look. The reason this needed
  real code rather than just flipping a CSS property: flexbox's own
  cross-axis stretch should already do this by default, but never actually
  engaged, because `.dvdm-t__item`'s `height: 100%` - written for the Grid's
  equal-height cells - is a percentage against a track with no fixed height
  of its own, which resolves to nothing rather than to the `auto` that
  stretch needs to size against. Turning the new toggle on replaces that
  with an explicit `height: auto` and `align-items: stretch`, which is what
  actually makes the track's tallest card set the height every other card
  stretches to fill. Off, nothing changes from before.

### Changed

- **Reduced the space between a Spotlight quote and its attribution.** The
  base gap between a card's quote, Read more and attribution is sized for
  Grid's compact cards; next to Spotlight's much larger quote the same gap
  reads as noticeably more air. Spotlight now uses a tighter gap of its own.
- **The recommended Quotation mark glyph is now "Heavy" instead of "Curly"**
  for new installs. "Curly" is the correct Unicode character for a curly
  quote, but exactly how curved it looks still depends on the quote font's
  own design of that character; "Heavy" is a dedicated ornamental glyph
  (the dingbat range) that draws as a rounded, curled mark consistently
  regardless of the quote font. This only changes the default a fresh
  install starts with - an existing site that already has a Glyph chosen in
  Design keeps it, and can switch to Heavy from that same dropdown.

### Fixed

- **The Ledger quotation mark could visually overlap the star rating shown
  above it**, on Grid and Marquee in particular. Ledger's mark is sized well
  past its own line box on purpose, which leaves it prone to reaching up
  into whatever sits directly above it - the rating, when shown - and the
  space between them was not consistently enough headroom for how far this
  particular mark's ink reaches, across every quote font it might be paired
  with. The rating now carries its own margin beneath it whenever Ledger is
  the active mark, on every layout.
- **On a multi-card Spotlight slider ("Slideshow"), Ledger's mark could
  read as noticeably detached from the quote text next to it.** The gap
  between mark and text is sized for a single, wide Spotlight quote; the
  same absolute distance looks larger, proportionally, once that quote is
  narrowed down to one slide sharing a row with others. That gap is now
  pulled in specifically wherever the mark is already scaled down for a
  narrower column.

## 1.4.3 - 2026-07-30

### Fixed

- **The Read More dialog's quote wrapped to a narrow column, leaving most of
  the dialog's width empty**, on Spotlight in particular. Spotlight caps its
  own card's quote at `max-width: 26ch` to keep the big pull-quote's line
  lengths readable - a sensible limit for the card, but the dialog is a
  clone of that same quote sitting inside the same layout wrapper, so it
  inherited that same 26-character cap with nothing around 2.75x the
  width of the box it was sitting in going unused. `.dvdm-t__dialog
  .dvdm-t__quote` now explicitly resets `max-width: none`, spotted and
  confirmed by direct measurement (614px of the dialog's 704px, versus the
  568px-wide card quote it was wrongly matching before) - the card itself is
  untouched.

## 1.4.2 - 2026-07-30

### Fixed

- **The Read More dialog could render pinned to the left edge of the screen
  instead of centred**, with a large empty gap on the right. The dialog's
  centring relies on all four `inset` sides plus `margin: auto` cooperating;
  a theme's own reset targeting the bare `dialog` element (rather than a
  class this plugin controls) can win the fight for one or two of those
  properties individually - typically `left`/`right`/`margin` - even though
  this plugin's own selector is more specific overall, because CSS resolves
  each property independently rather than as a whole rule. With `right`
  no longer pinned to 0, `margin: auto` has nothing left to distribute
  against, and the dialog collapses to wherever `left` puts it. The
  properties that place and size the dialog (`position`, `inset`, `margin`,
  `width`, `max-width`, `z-index`) now carry `!important`, which a theme
  rule can only still beat by also using `!important` itself - confirmed by
  reproducing the exact failure with a simulated hostile rule first (dialog
  pinned 16px from the left, 580px of empty space on the right, matching
  what was reported), then confirming this fix holds against that same rule.

## 1.4.1 - 2026-07-30

### Fixed

- **The Read More dialog could render with a huge, wrapped-to-a-few-words
  quote and no visible box behind it** - reported against the live site
  after v1.3.0 was already installed, so this is a genuine second look, not
  a repeat of the earlier dialog-positioning fix. Two separate real bugs,
  both now fixed:
  - The dialog's quote is deliberately sized a little larger than the card's
    own text (`1.2em`), but `em` is relative to whatever font-size the
    dialog inherits - and the dialog lives inside the same wrapper a
    block's Typography panel writes a custom font-size onto. A block given
    a large display size (a plausible, real setting to reach for on a
    single hero-style testimonial) inflated the dialog's quote by that same
    multiple, wrapping it to a handful of oversized words per line and
    making it look like it was overflowing its box. The dialog now resets
    its own font-size to a plain `1rem` baseline first, so its sizing is
    always relative to that, never to whatever the block happens to be
    displayed at.
  - The dialog's background was meant to fall back to an opaque `canvas`
    only when no Surface colour is configured - but the Design screen's
    "unset" default was being written out as the literal string
    `transparent` (not left undefined), and a CSS custom property that has
    any value at all, including that string, is never replaced by a var()
    fallback. Every dialog was therefore transparent whenever no Surface
    colour had been explicitly chosen, which is the common case, since a
    transparent surface is the right default for a bare Spotlight-style
    *card* but the wrong one for a dialog sitting over page content. The
    site-wide default is now left unset in that case, so the card and the
    dialog each fall back to their own correct default, and a colour that
    is explicitly chosen still reaches both exactly as before.

## 1.4.0 - 2026-07-30

### Added

- **Update straight from GitHub.** The plugin now checks the public
  `ravishankarsingh-ux/devadigm-testimonial` repository's tags for a newer
  version and, when one exists, shows the normal "update available" notice
  in Plugins and Dashboard > Updates - the same UI a wordpress.org-hosted
  plugin uses, including "View version x.y.z details" with the changelog for
  that release. Clicking Update now installs it directly, with no manual zip
  download and upload. The repository holds more than this plugin (docs,
  sample data), so the downloaded archive's real plugin folder is relocated
  into place automatically before WordPress installs it - see
  `includes/class-updater.php`. Update checks are cached for twelve hours.

## 1.3.0 - 2026-07-30

### Added

- **Alignment control.** A block toolbar (Align text left/center/right) now
  sits above every testimonials block, matching how core blocks expose
  alignment. It writes the same `has-text-align-{left|center|right}` class
  core paragraphs use, so a theme's own alignment rules apply too.
- **Read More is now universal.** Previously it only appeared for side-by-side
  arrangements (Grid/columns/sliders); a single large Spotlight or Inline
  testimonial always rendered its full text with no way to trim it. Read More
  now depends purely on the block's own Read More toggle (or the Design
  screen's site-wide default when the block hasn't set one), regardless of
  layout or column count.

### Fixed

- **The Read More dialog could render with overlapping, misplaced content.**
  Its positioning relied entirely on the browser's default `<dialog>`
  stylesheet, which a theme's own CSS reset can (and did) override. The
  dialog now carries explicit position, sizing and z-index rules of its own,
  so it centres correctly and stays above page content no matter what the
  active theme resets.
- **The Read More link ignored the block's own alignment**, because
  `.dvdm-t__more` hardcoded `align-self: start`, overriding whatever
  alignment the surrounding card was using. That override is gone; the link
  now follows the card's alignment like everything else in it.
- **Ledger and Slab quotation marks did not centre or right-align.** Both
  rely on CSS Grid with an `1fr` content track, and a grid track cannot
  shrink-wrap to the width of wrapped text - so centering the grid centred a
  column much wider than the visible text, leaving the mark and quote looking
  left-anchored regardless of alignment. Center and right alignment now
  switch these two marks to a stacked block/flex arrangement instead of
  fighting the grid.
- **The Dropcap mark and centered/right-aligned text were fundamentally at
  odds** - a floated first letter is inherently a left-margin technique, and
  combining it with centered text produced lopsided wrapping. Center and
  right alignment now fall back to a plain inline mark for Dropcap instead of
  attempting to float it.
- **Marquee's Read More could occasionally be unreliable to click** while the
  strip was actively drifting: a card partially masked by the marquee's own
  `overflow: hidden` still reports its full, unclipped bounding box to the
  browser, so a click aimed at the reported center can land past the visible
  edge, on the page behind the strip. Hover/focus now pauses the drift via
  the Interactivity API directly (previously CSS-only `:hover`, which a theme
  could override), stopping the motion as soon as a pointer or keyboard focus
  reaches a card. This does not claim to eliminate the underlying limitation
  100% of the time - it is a characteristic of continuously-scrolling masked
  strips in general - but it removes the CSS-override failure mode and stops
  the motion far sooner than before.

## 1.2.0 - 2026-07-30

### Changed

- **Columns and sliding are now independent of which layout is chosen**, on any
  layout except Marquee. In 1.1.0 sliding only worked on Spotlight or Grid, and
  Columns only appeared for Grid - Inline had neither, and a plain Spotlight
  block had no way to become a 2- or 3-column arrangement, sliding or static.
  Both controls now show for Spotlight, Grid and Inline alike. Layout still
  decides what a card looks like (Spotlight bare and large, Grid boxed, Inline
  a pull-quote accent); Columns and Slider decide how many show and whether
  they page through.
- **One Columns setting drives both roles.** It is how many sit in a row when
  static, and how many a slider shows per page - the same idea either way, so
  the separate "slides per view" control from 1.1.0 is gone. At columns: 1 with
  sliding off, an extra testimonial simply stacks on the next row instead of
  needing a distinct "single" mode.
- Masonry and sliding are mutually exclusive (packing by height and paging by
  a fixed width are different ideas), so the Masonry toggle only appears while
  a block is not already sliding.

### Fixed

- **The Slider toggle and Columns control were invisible for any block still
  carrying a pre-1.1.0 layout name** (Row, Wall, Slideshow) until someone
  happened to re-pick a layout from the dropdown. The editor read the raw
  stored layout name directly instead of translating it the way the PHP
  renderer already did, so a legacy block's *effective* layout, columns and
  slider state never reached the controls that decide what to show - they
  simply stayed hidden. The editor now runs the same translation PHP does, so
  a legacy block shows its real current arrangement immediately, with an
  informational notice rather than a warning, and can be adjusted without
  first switching its layout name.
- **The "Default columns" setting silently did nothing** unless a block had
  already had its own Columns control touched at least once. `columns` had no
  default in the block's own schema, so an untouched block wrote no column
  count onto itself at all, and the stylesheet's own hardcoded fallback of 3
  rendered instead - never the Design screen's value. Attributes are now
  normalised once, before either the style or the markup is built from them,
  so every rendered block always carries its real, resolved column count.
- **`wp_localize_script()` silently stringifies every top-level scalar value**
  it is handed - a long-standing WordPress behaviour, not new in this release,
  but one this plugin was tripped by. `defaultLoop` and `defaultReadMore` were
  arriving in the editor as the strings `"1"` or `""`, and `"" !== false` is
  `true` in JavaScript - so a site with either setting switched off in the
  Design screen still had the editor believe it was on. All numeric and
  boolean defaults the editor reads are now nested inside one object in the
  localized data, which is what let `legacyLayouts`' own booleans survive
  correctly all along and is the fix for the rest.
- A mark sized for one wide, solitary quote (Ledger's oversized glyph, the
  Filled slab's block) is scaled down whenever that quote actually has
  neighbours narrowing its column - previously only true for Grid or Marquee
  specifically, now true for Spotlight or Inline in multi-column mode too.

## 1.1.0 - 2026-07-30

### Changed

- **Row and Wall merged into one Grid layout with a Masonry toggle.** They only
  ever differed in whether cards share a row baseline or pack by height, which
  is one switch, not two layouts. Existing blocks saved as Row or Wall keep
  rendering exactly as before - `row` now maps to Grid, `wall` maps to Grid with
  Masonry on.
- **Sliding is now a property of a layout, not a layout of its own.** Spotlight
  and Grid can each be turned into a slider, with control over how many
  testimonials are visible at once. This is what makes a two-column or
  three-column slider possible, which a dedicated "Slideshow" layout could
  never offer. Blocks saved as Slideshow keep rendering as a one-at-a-time
  Spotlight slider.
- The block inserter now offers ready-made variations - Spotlight, Spotlight
  slider, Grid, Two-column slider, Three-column slider, Masonry wall, Marquee,
  Inline proof - instead of one generic block plus a sidebar full of choices.

### Added

- **Read more for long quotes**, on Grid and Marquee. A quote past a
  configurable word count is trimmed to a set number of lines with a Read more
  link; shorter quotes are left alone. The link is a real link to the
  testimonial and opens the full quote in a native `<dialog>` - it still works
  with scripting turned off, and it still works as a link (open in new tab,
  copy link) with scripting on.
- **Slider looping**: an end-of-track arrow can wrap back to the start, on by
  default, switchable per block or site-wide.
- **Slider autoplay**: off by default. When set, a Pause control appears,
  autoplay stops the moment anyone interacts with the slider, and it never
  starts at all for visitors who have asked for reduced motion.
- Site-wide defaults for columns, slider looping, autoplay and the Read more
  behaviour, all on the Design settings screen, all overridable per block.
- A version number and an upgrade routine. The plugin now records which
  version last ran and carries new setting defaults forward automatically on
  upgrade, without ever rewriting saved block content.
- This changelog, and the plugin version now shows at the top of the Design
  settings screen.

### Fixed

- **The slideshow did not loop past the last slide.** The old carousel clamped
  its target index to `slides.length - 1`, so clicking "next" on the final
  slide simply did nothing. Looping is now implemented properly and is on by
  default for any slider, with an off switch per block or site-wide.
- A related race during the rewrite: clicking an arrow while the previous
  click's smooth-scroll animation was still travelling could have the
  scroll-position watcher (which exists to catch direct swipes and scrollbar
  drags) read the track mid-flight and overwrite the state the click had just
  set, landing one slide behind where a normal click cadence should put it.
  The scroll-position watcher now defers to a click in progress and only
  reconciles once the browser's own `scrollend` signal confirms the animation
  has actually finished.

## 1.0.0 - 2026-07-30

Initial release.

- Six display layouts: Spotlight, Row, Slideshow, Wall, Marquee, Inline proof.
- Six quotation-mark treatments: Ledger, Watermark, Hairline badge, Drop-cap
  fusion, Bracket rules, Filled slab.
- Design settings screen: colours, typefaces, the quotation glyph, rating
  icons, avatar shape, carousel arrows, size scales and custom CSS, each
  defaulting to inheriting from the active theme.
- Custom post type with consent and provenance fields, service line and source
  taxonomies.
- Built on the WordPress Interactivity API; no jQuery, no bundled slider
  library, no build step required to run the plugin.
- Verified at zero axe violations against WCAG 2.2 AA.
