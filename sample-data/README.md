# Sample testimonial data

`devadigm-testimonials-sample-data.xml` is a WordPress WXR export containing
twelve testimonials for trying the plugin out.

## Importing

1. Activate **Devadigm Testimonials** first. The importer needs the testimonial
   post type and its taxonomies to already exist, otherwise the items are
   skipped.
2. Go to **Tools > Import > WordPress** and install the importer if prompted.
3. Upload the XML, assign the posts to any existing user, and leave
   "Download and import file attachments" unticked. There are no attachments.

## What is in it

Twelve testimonials across five service lines (Branding, Web Design, Web
Development, SEO, Retainer) and five sources (Direct, Google, LinkedIn, Clutch,
Email).

The data is deliberately uneven so the layouts get an honest test:

- Ratings from 4 to 5, including half steps, so the rating filter has something
  to bite on.
- Five are marked featured, so "Featured only" returns a real subset.
- Five carry a result metric; the rest do not, so cards of differing height land
  in the wall and row layouts.
- Quotes range from one line to three, which is what shows whether the grid
  alignment holds.
- Three carry a company URL, so the attribution renders as a link.
- All twelve carry a consent method and a verified date.

No featured images are included, so avatars fall back to initials. Add images to
individual testimonials to see the avatar treatment.

## A warning worth repeating

Every person, company and quote in this file is invented. None of it is a real
customer statement. Delete it before the site goes live, and never let sample
testimonials reach a public page: publishing a fabricated endorsement is a
regulatory problem in most markets, not just an embarrassing one.
