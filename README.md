# NextGen Builds

Eigenes WordPress-Theme und eigene Plugins für [nextgen-builds.ch](https://nextgen-builds.ch). Im Repo liegt kein WordPress-Core. Lokal läuft eine Docker-Umgebung, ein Push auf `main` lädt nur die eigenen Ordner auf den Server.

Das Theme `nextgen-base` ist ein Block-Theme (Full Site Editing): kein Page-Builder, kein jQuery, keine Schrift von einem Google-Server. Die Vorlage `nextgen-starter` ist zum Kopieren gedacht.

## Struktur

```text
├── .github/workflows/deploy.yml   Deployment nach GitHub
├── .wp-env.json                   Lokales WordPress
├── themes/nextgen-base/           Eigenes Theme
├── plugins/nextgen-starter/       Plugin-Vorlage
├── .gitignore
└── README.md
```

Farben, Abstände und Schriftgrössen stehen in `themes/nextgen-base/theme.json` und werden zu CSS-Variablen, zum Beispiel `--wp--preset--color--primary`, `--wp--preset--color--accent`, `--wp--preset--color--background`, `--wp--preset--color--text`.

## Voraussetzungen

Auf Arch Linux:

- Docker (`sudo systemctl enable --now docker`, Benutzer in der Gruppe `docker`, danach neu anmelden)
- Node.js und npm (für `npx`)
- Git

PHP auf dem eigenen Rechner ist nicht nötig. Die lokale Seite läuft im Container, die Syntaxprüfung läuft in GitHub Actions.

## Repo und erster Push

Das GitHub-Repo [oggy098/0815](https://github.com/oggy098/0815) ist leer. Im Projektordner:

```bash
git init
git branch -M main
git add .
git status
git commit -m "Grundgerüst für Theme, Plugins und Deployment"
git remote add origin https://github.com/oggy098/0815.git
git push -u origin main
```

`git status` vor dem Commit prüfen: keine `.env`, keine Schlüssel, keine Logs.

Der Push auf `main` startet das Deployment. Zuerst die Secrets eintragen, sonst bricht der Workflow vor dem Upload ab und es wird nichts verändert. Danach unter **Actions → Deploy** den Lauf neu starten.

## GitHub Secrets

Im Repo: **Settings → Secrets and variables → Actions → New repository secret**.

Pflicht für Methode A (FTP/FTPS):

| Secret | Beispiel | Bedeutung |
| --- | --- | --- |
| `FTP_SERVER` | `ftp.nextgen-builds.ch` | Nur der Hostname, ohne `ftp://` |
| `FTP_USERNAME` | `benutzer` | FTP-Benutzer |
| `FTP_PASSWORD` | *(Passwort)* | FTP-Passwort |
| `FTP_PROTOCOL` | `ftps` | `ftp`, `ftps` oder `ftps-legacy` |
| `REMOTE_WP_PATH` | `public_html/` | WordPress-Wurzel auf dem Server, **mit** Slash am Ende |

Optional:

| Secret | Wann |
| --- | --- |
| `FTP_PORT` | Nur wenn der Hoster nicht Port 21 nutzt. Implizites FTPS (`ftps-legacy`) ist oft `990`. |
| `SSH_HOST`, `SSH_USER`, `SSH_KEY` | Nur für Methode B. `SSH_KEY` ist der private Schlüssel, inklusive der Zeilen `BEGIN` und `END`. |

`REMOTE_WP_PATH` ist der Ordner, in dem `wp-config.php` und `wp-content` liegen, so wie ihn das FTP-Konto sieht.

- Login landet schon in WordPress: `./`
- Login landet eine Ebene darüber: `public_html/` oder `/www/`

Slash am Ende ist Pflicht. Der Workflow hängt selbst `wp-content/themes/nextgen-base/` bzw. `wp-content/plugins/<name>/` an.

`FTP_PROTOCOL=sftp` funktioniert mit dieser Action nicht. [FTP-Deploy-Action v4](https://github.com/SamKirkland/FTP-Deploy-Action) spricht FTP und FTPS. Wer nur SSH hat, nutzt Methode B.

Passwörter nur als Secret speichern, nie in Dateien im Repo.

## Deployment

Auslöser:

- Push auf `main`
- Manuell: **Actions → Deploy → Run workflow**. Der Schalter «Nur anzeigen, nichts hochladen» verbindet sich mit dem Server und listet die Änderungen, schreibt aber nichts.

Ablauf:

1. PHP-Syntax (`php -l`) für alle PHP-Dateien. Bei einem Fehler gibt es keinen Upload.
2. `themes/nextgen-base/` geht nach `wp-content/themes/nextgen-base/`.
3. Jeder Ordner `plugins/nextgen-*` geht nach `wp-content/plugins/<gleicher Name>/`.

Es werden nur geänderte Dateien hochgeladen. `dangerous-clean-slate` ist `false` und muss `false` bleiben.

Was der Workflow nicht tut:

- Er löscht keine anderen Themes, keine anderen Plugins, keine Uploads und nicht `wp-config.php`.
- Er aktiviert Theme oder Plugin auf dem Server nicht. Das bleibt ein einmaliger Klick im Backend.
- Innerhalb des eigenen Theme- oder Plugin-Ordners entfernt er Dateien, die er früher selbst hochgeladen hat und die im Repo nicht mehr existieren.

`.git`, `.github`, `node_modules`, `vendor`, `README.md`, `.wp-env.json` und `.env` liegen nicht in diesen Ordnern und werden zusätzlich per Exclude ausgeschlossen.

Auf dem Server entsteht in jedem hochgeladenen Ordner eine `.ftp-deploy-sync-state.json`. Darin stehen Dateinamen und Prüfsummen, keine Passwörter. Die Datei nicht löschen, sonst lädt der nächste Lauf alles neu hoch.

Wenn FTPS am Zertifikat scheitert: im Workflow bei beiden FTP-Schritten `security: loose` ergänzen, oder `ftps-legacy` mit Port `990` versuchen.

### Methode B (SSH und rsync)

In `.github/workflows/deploy.yml` sind die rsync-Schritte auskommentiert. Nur nutzen, wenn der Hoster SSH erlaubt. Dann die FTP-Schritte auskommentieren und die rsync-Schritte aktivieren. `--delete` gilt nur für den jeweiligen Theme- oder Plugin-Ordner. Den Zielpfad nie auf die WordPress-Wurzel oder auf ganz `wp-content/` stellen.

## Lokal testen

Im Projektordner, Docker muss laufen. Der erste Start lädt die WordPress- und Datenbank-Images und dauert einige Minuten.

```bash
npx @wordpress/env start
npx @wordpress/env stop
```

| | |
| --- | --- |
| Seite | http://localhost:8888 |
| Backend | http://localhost:8888/wp-admin |
| Benutzer | `admin` |
| Passwort | `password` |

Das Passwort gilt nur für diese lokale Instanz.

`.wp-env.json` macht Folgendes:

- aktuelle stabile WordPress-Version, PHP 8.3
- Theme `nextgen-base` wird eingebunden und aktiviert
- der Ordner `plugins/` ersetzt `wp-content/plugins`, dadurch sind alle eigenen Plugins sichtbar
- nach dem Start werden die Plugins aktiviert
- `WP_DEBUG`, `WP_DEBUG_LOG`, `WP_DEBUG_DISPLAY` und `SCRIPT_DEBUG` sind an

Weitere Befehle:

```bash
npx @wordpress/env start --update   # WordPress im Container aktualisieren
npx @wordpress/env logs             # PHP- und Docker-Logs
npx @wordpress/env run cli cat wp-content/debug.log
npx @wordpress/env destroy          # Container und lokale Daten löschen
```

Dokumentation: [@wordpress/env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/).

Sprache der lokalen Seite: **Einstellungen → Allgemein → Deutsch (Schweiz)**.

## Neues Plugin aus der Vorlage

1. Ordner kopieren: `plugins/nextgen-starter` → `plugins/nextgen-mein-plugin`.
2. Hauptdatei umbenennen: `nextgen-mein-plugin.php`.
3. Im neuen Ordner ersetzen:
   - `NextGen\Starter` → `NextGen\MeinPlugin`
   - `nextgen-starter` → `nextgen-mein-plugin`
   - `nextgen/starter` → `nextgen/mein-plugin`
   - Option `nextgen_starter_settings` → eigenen Optionsnamen
4. Plugin-Header (Name, Beschreibung) anpassen.
5. `npx @wordpress/env start` erneut ausführen. Der Ordner wird gemountet und das Plugin aktiviert, sofern der Ordnername mit `nextgen-` beginnt und nur Buchstaben, Ziffern, Punkt, Unterstrich und Bindestrich enthält.

Die Vorlage enthält:

- Einstellungsseite unter **Einstellungen → NextGen Starter** (Nonce und `manage_options` über die Settings API, Eingabe wird bereinigt)
- Shortcode `[nextgen_starter]` und `[nextgen_starter message="Eigener Text"]`
- Block **NextGen Starter** im Inserter, ohne npm-Build (`block.json`, `index.js`, `index.asset.php`, `render.php`)
- Aktivierung legt die Option an, Deaktivierung löscht sie nicht, Löschen des Plugins entfernt sie über `uninstall.php`

## Theme aktivieren

Lokal aktiviert wp-env das Theme. Auf dem Server einmalig nach dem ersten erfolgreichen Upload:

1. Anmelden unter https://nextgen-builds.ch/wp-admin
2. **Design → Themes → NextGen Base → Aktivieren**
3. **Design → Editor**, um Templates, Navigation und Stile zu bearbeiten
4. **Einstellungen → Allgemein → Deutsch (Schweiz)**
5. **Plugins → NextGen Starter → Aktivieren**, falls die Vorlage auf dem Server genutzt wird

Dunkelmodus: im Website-Editor **Stile → Dunkel**. Die helle Palette bleibt der Standard. Die Umschaltung folgt nicht automatisch dem Betriebssystem, damit eine gewählte helle Darstellung hell bleibt.

Vorlagen im Inserter, Kategorie **NextGen**: Hero, Text und Bild, Kontakt-Aufruf.

Die Schrift ist der System-Stack des Geräts. Es gibt keinen Request an Google. Für eine eigene Schrift die Lizenz prüfen (zum Beispiel SIL OFL), die `.woff2` nach `themes/nextgen-base/assets/fonts/` legen und in `theme.json` unter `fontFamilies` eintragen:

```json
{
  "slug": "inter",
  "name": "Inter",
  "fontFamily": "Inter, sans-serif",
  "fontFace": [
    {
      "fontFamily": "Inter",
      "fontWeight": "400",
      "fontStyle": "normal",
      "src": ["file:./assets/fonts/inter-400.woff2"]
    }
  ]
}
```

`file:./` liefert die Datei von der eigenen Domain.
