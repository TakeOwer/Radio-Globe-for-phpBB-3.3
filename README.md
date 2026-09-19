# Radio Globe per phpBB 3.3

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
