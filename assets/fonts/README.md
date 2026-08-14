# Self-hosted fonts — drop-in instructions

The theme references these six `.woff2` files (declared in
`assets/css/fonts.css`, preloaded in `inc/enqueue.php`). Both families are free
and open-source under the **SIL Open Font License**, so self-hosting is fully
license-clean (spec: fully free + no Google round-trip, §8).

Place these exact filenames in this folder:

| File | Family / weight |
|---|---|
| `dmsans-400.woff2` | DM Sans Regular |
| `dmsans-500.woff2` | DM Sans Medium |
| `dmsans-700.woff2` | DM Sans Bold |
| `playfairdisplay-400.woff2` | Playfair Display Regular |
| `playfairdisplay-600.woff2` | Playfair Display SemiBold |
| `playfairdisplay-700.woff2` | Playfair Display Bold |

## Where to get them (free)

Easiest is the **Fontsource** packages (pre-subset `.woff2`, OFL):

```bash
# from any machine with npm, then copy the woff2 files here and rename:
npm pack @fontsource/dm-sans @fontsource/playfair-display
# unpack the tarballs; the woff2 files live under files/*-latin-<weight>-normal.woff2
```

Or download the families from Google Fonts / the Fontsource site and convert the
`.ttf` to `.woff2` (e.g. with `woff2_compress` or an online converter), then
rename to match the table above.

> Tip: a Latin subset keeps each file ~15–30 KB. If the store ever needs Bangla
> product copy rendered in these UI fonts, add a Bangla-capable face here and
> extend `fonts.css` — the body already falls back to the system UI font, so
> missing files degrade gracefully rather than breaking layout.
