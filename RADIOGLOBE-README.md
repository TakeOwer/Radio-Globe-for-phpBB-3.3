# Radio Globe for phpBB 3.3

![Version](https://img.shields.io/badge/version-1.10.1-105080)
![phpBB](https://img.shields.io/badge/phpBB-3.3.x-377a33)
![PHP](https://img.shields.io/badge/PHP-%3E%3D7.4-377a33)
![License](https://img.shields.io/badge/license-GPL--2.0--only-7f7f7f)

Explore tens of thousands of live radio stations from all over the world on an interactive 3D globe, Radio Garden style, and listen to them with a modern Spotify-like player that follows your members across the whole board.

Station data comes from [Radio Browser](https://www.radio-browser.info/), a free open-data directory. It is downloaded by the board and stored in its own database: visitors never query the external service directly.

---

## Contents

- [Features](#-features)
- [What's new](#-whats-new)
- [Requirements](#-requirements)
- [Installation](#-installation)
- [Updating from a previous version](#-updating-from-a-previous-version)
- [Configuration](#%EF%B8%8F-configuration)
- [Permissions](#-permissions)
- [Using the globe and the player](#-using-the-globe-and-the-player)
- [Station updates (sync and cron)](#-station-updates-sync-and-cron)
- [Troubleshooting](#-troubleshooting)
- [Uninstalling](#-uninstalling)
- [Technical notes](#-technical-notes)
- [Changelog](#-changelog)
- [Credits](#-credits)

---

## 🌟 Features

### The 3D globe (`app.php/radio`)
- Interactive 3D globe powered by [globe.gl](https://github.com/vasturiano/globe.gl), with stations grouped by place (city or area).
- **Fixed-size dots**, Radio Garden style: dots stay small at every zoom level and spread apart as you zoom in, instead of growing and covering each other. Classic 3D markers are still available.
- **Custom dot colours** chosen from the ACP with a colour picker, plus four colouring modes: shades of the chosen colour, solid colour, heat map (blue → red by number of stations), or one colour per country. The atmosphere glow and the "now playing" ring follow the chosen colour.
- Four globe looks: **Dark** (lightweight), **Earth at night**, **Blue Marble** and **Detailed satellite** (Esri World Imagery tiles that get sharper as you zoom in).
- Central reticle: whatever place sits under the crosshair is shown and, when the reticle turns green, its first station starts playing. Clicking a dot opens the place and immediately starts its first station.
- Optional automatic rotation of the globe while idle.
- **Search** by station name, genre, country, region or city, with instant results.
- **Search by coordinates**: type a position and the globe flies there, marks the point with a ring and lists the nearest stations sorted by distance (in km). Supported formats include:
  - `45.4642, 9.19` · `45.4642 9.19` · `45,4642 9,19` · `-33.86 151.21`
  - `45.46N 9.19E`
  - `45°27'51"N 9°11'24"E` · `N 45° 27.85' E 9° 11.4'`
- **Stations without coordinates**: about four in five Radio Browser stations have no coordinates. Radio Globe can place them in their region (where the stations with coordinates of the same region are), in the city named in their region field, name or tags ("NRJ Lyon", "Radio Bahía Blanca", "Tucson AZ": about 34,000 cities from GeoNames, joining the dot of that city), in the region named in their name ("Antenne Bayern") or, as a last resort, in the centre of their country. They are shown as "*Region* (region)" and "*Country* (whole country)". With this option a typical import grows from about 9,000 to more than 33,000 stations on the globe.

### The player
- Player bar fixed at the bottom of the page with station logo or album cover, now-playing title, play/pause, previous/next, shuffle and repeat (automatic reconnection when a stream drops).
- Volume slider (also works with the arrow keys) and mute.
- **Picture-in-Picture mini player** (Document Picture-in-Picture, on supporting browsers) and **full-screen mode**.
- **Player across the board**: the player can stay visible on every forum page and resumes the station after each page change.
- Adjustable **transparency** of the player bar (10–100 %), with a light blur, and a live preview in the ACP.
- Integration with the operating system media controls (Media Session API): media keys, lock screen and notification area show station and title and can play, pause and skip.
- Streams that the browser cannot play on an HTTPS page (mixed content) are clearly flagged instead of failing silently.

### Now playing and covers
- The board reads the **ICY metadata** of the stream (`Artist - Title`) and caches it for 25 seconds per station.
- **AzuraCast** stations are recognised automatically and read through their public now-playing API, which is more reliable than ICY.
- Placeholder titles sent by many stations (such as "Unknown", "Tag1", the station name itself, empty "-" titles…) are filtered out.
- **Album covers** are looked up on the iTunes Search API from the now-playing title and cached for 7 days. The station logo is used when nothing is found.

### "Is listening" notices
- When a member starts a station, a notice appears at the **top right of every board page**: "**Founder** is listening to: *station name* ♪ *now-playing title*".
- The user name is shown in the **colour of their group** and links to their profile.
- The notice fades in, closes by itself after **5 seconds** (configurable from 2 to 30) with a fade-out, and stays open while the mouse is over it. A × button closes it at once.
- **Click the notice to listen to the same station**: it starts in the player, or the globe page opens on that station.
- Fully responsive: on phones it spans the screen width at the top.
- Shown to everybody who can listen to the radio, on every page (it also works when "Player across the board" is off). Your own listening is never shown to you.
- Privacy: only groups with the permission **"Their listening is shown to others"** are announced (Registered users by default).
- Lightweight: each open page checks for new listeners every 15 seconds, only while the tab is visible. Browser tabs share what has already been shown, so a notice does not repeat on every tab or page change. The same station is not announced again within 30 minutes, and quick station changes (next, next…) produce a single notice.

### Members
- **Favourites** (a personal playlist), stored in the board database and available on every device.
- **Listening history** (the last 30 stations) and a **queue** of what plays next.
- **Station comments**, with load-more paging, length limit, flood protection and deletion by the author or by moderators. Ctrl+Enter sends a comment.
- Favourites and comments of deleted users are removed automatically.

### Administration
- Full ACP module with four pages: **Settings**, **Authorised groups**, **Station update** and **Comments**.
- Granular phpBB permissions for listening, favourites, comments and comment moderation.
- Manual station update with a step-by-step progress bar, plus an automatic update at the hours you choose.
- Comment moderation with bulk delete.
- English and Italian translations.

---

## 🆕 What's new

| Version | Changes |
|---|---|
| **1.10.1** | Fix: with more than one board tab open, two players could play at the same time and pause only stopped the one on screen. Pause and close now apply to every tab, and resuming when changing page can be switched off in the ACP. |
| **1.10.0** | Red bin next to every station: administrators and moderators can take dead, silent or wrong stations out of the board list. The next station update from the ACP brings them back. |
| **1.9.1** | Desktops: "Near me" now says clearly when the position does not come from GPS (it is the provider's city) and offers "Not my city", which explains how to set the right one in three steps. |
| **1.9.0** | "Near me" is much more accurate: high accuracy is requested and the best reading is used, the accuracy is shown, and a location chosen by hand can be saved ("Use as my location"), which also works on desktops without GPS. |
| **1.8.3** | Fix: on phones the globe could be drawn off to one side after a resize; it now always fills its area and the size is re-checked after every change. |
| **1.8.2** | Check-up report: files without an extension (LICENSE) are no longer reported as changed after an FTP upload in text mode. |
| **1.8.1** | "Near me" now also starts the nearest station, like the reticle does. |
| **1.8.0** | "Near me" button: the globe flies to your city and lists the nearest stations (automatic on opening when the browser already has the permission). The search box shows the coordinates of the place under the reticle. Fix: on phones, pinching the globe no longer zooms the page (only half the globe was visible afterwards). |
| **1.7.0** | New ACP tab "Check-up report": a complete check of the extension (environment, files, downloaded files folder, database, addresses, stations, cities, connectivity, real tests) with a progress bar, a coloured summary and "Copy the report". |
| **1.6.0** | The city list warns when it is old (on the Cities tab and the ACP main page, after 6 months by default) and can update itself through the phpBB scheduled tasks. |
| **1.5.0** | New ACP tab "Cities (GeoNames)": downloads the updated city list from GeoNames with a progress bar, checks it and uses it instead of the included one; one click goes back to the included list. |
| **1.4.1** | Text fixes: user names with & or ' in the "is listening" notices, song titles sent in Windows-1252 / ISO-8859-1 or double-encoded, broken titles hidden, station names escaped in the ACP comments page. |
| **1.4.0** | Stations without coordinates are placed much more precisely: the city is recognised in the region field, name or tags (about 34,000 cities from GeoNames), so about 3,200 more stations leave the crowded "whole country" dots and join the dot of their city or region. |
| **1.3.0** | The player cover spins for about 3 seconds at a chosen interval (seconds, minutes or hours; 3D or flat, with a "Try" button in the ACP). The "is listening" notice can be repeated every N minutes while the user keeps listening to the same station. |
| **1.2.1** | Radio Garden style tuning: when the central reticle turns green over a place, its first station starts playing by itself, on desktop and on phones/tablets (iPhone and iPad included). |
| **1.2.0** | New "is listening" notices: "*User* is listening to: *station* ♪ *title*" at the top right of every page, with the group colour, fade in/out, auto-close after 5 s, click to listen. New ACP options and new permission. |
| **1.1.2** | Dot colour chosen from the ACP (colour picker, 9 quick colours, hex field, live preview) and four colouring modes: shades, solid, heat map, one colour per country. Atmosphere and "now playing" ring follow the chosen colour. |
| **1.1.1** | **Stability fix**: the board can no longer crash with `RouteNotFoundException` when the phpBB router cache is out of date after uploading the files. The player and the menu link are simply hidden until the cache is purged. |
| **1.1.0** | Stations without coordinates placed in their region or country (new ACP option). Search by coordinates with a ring on the globe and results sorted by distance. |
| **1.0.9** | Much better now-playing titles: AzuraCast API support, placeholder titles filtered out, more compatible User-Agent for ICY requests. |
| **1.0.8** | Radio Garden-style fixed-size dots, detailed satellite globe (Esri World Imagery), new "Marker style" option. |

See the full [changelog](#-changelog) below.

---

## 📋 Requirements

| | |
|---|---|
| **phpBB** | 3.3.0 or newer (tested on 3.3.17) |
| **PHP** | 7.4 or newer (tested on 8.2) with the JSON extension |
| **PHP cURL** | Strongly recommended: required for now-playing titles and covers. The station download also works without it. |
| **Writable folder** | `store/radioglobe/` (created automatically inside the board `store/` folder) for the temporary update files |
| **Browser** | Any modern browser with WebGL for the globe. The player works everywhere. |

---

## 🚀 Installation

1. Download the package and extract it. You get a folder called `radioglobe`.
2. Upload it to your board so that the path is:
   ```
   ext/salvocortesiano/radioglobe/
   ```
   (the file `composer.json` must be at `ext/salvocortesiano/radioglobe/composer.json`).
3. Make sure the board `store/` folder is writable by the web server.
4. Go to **ACP → Customise → Manage extensions** and click **Enable** next to **Radio Globe**.
5. Purge the cache: **ACP → General → Purge the cache**.
6. Go to **ACP → Extensions → Radio Globe → Station update** and click **Update stations now**. Keep the page open until the bar reaches 100 % (it usually takes a few minutes).
7. Open `https://your-board/app.php/radio` (a **Radio** link is also added to the board menu).

By default guests and registered users can listen, registered users can also use favourites and comments, and moderators/administrators with full roles can delete comments. You can change all of this from the ACP (see [Permissions](#-permissions)).

---

## 🔄 Updating from a previous version

Always follow this order. It keeps all stations, favourites, comments and settings:

1. **ACP → Customise → Manage extensions → Disable** Radio Globe.
   ⚠️ Do **not** click "Delete data": that would erase stations, favourites and comments.
2. Upload the new files over `ext/salvocortesiano/radioglobe/`, overwriting the old ones.
3. **Purge the cache** (ACP → General → Purge the cache), or delete the contents of `cache/production/`.
4. **Enable** Radio Globe again. This runs any database update included in the new version (for example the new settings of 1.1.0 and 1.1.2).
5. Purge the cache once more and reload the board.
6. If the new version changes how stations are imported (such as 1.1.0), run **Station update → Update stations now**.

> **Why the cache matters:** phpBB keeps the list of extension pages (routes) in a cached file. If new files are uploaded but the cache is not purged, that list can be out of date. Before 1.1.1 this could stop the whole board with a `RouteNotFoundException`. From 1.1.1 the board keeps working, but the player stays hidden until the cache is purged.

---

## ⚙️ Configuration

All options are in **ACP → Extensions → Radio Globe → Settings**.

### Stations and filters
Filters apply from the next station update.

| Option | Default | Description |
|---|---|---|
| HTTPS streams only | Yes | Recommended when the board uses HTTPS: browsers block HTTP audio on secure pages. If disabled, HTTP stations are listed but marked as not playable on HTTPS pages. |
| Exclude HLS streams | Yes | HLS streams (`.m3u8`) do not play natively in every browser. |
| Include stations without coordinates | Yes | Places stations without coordinates in their region or country (see [Features](#the-3d-globe-appphpradio)). Raising the maximum number of stations to 40,000 is recommended. |
| Genres to include | *(empty)* | One per line or comma separated (e.g. `rock, jazz, news`). A station is included if one of its tags contains one of the words. Empty = every genre. |
| Minimum bitrate | 0 | In kbps. 0 = no limit. |
| Maximum number of stations | 30,000 | The most listened stations are imported first. Range 500–100,000. On shared hosting stay below 30,000–40,000. |
| Place grouping | 25 | Size of the area that becomes a single dot, in hundredths of a degree: 25 ≈ 25 km (a city), 100 ≈ 100 km. |
| Radio Browser server | *(empty)* | Leave empty to pick a working server automatically (recommended). |

### Automatic update
| Option | Default | Description |
|---|---|---|
| Automatic update enabled | Yes | Uses phpBB scheduled tasks (cron), which are triggered by board visits. |
| Start hours | 04:00, 16:00 | One or more hours, in the board time zone. Quick buttons: every 24, 12 or 6 hours. |

### Player and globe
| Option | Default | Description |
|---|---|---|
| Player transparency | 100 % | Opacity of the player bar background, with a live preview. |
| Player across the board | Yes | The player stays visible on every page and resumes the station after a page change. If disabled it only appears on the globe page. |
| "Radio" menu link | Yes | Adds a link to the globe page in the board navigation. |
| Show the now-playing title | Yes | Reads ICY metadata / AzuraCast data (cached 25 s per station). |
| Look up covers | Yes | Album covers from the iTunes Search API (cached 7 days). |
| Globe look | Dark | Dark, Earth at night, Blue Marble, Detailed satellite. |
| Marker style | Fixed-size dots | Fixed-size dots (Radio Garden style) or classic 3D markers. |
| Dot colour | `#1ed760` (green) | Colour picker, hex field (`#rrggbb` or `#rgb`), 9 quick colours and "Reset to green". |
| Dot colouring | Shades | **Shades** of the chosen colour (lighter where there are more stations), **Solid colour**, **Heat map** (blue → red by number of stations) or **One colour per country**. A live preview shows the result before saving. |
| Globe auto-rotation | Yes | The globe turns slowly while nobody is using it. |

### "Is listening" notice
| Option | Default | Description |
|---|---|---|
| Show who is listening | Yes | Turns the notices on or off for the whole board. |
| Notice duration | 5 s | From 2 to 30 seconds. The notice stays open while the mouse is over it. |

Who is announced is controlled by the permission "Their listening is shown to others" (see [Permissions](#-permissions)).

### Station comments
| Option | Default | Description |
|---|---|---|
| Comments enabled | Yes | Turns the comment feature on or off for everybody. |
| Maximum comment length | 1,000 | Characters (50–5,000). |
| Comments loaded at a time | 20 | 5–100. |
| Minimum interval between comments | 15 s | Flood protection, 0–3,600 s. Does not apply to moderators. |

---

## 🔐 Permissions

Radio Globe adds a **Radio Globe** category to the phpBB permission system.

| Permission | Type | Default |
|---|---|---|
| Can listen to the radio (globe and player) | User | Guests, Registered users |
| Can add stations to favourites / playlist | User | Registered users |
| Can comment on radio stations | User | Registered users |
| Their listening is shown to others ("is listening") | User | Registered users |
| Can delete anyone's station comments | Moderator | Full moderator and full admin roles |

The easiest way to change them is **ACP → Extensions → Radio Globe → Authorised groups**: one row per group with four checkboxes (listen, favourites, comment, listening visible to others). The checkboxes write to the regular phpBB permissions, so you can also manage them in **ACP → Permissions**.

> If a group uses a permission **role** (for example "Standard Features"), the change is applied to the role so the role assignment is kept. It therefore also applies to every other group using that role. The page lists the roles that were changed.

Guests and bots can listen (if allowed) but cannot use favourites or comments.

---

## 🎧 Using the globe and the player

- **Explore:** drag to rotate, scroll or pinch to zoom. The place under the central reticle is shown at the top and, once the reticle turns green, its first station starts playing. Click it, or click any dot, to open the list of its stations; the first one starts playing.
- **Search:** type in the search box to find stations by name, genre, country, region or city. Type coordinates to fly to a point and list the nearest stations with their distance.
- **Player:** play/pause, previous/next inside the current list, shuffle, repeat (reconnects automatically if the stream drops), volume (arrow keys work on the slider), mini player and full screen. Press **Esc** to close panels and full screen.
- **Queue, favourites and history:** open the list button in the player. Click the heart to add or remove a favourite.
- **"Is listening" notices:** when someone starts a station a notice appears at the top right. Click it to listen too, click the name to open their profile, or close it with ×.
- **Comments:** open the comments of the playing station. **Ctrl+Enter** sends. Authors and moderators can delete comments.
- **Media keys:** the operating system media controls work while a station is playing.

---

## 🔁 Station updates (sync and cron)

The update downloads the station list from Radio Browser, applies the filters, imports it into the database and rebuilds the places on the globe. It runs in small steps, so it works on shared hosting without hitting PHP time limits.

- **Manual:** ACP → Radio Globe → **Station update → Update stations now**. A progress bar shows the three phases (download, import, building places). Keep the page open until 100 %. An interrupted update can be resumed or cancelled.
- **Automatic:** at the chosen hours, or on the first board visit after them, the phpBB cron starts the update and continues it step by step during the following visits.
- The Station update page shows the status, last update, active stations, places on the globe, duration, servers used, next automatic start and the last error, if any.
- Stations removed from Radio Browser are **deactivated, not deleted**: they stay in favourites and keep their comments. Deactivated stations without favourites or comments are deleted after 30 days.

---

## 🩺 Troubleshooting

| Problem | Solution |
|---|---|
| The board shows a blank page / `RouteNotFoundException … salvocortesiano_radioglobe_page` after uploading files | The router cache is out of date. Delete the contents of `cache/production/` (or purge the cache) and reload. Updating to **1.1.1 or newer** prevents the crash for good. |
| The player or the "Radio" link does not appear | Purge the cache. Check that the group has "Can listen to the radio". Check "Player across the board" if you expect it on every page. |
| The globe page says there are no stations | Run **Station update → Update stations now** and wait for 100 %. |
| "The store/radioglobe/ folder does not exist or is not writable" | Make `store/` writable by the web server (the extension creates `store/radioglobe/` itself). |
| No now-playing titles or covers | Enable the PHP cURL extension. Not every station sends metadata. |
| A station shows "not playable" / mixed content | It is an HTTP stream on an HTTPS board: browsers block it. Enable "HTTPS streams only" to hide such stations. |
| Changed filters have no effect | Filters apply from the next update: run **Update stations now**. |
| Settings page: the colour field refuses a value | Use `#rrggbb` or `#rgb` (e.g. `#ff3b3b` or `#f80`). Fixed in 1.1.2: upload `adm/style/radioglobe_settings.html` and purge the cache if you installed an early 1.1.2 build. |
| No "is listening" notices appear | Check ACP → Settings → "Show who is listening". The listener's group needs "Their listening is shown to others", the viewer's group needs "Can listen to the radio". You never see your own notice. Purge the cache after updating. |
| The globe does not show at all | The browser needs WebGL. Try another browser or enable hardware acceleration. |

---

## 🗑️ Uninstalling

1. **ACP → Customise → Manage extensions → Disable** Radio Globe. Temporary update files in `store/radioglobe/` are removed.
2. To remove everything, click **Delete data**. This drops the Radio Globe tables (stations, places, favourites, comments), settings, permissions and ACP modules. **This cannot be undone.**
3. Delete the folder `ext/salvocortesiano/radioglobe/` and purge the cache.

---

## 🔧 Technical notes

- **Database tables:** `phpbb_radioglobe_stations`, `phpbb_radioglobe_places`, `phpbb_radioglobe_favorites`, `phpbb_radioglobe_comments`, `phpbb_radioglobe_listening` (with your table prefix). The listening table only keeps the last hour.
- **Routes:** the page is `/radio`; the data endpoints are under `/radio/data/…` (places, place, search, station, now playing, favourites, comments, listen, listening). All write requests are POST and protected by a link hash.
- **External services:** Radio Browser (station list, only during updates), iTunes Search API (covers, server side, cached), Esri World Imagery tiles (only with the satellite look, loaded by the visitor's browser), stream servers (ICY/AzuraCast titles, server side, cached).
- **Browser storage:** the player keeps its state and the listening history in `localStorage` (`radioglobe.state.v1`, `radioglobe.history.v1`); the notices remember the last one shown in `radioglobe.toast.last`. Favourites and comments are stored in the board database.
- **Assets** are cache-busted automatically when the CSS/JS files change.
- The "is listening" notices read the now-playing title from the server cache only: they never open extra connections to the streams.

---

## 📜 Changelog

### 1.10.1
- Fix: the player resumes the station by itself when the page changes, but it did not tell the other tabs, so two tabs could play the same stream at the same time; and pause only stopped the tab on screen, so the sound kept coming from the other one with no way to stop it. Now every start is announced to the other tabs (which stop), and pause and close apply to all of them.
- Pause and close also stop any other audio element of the page, as a safety net.
- New ACP option **"Resume listening when changing page"** (on by default, Settings > Player and globe): with "No" the player stays paused after a page change and only starts when play is pressed.
- New migration `add_player_resume`.

### 1.10.0
- New: a **red bin** appears next to every station in the lists for users with the new permission "Can remove stations from the radio list" (administrators and moderators by default). It asks for confirmation and takes the station out of the board list for everyone.
- The station is not deleted, it is deactivated: its comments and favourites are kept, and the next station update from the ACP brings it back if Radio Browser still lists it (exactly like stations that disappear on their own).
- The place loses one station straight away: the counter above the list and the dot on the globe are updated, and the dot disappears when the place is left empty. The station counters of the board are updated too.
- If the removed station is the one playing, the player moves on to the next one in the queue.
- Every removal is written in the phpBB admin log.
- New migration `add_station_remove` (permission `m_radioglobe_stations`). New route `/radio/data/station/remove`.

### 1.9.1
- Desktops have no GPS, so the browser works the position out from the internet address and returns the provider's city (often hundreds of kilometres away). Now anything less accurate than 1.5 km is marked as "not a GPS reading" with an orange notice, instead of being presented as the user's city.
- New button **"Not my city"** next to the results: it clears the search, puts the cursor in the box and explains the three steps (type the city, click one of its stations, press the pin) to set the right position once and for all.
- Clicking a station in the search results now opens its place, where the pin "Use this place as my location" is. Before, the place page (and the pin) could only be reached from the globe.
- The pin is no longer offered on the first search result: searching "Roma" could return a station whose name merely contains those letters, hundreds of kilometres away, and saving it would have set the wrong position.

### 1.9.0
- "Near me" now asks the browser for high accuracy and keeps listening for a few seconds: the first answer is almost always the rough one from the network, the GPS one arrives later. The best reading is used (waiting at most 5 more seconds after the first answer, 12 seconds in total).
- The accuracy of the position is shown above the list ("accurate to about 18 m"). When it is worse than 25 km the notice turns orange and explains that such a rough position almost always comes from the internet address and can point at the provider's city, with instructions to correct it.
- New: **Use as my location**. From the "Near me" results, or with the pin button next to the play button of any place, the position can be saved in the browser. "Near me" then always uses it, without asking for the location permission: this is the only reliable way on a desktop, which has no GPS. "Detect again" goes back to the browser location.
- The extension has never used IP geolocation on the server side: it only uses the browser Geolocation API. The imprecision comes from the browser falling back to the internet address when GPS and Wi-Fi are not available.
- A missing translation for these new texts no longer breaks the globe page.

### 1.8.3
- Fix (phones): after a resize the globe could be drawn larger than its area and therefore off to one side, with the reticle left in the middle of the screen. The containers created by globe.gl and the canvas are now forced to fill the globe area (so the globe stays centred even while the drawing size is out of date), and the size is re-checked on window resize, orientation change, page zoom (visual viewport), when the page becomes visible again, at the end of every globe movement and every two seconds as a safety net.

### 1.8.2
- Fix (Check-up report): `LICENSE` was reported as "different from the original" although it was uploaded correctly. FTP clients uploading in text mode convert the line endings, and the check ignored that only for files with a known extension. Text files are now recognised by their content, so files without an extension are handled too. Binary files (images, fonts) are still compared byte by byte.

### 1.8.1
- "Near me": after the globe reaches your city, the nearest playable station starts straight away; the other nearby stations are in the queue, so previous/next move through them by distance. It happens only when the button is pressed: when the location is used automatically on opening the page, nothing starts by itself (browsers would block audio without a tap anyway).

### 1.8.0
- New: **Near me** button at the top right of the globe. The browser asks for the location (only the first time), the globe flies to it and the list shows the nearest stations with their distance. The coordinates are rounded to two decimals (about 1 km) before being searched, so the board never receives the exact position. When the browser has already been given the permission, this happens by itself when the globe page is opened (no surprise permission requests). Works only on HTTPS, as required by browsers.
- New: when the reticle stops on a place, or a dot is clicked, the search box shows its coordinates ("41.8687, 12.4725"). Clicking the box selects them, so typing starts a new search straight away.
- After a coordinate search (or Near me), turning the globe makes the reticle choose places again.
- Fix (phones): pinching the globe could also zoom the whole page (a finger on the button or on the texts over the globe, or Safari on iPhone, which uses its own "gesture" events); after zooming out the page stayed enlarged and shifted and only half of the globe was visible. The globe area now blocks page zoom and Safari gestures, while one-finger gestures stay free.
- Fix: zooming out stops at a sensible distance (4.5 globe radii instead of 100, where the globe became a speck).

### 1.7.0
- New ACP tab **Check-up report**. It starts by itself when the tab is opened (and again with "Check now") and checks one section at a time, with a progress bar:
  - **Environment**: extension, phpBB and PHP versions, cURL/TLS, mbstring, intl, zlib, zip, memory limit, execution time, server time.
  - **Extension files**: every file is compared with `checksums.json`, written when the package is built (missing files, files different from the original, files from old versions). Text files are compared without line endings, so an FTP upload in text mode does not count as a change.
  - **Downloaded files folder** (`store/radioglobe/`): path, writable, protected by `.htaccess`, free disk space, every file with size and date, leftovers of interrupted updates.
  - **Database**: tables and rows, migrations run, permissions, ACP tabs, comments and favourites of stations that no longer exist.
  - **Addresses and phpBB integration**: all 13 addresses of the extension (the check that finds a stale router cache), globe page address and main options.
  - **Radio stations**: last update, active stations, places, station limit reached, update stuck, last error, automatic update.
  - **Cities (GeoNames)**: list in use, number of cities, date and age, automatic update, last error, recognition test.
  - **Connectivity**: Radio Browser (servers and catalogue), GeoNames, iTunes and Esri when used, with response times.
  - **Real tests**: globe places, search by name and by coordinates, now-playing title read from a real stream, UTF-8 text repair.
- Coloured summary (all fine / warnings / errors) with counts and time, and "Copy the report" to paste it into a message.
- It writes nothing: it can be run at any time on a live board.
- New migration `add_report_module`. New files: `service/health_check.php`, `adm/style/radioglobe_report.html`, `language/*/health.php`, `checksums.json`.

### 1.6.0
- New: notice when the city list is old. When the list in use (included or downloaded) is older than the chosen age (1–60 months, default 6, counted from the date of the GeoNames data), a notice appears on the Cities (GeoNames) tab and on the ACP main page, with a link to the tab.
- New: optional automatic update (off by default). A phpBB scheduled task downloads the new list by itself in short steps during visits to the board, like the station update. On errors the previous list stays in use and it is retried the next day. A download started by hand and left half-way is also completed.
- The check does not contact GeoNames: GeoNames regenerates the file every night, so "is there a newer file?" would always be yes. The age of the list is read from its first lines, so it costs nothing.
- A lock keeps the scheduled task and the ACP from working on the same files at the same time.
- New migration `add_cities_auto` (settings `radioglobe_cities_notice`, `radioglobe_cities_max_months`, `radioglobe_cities_auto`, `radioglobe_cities_auto_last`, `radioglobe_cities_lock`). New files: `cron/update_cities.php`, `event/acp_listener.php`, `adm/style/event/acp_main_notice.html`.

### 1.5.0
- New ACP tab **Cities (GeoNames)**. The city list used to place stations without coordinates (1.4.0) can be updated from GeoNames: the file (about 3.4 MB) is downloaded in 512 KB pieces with a progress bar, extracted (also without the PHP zip extension, with CRC check), converted and checked. It replaces the list in use only when complete (at least 20,000 cities); on any error the previous list stays in use.
- The tab shows the list in use (included or downloaded), the date of the GeoNames data, the number of cities, the last download and the last error. "Back to the included list" deletes the downloaded copy.
- The downloaded list is kept in `store/radioglobe/cities.tsv`, so it survives extension updates. It also keeps all the alternative names of cities above 100,000 inhabitants, so new stations called for example "Radio Wien" or "Roma FM" are recognised too.
- Tested with the real GeoNames file: 15 steps, 3.6 seconds, 28.6 MB of memory at most; 33,782 of 33,789 cities identical to the included list (the other 7 are cities with the same name and population); same placement of all stations.
- New migration `add_cities_module` (ACP tab, settings `radioglobe_cities_updated`, `radioglobe_cities_count`, `radioglobe_cities_error`). New files: `service/city_update.php`, `adm/style/radioglobe_cities.html`.

### 1.4.1
- Fix: in the "is listening" notices, user names containing `&`, `'`, `"`, `<` or `>` were shown with HTML codes ("Tom &amp;amp; Jerry"); now they are shown as written.
- Fix: song titles from streams that do not send UTF-8 are converted as Windows-1252 (a superset of ISO-8859-1), so curly quotes, dashes and € are shown correctly; this also works without the mbstring extension.
- Fix: double-encoded texts ("DinÃ¡mica", "Donâ€™t") are repaired in station names, regions, tags and song titles, only when the result is valid UTF-8.
- Titles already broken at the source (two or more "�" characters) are not shown: the player shows the station name instead. Runs of "����" at the end of station names are removed.
- Fix: station names and countries are escaped in the ACP comments page (a name containing `<` or `&` could break the page).
- Checked on 756 real stream titles (all valid UTF-8 after the fixes, none with broken characters shown) and on the 37,386 stations of Radio Browser (4 texts repaired, nothing else changed).
- New file: `service/utf8_text.php`.

### 1.4.0
- Better placement of stations without coordinates. In order: region field matching a known region (as before), city in the region field ("Tucson AZ", "Krakow"), city in the name or tags ("NRJ Lyon", "Radio Mitre Mendoza", "Radio Bahía Blanca"), region in the name or tags ("Antenne Bayern", "Radio Sicilia"), and only then the whole country.
- Stations placed on a city join the dot of the stations with coordinates of that city. When the region field is empty it is filled with the city or region found, so the dot has the right name.
- US stations: the state written at the end ("Salem NH", "Columbia MO") picks the right city among cities with the same name.
- Guards against false matches: longer names win ("New York", not "York"), words common in radio names all over the world ("radio", "music", "hits", "university"...) and country names ("Deutschland", "Polska", "Europe") are never taken for places.
- Measured on the Radio Browser list of 19 September 2026 (34,311 stations after the default filters): 3,212 stations leave the "whole country" dots (1,315 from the region field, 1,752 from the name or tags, 145 regions from the name); 15,689 remain on their country.
- Fix: accents are now removed correctly also on servers without the PHP `intl` extension (ț, ș, ő, č, ă, Greek and Cyrillic letters...), for regions too.
- New files: `service/city_index.php`, `service/data/cities.tsv` (GeoNames cities15000, reduced, CC BY 4.0), `service/data/words.txt`. GeoNames is credited at the bottom of the globe page. No database changes: the new placement applies from the next station update.

### 1.3.0
- New: spinning cover. The cover or logo on the left of the player spins for about 3 seconds at regular intervals while a station is playing. ACP: on/off, interval (5 seconds to 24 hours, in seconds, minutes or hours), style (3D like a coin, or flat like a record) and a "Try" button on the live preview.
- New: repeated "is listening" notice. While a user keeps listening to the same station, the notice is shown again every N minutes (1–1440, default 10). Only actual listening time counts; pauses do not, and the count survives page changes. Changing station still shows the notice straight away, exactly as before.
- New migration: `add_cover_spin` (settings `radioglobe_cover_spin`, `radioglobe_cover_spin_every`, `radioglobe_cover_spin_style`, `radioglobe_toast_repeat`, `radioglobe_toast_repeat_minutes`).

### 1.2.1
- New: when the reticle turns green over a place, the first station of that place starts playing automatically (no click needed), like Radio Garden. It does not restart if you are already listening to a station of that place.
- Mobile: the audio is unlocked at the first touch, so automatic playback also works on iPhone and iPad.

### 1.2.0
- New: "is listening" notices at the top right of every board page, with the user name in the group colour, station, now-playing title and cover/logo.
- Fade in and fade out, automatic close after 5 seconds (2–30, configurable), pause while hovering, close button, click to listen to the same station.
- Responsive: full width at the top on phones.
- New ACP section "“Is listening” notice" (on/off, duration).
- New permission "Their listening is shown to others" (Registered users by default), also on the Authorised groups page.
- New migration: `add_listen_toast` (table `radioglobe_listening`, settings `radioglobe_toast_enabled`, `radioglobe_toast_seconds`, permission `u_radioglobe_announce`).

### 1.1.2
- New: dot colour selectable in the ACP with a colour picker, 9 quick colours, hex field and "Reset to green".
- New: dot colouring modes: shades of the chosen colour, solid colour, heat map, one colour per country.
- New: live preview of the dot colours in the ACP.
- The atmosphere glow and the "now playing" ring follow the chosen colour.
- Fix: the colour field in the ACP no longer rejects valid colours (phpBB template engine removed `{3}`/`{6}` from the validation pattern).
- Updated the "Stations and filters" description in the ACP.
- New migration: `add_dot_color` (settings `radioglobe_dot_color`, `radioglobe_dot_mode`).

### 1.1.1
- Fix: out-of-date router cache (`cache/production/url_generator.php`) no longer crashes every board page with `RouteNotFoundException`. Routes are generated safely and the player is skipped until the cache is purged.

### 1.1.0
- New: stations without coordinates placed in their region or country (option "Include stations without coordinates", on by default).
- New: search by coordinates in many formats, with a ring on the globe and results sorted by distance.
- Place titles show "(region)" / "(whole country)" for approximate places.
- New migration: `add_nogeo_stations`.

### 1.0.9
- Now-playing titles read from the AzuraCast API when available.
- Placeholder titles ("Unknown", "Tag1", station name, empty titles…) are filtered out.
- More compatible User-Agent for ICY requests (some servers refused the old one).

### 1.0.8
- Radio Garden-style fixed-size dots that spread apart when zooming in.
- New globe look: detailed satellite (Esri World Imagery) with attribution.
- New option "Marker style". New migration: `add_globe_markers`.

### 1.0.7 and earlier
- 3D globe, Spotify-like player, now-playing titles and covers, favourites, history, queue, comments, ACP settings, authorised groups, manual and automatic station updates, player transparency.

---

## 🛠️ Credits

- **Station data:** [Radio Browser](https://www.radio-browser.info/) (open data)
- **3D globe engine:** [globe.gl](https://github.com/vasturiano/globe.gl) by Vasco Asturiano (MIT License)
- **Satellite imagery:** [Esri World Imagery](https://www.arcgis.com/home/item.html?id=10df2279f9684e4a9f6a7f08febac2a9) (Source: Esri, Maxar, Earthstar Geographics and the GIS User Community)
- **Album covers:** [iTunes Search API](https://performance-partners.apple.com/search-api)
- **Country centres:** [mledoze/countries](https://github.com/mledoze/countries) (ODbL)
- **Cities:** [GeoNames](https://www.geonames.org/) cities with more than 15,000 inhabitants ([CC BY 4.0](https://creativecommons.org/licenses/by/4.0/)), reduced to name, country and coordinates in `service/data/cities.tsv`

---

**License:** [GPL-2.0-only](LICENSE)
**Author:** © 2026 Salvo Cortesiano ([netshadows.de](https://netshadows.de))
