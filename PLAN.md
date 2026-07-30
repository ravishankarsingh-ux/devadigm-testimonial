# Devadigm Testimonials - Build Plan

Technical specification for a testimonials plugin targeting **Atelier Lune**
(WordPress 7.0.2, block/FSE theme, Cloudways + Breeze, no page builder).

The visual advisory and live design specimens live in
[`docs/testimonials-design-board.html`](docs/testimonials-design-board.html).

---

## 1. Environment findings

Observed from the live site before writing this plan:

| Item | Value | Consequence |
| --- | --- | --- |
| WordPress | 7.0.2 | Interactivity API, Block Bindings and `wp_enqueue_block_style` all available |
| Theme | `atelier-lune`, full block theme | Build blocks, not shortcodes |
| Page builder | none | No Elementor/Bricks widget layer needed |
| Plugins | `breeze` only | Full-page caching - markup must be cache-safe |
| Palette | ink, bone, brass, chalk, cream, plum | Consume as `--wp--preset--color--*` |
| Fonts | Fraunces / Inter / IBM Plex Mono, self-hosted | Consume as `--wp--preset--font-family--*` |

**Do not hardcode a single colour or typeface.** Reading from theme presets is what
makes the plugin portable to every other client site.

---

## 2. Architecture

- **Slug / prefix:** `devadigm-testimonials`, text domain `devadigm-testimonials`, PHP prefix `Devadigm\Testimonials\`.
- **Minimums:** PHP 8.1, WordPress 6.7 (target 7.0).
- **Blocks:** Block API v3, `block.json` metadata, built with `@wordpress/scripts`.
- **Interactivity:** WordPress Interactivity API for slideshow, filtering, marquee. No jQuery, no Swiper, no Splide.
- **Rendering:** server-rendered via `render_callback`; manipulate markup with `WP_HTML_Tag_Processor`, never regex.
- **Quality gates:** PHPCS (WordPress-Extra), PHPStan, ESLint, PHPUnit via `wp-env`, Playwright for the carousel and filter interactions.

### Data model

Custom post type `testimonial` (`show_in_rest: true`).

Taxonomies: `testimonial_service` (service line), `testimonial_source` (Google, LinkedIn, email, video, direct).

Registered meta (all `show_in_rest` with auth callbacks):

`author_name`, `author_role`, `author_company`, `company_url`, `avatar_id`,
`rating`, `given_on`, `source_url`, `video_id`, `locale`, `featured`,
`result_metric`, `consent_*`, `verified_at`, `original_text`.

### Consent & provenance

First-class fields, not an afterthought:

- `consent_actor`, `consent_at`, `consent_method` (form submission ID / signed email / upload token)
- `verified_at` - immutable once set
- `original_text` - the unedited submission, retained so edits are provable
- Configurable **expiry** that flags aged testimonials for re-consent or auto-unpublish
- Capability `moderate_testimonials`, separate from `edit_posts`

---

## 3. Display layouts (six variants)

Each is a block variation on one parent block, so each appears separately in the inserter.

| Variant | Use | Notes |
| --- | --- | --- |
| **Spotlight** | one large testimonial | The primary homepage treatment |
| **Row** | three across | CSS `subgrid` so attribution baselines align across unequal quote lengths |
| **Slideshow** | one at a time | Scroll-snap + Interactivity API; ARIA carousel pattern |
| **Wall** | filterable masonry | Dedicated `/testimonials` page, filter chips by service line |
| **Marquee** | continuous drift | Needs a pause control (WCAG 2.2.2) |
| **Inline proof** | single pull-quote | Drops beside a mid-page CTA |

Sidebar query controls: service line, source, minimum rating, featured-only, count,
ordering (newest / rating / random / manual).

**Block Bindings** should expose every testimonial field so designers can compose
layouts we did not ship, without new code.

---

## 4. Quotation-mark treatments (six options)

A style option independent of layout. Rendered live in the design board.

1. **Ledger** - oversized mark hanging outside the text measure, optically aligned to the first line. *Default.*
2. **Watermark** - enormous glyph bled off the corner, clipped, low opacity.
3. **Hairline badge** - small mark inside a hairline circle, above the quote.
4. **Drop-cap fusion** - mark and first letter set as one display cluster.
5. **Bracket rules** - no glyph; two short rules imply the quotation.
6. **Filled slab** - solid block with a knocked-out mark (plum on light, brass on dark).

Rules that apply to all six:

- The glyph lives in a `::before` pseudo-element, **never in the DOM text** - otherwise it breaks copy-paste, pollutes indexed text, and makes screen readers announce "left double quotation mark" before every quote.
- Derive the character from `get_locale()`. German opens with `„`, French uses `« »`.
- Decorative marks carry `aria-hidden`.

---

## 5. Palette contrast audit

Computed against the live `theme.json` values (WCAG 2.1 relative luminance):

| Foreground | Ground | Ratio | Body text | Action |
| --- | --- | ---: | --- | --- |
| ink `#14161c` | cream | 16.0:1 | Pass | - |
| bone `#ede7dc` | ink | 15.1:1 | Pass | - |
| brass `#b08d57` | ink | 5.98:1 | Pass | Brass is a text colour on dark only |
| brass `#b08d57` | cream | 2.81:1 | **Fail** | Decoration only; ship `#7d5f2e` (5.38:1) for accent text on light |
| chalk `#8c887e` | cream | 3.22:1 | **Fail** | Attribution lines would fail; use `#6b675e` (5.13:1) |
| cream on plum `#5b2a3b` | - | 10.4:1 | Pass | Why plum is the Filled-slab fill on light grounds |

