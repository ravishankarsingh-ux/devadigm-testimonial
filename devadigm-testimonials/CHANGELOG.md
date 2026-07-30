# Changelog

All notable changes to this plugin are documented here. Version numbers follow
[Semantic Versioning](https://semver.org/): MAJOR for breaking changes, MINOR
for new features that stay backward-compatible, PATCH for fixes.

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
