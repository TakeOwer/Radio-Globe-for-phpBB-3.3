# Radio Globe per phpBB 3.3

# Radio Globe for phpBB 3.3

![Version](https://img.shields.io/badge/version-1.0.7-105080)
![phpBB](https://img.shields.io/badge/phpBB-3.3.17-377a33)
![PHP](https://img.shields.io/badge/PHP-8.2.33-377a33)
![License](https://img.shields.io/badge/license-GPL--2.0--only-7f7f7f)

Explore global live radio stations on an interactive 3D globe featuring a Radio Garden-style visualization and a modern, feature-rich Spotify-like media player.

## 🌟 Key Features

- **3D Globe Interface (`globe.gl`):** Grouped station markers by location, central reticle aiming, and instant filter/search by name, genre, or country.
- **Bottom Fixed Player:** Displays album artwork, live stream titles, shuffle, previous/next track, auto-reconnect repeat mode, volume control, Picture-in-Picture (PiP) mini-player, and full-screen mode.
- **Dynamic Metadata & Covers:** Real-time ICY stream track title parsing paired with automatic iTunes Search API artwork lookup.
- **User Features:** Favorites list (personal playlist), playback history, dynamic queue, and station commenting system.
- **Full ACP Control:** Comprehensive settings, granular permission management (listening, favorites, comments), manual database synchronization, and comment moderation tools.
- **Automated Cron Sync:** Automatic station updates executed at scheduled intervals (default: 04:00 & 16:00 forum time).

## 📋 Requirements

- **phpBB:** `^3.3.0`
- **PHP:** `>= 7.4`
- **PHP Extensions:** `cURL` (required for station sync and ICY stream title retrieval)

## 🚀 Installation

1. Copy the repository contents into `ext/salvocortesiano/radioglobe/`.
2. Go to **ACP** → **Customise** → **Manage extensions** and enable **Radio Globe**.
3. Verify that the `store/` folder has appropriate write permissions.
4. Go to **ACP** → **Extensions** → **Radio Globe** → **Station Updates** and click **Update Now**.
5. Access your radio portal at `app.php/radio`.

## 🛠️ Credits & Attribution

- **Data Source:** [Radio Browser](https://www.radio-browser.info/) (Open Data)
- **3D Globe Engine:** [globe.gl](https://github.com/vasturiano/globe.gl) by Vasco Asturiano (MIT License)

---

**License:** [GPL-2.0-only](LICENSE)  
**Author:** © 2026 Salvo Cortesiano ([netshadows.de](https://netshadows.de))

Radio di tutto il mondo su un globo 3D in stile Radio Garden, con un player in stile Spotify.

## Funzioni
- Globo 3D (globe.gl) con le stazioni raggruppate per luogo, mirino centrale, ricerca per nome/genere/paese.
- Player fisso in basso: copertina, titolo in onda, shuffle, precedente/successiva, ripeti (riconnessione automatica), volume, mini player (Picture-in-Picture), schermo intero.
- Titolo del brano letto dai metadati ICY dello stream e copertina cercata su iTunes Search API.
- Preferiti (playlist personale), cronologia, coda, commenti alle stazioni.
- ACP: impostazioni, gruppi autorizzati (ascolto, preferiti, commenti), sincronizzazione manuale, moderazione commenti.
- Cron: aggiornamento alle ore scelte (predefinito 04:00 e 16:00, fuso del forum).

## Installazione
1. Copia la cartella in `ext/salvocortesiano/radioglobe/`.
2. ACP → Personalizza → Gestione estensioni → abilita **Radio Globe**.
3. Controlla che `store/` sia scrivibile.
4. ACP → Estensioni → Radio Globe → **Aggiornamento stazioni** → "Aggiorna ora".
5. La pagina è su `app.php/radio`.

## Requisiti
phpBB 3.3.x, PHP 7.4+, estensione cURL (per sincronizzazione e titoli in onda).

## Crediti
- Dati: [Radio Browser](https://www.radio-browser.info/) (open data).
- Globo: [globe.gl](https://github.com/vasturiano/globe.gl) di Vasco Asturiano, licenza MIT.

Licenza: GPL-2.0-only — © 2026 Salvo Cortesiano, https://netshadows.de
