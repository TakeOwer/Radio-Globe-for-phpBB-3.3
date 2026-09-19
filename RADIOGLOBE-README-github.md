# Radio Globe for phpBB 3.3

![Version](https://img.shields.io/badge/version-1.1.2-105080)
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
- Central reticle: whatever place sits under the crosshair is shown and can be opened with one click. Clicking a dot opens the place and immediately starts its first station.
- Optional automatic rotation of the globe while idle.
- **Search** by station name, genre, country, region or city, with instant results.
- **Search by coordinates**: type a position and the globe flies there, marks the point with a ring and lists the nearest stations sorted by distance (in km). Supported formats include:
  - `45.4642, 9.19` · `45.4642 9.19` · `45,4642 9,19` · `-33.86 151.21`
  - `45.46N 9.19E`
  - `45°27'51"N 9°11'24"E` · `N 45° 27.85' E 9° 11.4'`
- **Stations without coordinates**: about four in five Radio Browser stations have no coordinates. Radio Globe can place them in their region (where the stations with coordinates of the same region are) or, when the region is unknown, in the centre of their country. They are shown as "*Region* (region)" and "*Country* (whole country)". With this option a typical import grows from about 9,000 to more than 33,000 stations on the globe.

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
| Can delete anyone's station comments | Moderator | Full moderator and full admin roles |

The easiest way to change them is **ACP → Extensions → Radio Globe → Authorised groups**: one row per group with three checkboxes (listen, favourites, comment). The checkboxes write to the regular phpBB permissions, so you can also manage them in **ACP → Permissions**.

> If a group uses a permission **role** (for example "Standard Features"), the change is applied to the role so the role assignment is kept. It therefore also applies to every other group using that role. The page lists the roles that were changed.

Guests and bots can listen (if allowed) but cannot use favourites or comments.

---

## 🎧 Using the globe and the player

- **Explore:** drag to rotate, scroll or pinch to zoom. The place under the central reticle is shown at the top. Click it, or click any dot, to open the list of its stations; the first one starts playing.
- **Search:** type in the search box to find stations by name, genre, country, region or city. Type coordinates to fly to a point and list the nearest stations with their distance.
- **Player:** play/pause, previous/next inside the current list, shuffle, repeat (reconnects automatically if the stream drops), volume (arrow keys work on the slider), mini player and full screen. Press **Esc** to close panels and full screen.
- **Queue, favourites and history:** open the list button in the player. Click the heart to add or remove a favourite.
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
| The globe does not show at all | The browser needs WebGL. Try another browser or enable hardware acceleration. |

---

## 🗑️ Uninstalling

1. **ACP → Customise → Manage extensions → Disable** Radio Globe. Temporary update files in `store/radioglobe/` are removed.
2. To remove everything, click **Delete data**. This drops the Radio Globe tables (stations, places, favourites, comments), settings, permissions and ACP modules. **This cannot be undone.**
3. Delete the folder `ext/salvocortesiano/radioglobe/` and purge the cache.

---

## 🔧 Technical notes

- **Database tables:** `phpbb_radioglobe_stations`, `phpbb_radioglobe_places`, `phpbb_radioglobe_favorites`, `phpbb_radioglobe_comments` (with your table prefix).
- **Routes:** the page is `/radio`; the data endpoints are under `/radio/data/…` (places, place, search, station, now playing, favourites, comments). All write requests are POST and protected by a link hash.
- **External services:** Radio Browser (station list, only during updates), iTunes Search API (covers, server side, cached), Esri World Imagery tiles (only with the satellite look, loaded by the visitor's browser), stream servers (ICY/AzuraCast titles, server side, cached).
- **Browser storage:** the player keeps its state and the listening history in `localStorage` (`radioglobe.state.v1`, `radioglobe.history.v1`). Favourites and comments are stored in the board database.
- **Assets** are cache-busted automatically when the CSS/JS files change.

---

## 📜 Changelog

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

---

**License:** [GPL-2.0-only](LICENSE)
**Author:** © 2026 Salvo Cortesiano ([netshadows.de](https://netshadows.de))
