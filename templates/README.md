# Bundled Templates

Drop Elementor template **JSON exports** into this folder and they will be
imported automatically the next time the plugin loads (no manual upload needed).

## How it works

- On load, the plugin scans `*.json` files in this folder **and any sub-folders**,
  importing any that are new or have changed since the last import.
  Already-imported, unchanged files are skipped, so there are no duplicates.
- Imported templates appear in the **Wedding Widget** library (the heart icon)
  inside the Elementor editor, and in **Wedding Widget → Templates** in wp-admin.

## Categories via sub-folders (recommended)

Put each JSON inside a folder named after its category. The folder name becomes
the category automatically:

```
templates/
  adat/
    adat-jawa.json
    adat-jawa.jpg        <- optional preview, same base name
  flower/
    rustic-flower.json
    rustic-flower.png
  minimalist.json        <- in the root = no category
```

Results:
- `adat/adat-jawa.json`   → category **Adat**
- `flower/rustic-flower.json` → category **Flower**
- `minimalist.json`       → no category

Folder names are humanized: `adat-jawa` and `adat_jawa` both become **Adat Jawa**.
A `manifest.json` entry (see below) always overrides the folder-derived category.

## Adding a template

1. In Elementor, export a page/section/template as JSON.
2. Drop the `.json` file into the matching category folder (create the folder if
   needed), e.g. `templates/flower/elegant.json`.
3. (Optional) Add a preview image with the **same base name** next to it, e.g.
   `templates/flower/elegant.jpg` (`.jpg`, `.jpeg`, `.png`, `.webp`, or `.gif`).

That's it. Reload any admin page to trigger the import.

## Optional manifest.json

To set titles/categories explicitly, create a single `manifest.json` in the
**templates root**. Keys can be a relative path or a bare filename:

```json
{
  "flower/rustic-flower.json": {
    "title": "Rustic Flower",
    "category": "Flower & Botanical",
    "thumbnail": "rustic-flower.png"
  },
  "elegant-gold.json": {
    "title": "Elegant Gold",
    "category": "Minimalist"
  }
}
```

Fields are all optional:
- `title` — overrides the title from the JSON / filename.
- `category` — creates/assigns a template category.
- `thumbnail` — image filename in this folder to use as the preview.

If there is no manifest entry, the title falls back to the JSON `title` (or the
filename) and the thumbnail falls back to a same-named image if present.

## Notes

- Max behavior matches the manual uploader (JSON must contain `content` or be a
  raw element array).
- Editing a bundled JSON file and reloading will create a **new** template entry
  (the old one is not removed automatically), because templates may have been
  customized after import. Delete the old one from **Wedding Widget → Templates**
  if you want to replace it.