The plugin should ship corrected text tokens rather than using the raw presets for copy.

---

## 6. Non-negotiables

**Accessibility**
- ARIA carousel pattern: labelled controls, live region on slide change, keyboard arrows, auto-advance stops on `focus-within`.
- Marquee pause control.
- Ratings as real text ("Rated 5 out of 5") with glyphs hidden from assistive tech.
- `prefers-reduced-motion` honoured, including `scroll-behavior`.

**Performance**
- Zero front-end JS unless an interactive variant is present; per-block conditional enqueue.
- Avatars via `wp_get_attachment_image` with srcset, lazy loading, explicit dimensions (no CLS).
- Video behind a poster facade that loads the player only on click.
- Query results in transients, invalidated on save; markup must survive Breeze minification.

**SEO / schema**
- Ship schema **off by default**. Google treats `Review` / `AggregateRating` on your own business pages as self-serving and will not render stars; marking up imported third-party reviews as first-party breaks policy.
- When enabled, scope to a legitimate entity - never a blanket `AggregateRating` on the homepage.
- What helps is semantic `figure` / `blockquote cite` / `figcaption` and real selectable text.

---

## 7. AI layer (opt-in, human-gated)

- **Pull-quote extraction** - propose 2–3 tight excerpts from a long testimonial. Highest-value feature: the bottleneck is editing, not collection.
- **Transcription + captions** for video (text quote + WebVTT).
- **Relevance matching** - store an embedding per testimonial at save; surface the one most related to the page it renders on.
- **Claim-risk flagging** - warn on unverifiable superlatives and medical/financial outcome claims before publish.
- **Auto-tagging** of service line and extraction of `result_metric`.

Guardrails: every AI output lands in a draft field requiring approval; API key in
`wp-config.php`, never the database; the model selects from the customer's words and
**never rewrites them**; a single master off switch.

---

## 8. Phasing

| Phase | Scope | Estimate |
| --- | --- | --- |
| 1 | CPT, meta, admin UX; block with Spotlight / Row / Slideshow; all six quote treatments; token inheritance; a11y baseline. **Ships to Atelier Lune.** | 2–3 weeks |
| 2 | Submission form block, magic-link requests, moderation pipeline, consent records + expiry, CSV import | 2 weeks |
| 3 | Wall, Marquee, Inline proof; Interactivity-API filtering; video capture + facade + transcripts | 2–3 weeks |
| 4 | Pull-quote extraction, auto-tagging, relevance matching, risk flagging; impressions + A/B | 3 weeks |
| 5 | Third-party imports, preset packs, REST + WP-CLI, white-labelling, multisite | ongoing |

---

## 9. Explicitly out of scope

- Blanket `AggregateRating` stars on the homepage.
- A bundled slider library.
- Storing a person's name, face and words without a consent record.
- Letting AI rewrite a customer's words in a published quote.
- A settings page with sixty options.

---

## 10. Security note

The WordPress admin credentials for the target site were shared in plain text in the
task description. **Rotate that password and enable two-factor before the plugin is
installed.** No login was performed while preparing this plan - the environment
findings in section 1 come from the publicly served HTML only.
