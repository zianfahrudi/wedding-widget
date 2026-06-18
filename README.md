# Wedding Widget

A lightweight, independent **Elementor widget pack for wedding invitation websites**. Every widget is written from scratch (no third‑party premium code) and ships with its own private in‑editor template library.

Requires **Elementor 3.0+**. No license key, no external account.

---

## ✨ Features

- **10 Elementor widgets** under a dedicated **Wedding Widget** category.
- **Built‑in template library** inside the Elementor canvas (heart icon) with **search by name**, **category tabs**, and **thumbnails** — kept separate from Elementor's default "My Templates".
- **Admin dashboard** to upload/manage templates (bulk upload, categories, thumbnails, edit, bulk delete).
- **RSVP & Wishes** stored as native WordPress comments — moderate them from **Comments**, with attendance icons, IP, and an initials avatar.
- Vanilla JS (no jQuery dependency on the front end) and respects `prefers-reduced-motion`.

## 🧩 Widgets

| Widget | Description |
| --- | --- |
| **Countdown** | Counts down to a target date with styled day/hour/minute/second boxes (timezone‑aware). |
| **Cover** | Full‑screen invitation cover with an "open" reveal animation; can read the guest name from the `?to=` URL parameter. |
| **RSVP** | Attendance form (name, status, message) with live submission, statistics, and a wishes list. |
| **Wishes** | Guestbook with a stats header (count + Hadir/Tidak Hadir/Masih Ragu), initials avatars, reply/edit/delete, scrollable list, and pagination. |
| **Music** | Floating click‑to‑toggle background audio (upload, link, or YouTube) with custom play/pause icons and smooth animation. |
| **Timeline** | A vertical "love story" timeline (repeatable items with image). |
| **WhatsApp** | A button that opens a WhatsApp chat with a prefilled message (auto‑normalizes the number). |
| **Copy Text** | Copy‑to‑clipboard button (e.g. bank account / e‑gift). |
| **Add to Calendar** | Generates a Google Calendar "add event" link. |
| **QR Code** | QR for the page URL, guest name (`?to=`), or custom text. |

## 🎨 Template Library

- Upload Elementor template/page **JSON exports** from **Wedding Widget → Templates** (one or many at once).
- Organize templates with **categories** (e.g. *Adat*, *Flower*, *Minimalist*) and optional **preview thumbnails**.
- Inside the Elementor editor, click the **Wedding Widget (heart) icon** in the canvas add‑section row to open a dedicated library modal — **search by name**, filter by **category tabs**, and **insert** into the page.
- Templates are stored privately and do **not** clutter Elementor's default "My Templates".

## 🚀 Installation

1. Download/clone this repository and zip the `wedding-widget` folder (or use a release zip).
2. In WordPress: **Plugins → Add New → Upload Plugin**, choose the zip, **Install** and **Activate**.
3. Make sure **Elementor** is installed and active.
4. Edit a page with Elementor — the widgets appear under the **Wedding Widget** category.

## 📋 Requirements

- WordPress 5.6+
- PHP 7.0+
- Elementor 3.0+

## 🔐 Privacy & Security Notes

- **RSVP / Wishes** submissions are nonce‑protected and sanitized server‑side. Entries are public by design (guests are not logged in) and auto‑approved — pair with an anti‑spam/moderation plugin if needed.
- Submissions store the visitor's **IP address** (shown on the admin Comments screen). This is personal data — cover it in your privacy policy.
- Authors can edit/delete **their own** entry via an ownership token stored in their browser; users who can moderate comments can manage all.
- The **QR Code** widget renders via the external service `api.qrserver.com`; the encoded value is sent to that service by the visitor's browser. Use "Custom Text" or avoid the widget if you don't want external requests.
- Template uploads are restricted to users who can manage options, limited to `.json` (max 3 MB) and image thumbnails, and validated as Elementor export data.

## 🛠️ Development Notes

- The in‑editor template launcher/insert integrates with Elementor 3.x editor internals (`$e.run`, document containers). A future Elementor version may require small adjustments.

## 📄 License

GPL‑2.0‑or‑later.
