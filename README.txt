=== Wedding Widget ===
Contributors: you
Tags: elementor, wedding, countdown, rsvp, whatsapp, calendar
Requires at least: 5.6
Tested up to: 6.5
Requires PHP: 7.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A fresh, independent set of Elementor widgets for wedding invitation websites.

== Description ==

Registers the "Wedding Widget" category in Elementor with the following widgets,
all written from scratch (no third-party premium code):

* Countdown — counts down to a target date with styled day/hour/minute/second boxes.
* Cover — full-screen invitation cover with an "open" reveal animation and
  optional guest name pulled from the ?to= URL parameter.
* RSVP — attendance form (name, status, message) saved as WordPress comments,
  with live submission, statistics, and a wishes list.
* WhatsApp — a button that opens a WhatsApp chat with a prefilled message.
* Copy Text — copy-to-clipboard button (e.g. bank account / e-gift).
* Add to Calendar — generates a Google Calendar "add event" link.
* Music — floating click-to-toggle background audio (upload, link, or YouTube).
* Timeline — a vertical "love story" timeline (repeatable items with image).
* QR Code — renders a QR for the page URL, guest name, or custom text.
* Wishes — a guestbook form + list (shares storage with RSVP, attendance optional).

== Requirements ==

* Elementor 3.0.0 or higher.

== Installation ==

1. Zip the `wedding-widget` folder.
2. WordPress > Plugins > Add New > Upload Plugin > choose the zip > Install > Activate.
3. Edit a page with Elementor; the widgets appear under the "Wedding Widget" category.

== RSVP data ==

RSVP entries are stored as WordPress comments (type "ww_rsvp") on the page that
contains the widget, with the attendance status in the "ww_attendance" comment
meta. They are auto-approved. You can moderate or export them from
WordPress > Comments. To show data for a specific invitation, place the RSVP
widget on that invitation's page.

== Security notes ==

* RSVP / Wishes submissions are protected by a WordPress nonce and all fields
  are sanitized server-side. Submissions are public by design (guests are not
  logged in). If you need spam protection, pair it with a comment/anti-spam
  plugin or add moderation.
* The QR Code widget generates the image via the external service
  api.qrserver.com. The encoded value (page URL, guest name, or custom text)
  is sent to that third-party service by the visitor's browser. Default source
  is the page URL. Switch the data source to "Custom Text" if you prefer not to
  expose URLs/names, or avoid the widget if any external request is unacceptable.
* IP addresses: from v1.0.0, RSVP/Wishes submissions store the visitor's IP
  address (REMOTE_ADDR) so it can be shown on the admin Comments screen. This is
  personal data; ensure your privacy policy covers it. Entries created before
  this version have no stored IP.

== Admin Comments screen ==

In WordPress > Comments, RSVP/Wishes entries show:
* An initials avatar placeholder (no Gravatar/email is collected).
* The author name followed by an attendance icon — green check (Attending),
  red X (Not Attending), question mark (Maybe).
* The author IP address (shown by WordPress core, below the name).

== Dashboard & Templates ==

A "Wedding Widget" admin menu provides:
* Dashboard: an overview of the available widgets and templates.
* Templates: upload Elementor template/page JSON exports (with an optional
  preview thumbnail). Templates are stored in a private library and do NOT
  appear in Elementor's default "My Templates".

Inside the Elementor editor, a "Wedding Widget" launcher (heart icon) is added
to the canvas add-section row. Clicking it opens a dedicated template library
modal with search-by-name and thumbnails; selecting a template inserts it into
the current document.

Notes:
* The in-editor launcher/insert integration targets Elementor 3.x editor
  internals; if a future Elementor version changes them it may need adjusting.
* Uploads are restricted to users who can manage options, nonce-protected,
  limited to .json (max 3 MB) and image thumbnails (jpg/png/webp/gif, max 3 MB),
  and validated as Elementor export data.

== Changelog ==

= 1.0.0 =
* Initial release: Countdown, Cover, RSVP, WhatsApp, Copy Text, Add to Calendar,
  Music, Timeline, QR Code, Wishes (avatars, reply/edit/delete, stats header,
  pagination, admin Comments enhancements).
