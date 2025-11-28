# Image Filtering - Article Content Only

## Identified Problem

The original script downloaded ALL images from the page, including:
- ❌ Navigation icons (Twitter, LinkedIn, etc.)
- ❌ Footer images
- ❌ Logos and site elements
- ❌ Related article images

## Solution

An improved script (`download-images-improved.py`) was created that:

### ✅ Includes Only:
- Featured images from the article
- Images from the main article content
- Maps, infographics and charts from the content
- Images related to the article topic

### ❌ Excludes:
- Navigation images (`nav`, `header`)
- Footer images (`footer`)
- Social icons (`twitter`, `linkedin`, `facebook`, `social`)
- Logos (`logo`)
- Small icons (`icon`, navigation `svg`)
- Related article images
- Embedded data URIs
- Next.js optimization images from the layout

## Using the Improved Script

```bash
python3 download-images-improved.py \
  https://bizee.com/articles/can-a-minor-own-a-business \
  can-a-minor-own-a-business
```

## Filtering Criteria

The script uses the following criteria to determine if an image is from the content:

1. **Exclusion patterns:**
   - `nav`, `header`, `footer`, `sidebar`
   - `social`, `icon`, `logo`, `trustpilot`
   - `twitter`, `linkedin`, `facebook`
   - `mobile`, `menu`, `button-icon`

2. **Inclusion patterns:**
   - `teenager`, `headphones`, `map`, `infographic`
   - `states`, `minor`, `business`, `entrepreneur`
   - `article`, `featured`, `content`

3. **HTML location:**
   - Must be within `<main>` or `<article>`
   - Must not be a small SVG (icons)
   - Must not be a data URI

## Result for "Can a Minor Own a Business?"

After cleanup, only 2 images remain:

1. **Featured image:**
   - `public/assets/articles/featured/can-a-minor-own-a-business.webp`
   - Size: 34 KB

2. **Content map:**
   - `public/assets/articles/main-content/map-minor-business-ownership-states.webp`
   - Size: 28 KB

## Future Improvements

- [ ] Use more specific CSS selectors to identify the content area
- [ ] Analyze image size to exclude small icons
- [ ] Verify HTML context around images
- [ ] Allow manual configuration of exclusion/inclusion patterns
