<p align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="public/assets/logo/logo-dark.png">
    <img src="public/assets/logo/logo-light.png" alt="Open-FM" width="420">
  </picture>
</p>

# Open-FM

[![Lizenz: AGPL-3.0](https://img.shields.io/badge/Lizenz-AGPL--3.0-blue.svg)](LICENSE) [![PHP 8.4+](https://img.shields.io/badge/PHP-8.4%2B-777bb4.svg)](https://www.php.net/) [![Laravel 13](https://img.shields.io/badge/Laravel-13-ff2d20.svg)](https://laravel.com/) [![Filament 5](https://img.shields.io/badge/Filament-5-f59e0b.svg)](https://filamentphp.com/)

**Open-FM ist eine selbst gehostete Open-Source-Software, die Gebäudemanagement, Betreiberverantwortung, Projektsteuerung, Prüffristen und ein Service-Portal vor allem für Kommunen, aber auch für Unternehmen transparent in einer Anwendung vereint.** Barrierefreiheit (WCAG 2.1 AA) ist ein zentrales Entwurfsziel, alle Daten bleiben auf den eigenen Servern des Betreibers.

> **Beta (`1.0.0-beta.1`).** Der Funktionsumfang dieser Beta steht und ist mit 793 automatisierten Tests abgesichert (weitere 98 laufen nur mit installierten Plugins). Bis zum Release 1.0 können sich Datenmodell und Oberfläche noch ändern; jede Änderung am Datenbankschema kommt als Migration. Vor dem produktiven Einsatz bitte Backups einplanen und die Hinweise unter [Betrieb und Sicherheit](#betrieb-und-sicherheit) lesen. Rückmeldungen und Fehlerberichte sind ausdrücklich erwünscht.

## Inhalt

- [Warum Open-FM](#warum-open-fm)
- [Zielgruppen](#zielgruppen)
- [Architektur](#architektur)
- [Module](#module)
- [Installation](#installation)
- [Betrieb und Sicherheit](#betrieb-und-sicherheit)
- [Plugins](#plugins)
- [API](#api)
- [Entwicklung](#entwicklung)
- [Roadmap](#roadmap)
- [Mitwirken](#mitwirken)
- [Lizenz](#lizenz)

## Warum Open-FM

Kommunen und Unternehmen stehen vor der Herausforderung, Liegenschaften, Umbauprojekte, Prüfpflichten und Nachhaltigkeitsvorgaben transparent zu steuern. Open-FM soll teure Proprietärsysteme ablösen: eine barrierefreie, selbst gehostete Open-Source-Plattform, die

- **Gebäude-Strukturbäume** (Objekt, Gebäude, Geschoss, Raum) mit Anlagen, Störungen und Kosten verbindet,
- **Wartungs- und Prüfpläne** mit Katalogen (z. B. BetrSichV, DGUV, VDI 6022) automatisch in Aufträge und Fristen übersetzt,
- **Projekte** für Neubau und Instandhaltung inklusive Budgetierung und Ist-Kosten-Erfassung steuert,
- **BIM-Modelle** (IFC) clientseitig im Browser visualisiert,
- **Asset-Management** mit Kosten sowie Datenstrukturen für **Energie- und Abfallmanagement** im Sinne der Nachhaltigkeit zusammenführt,
- ein **Service-Portal** bietet, über das Nutzende und Hausmeisterdienste Schäden melden und den Stand verfolgen,
- sich künftig über einen **MCP-Server** auch in natürlicher Sprache durch KI-Agenten bedienen lässt, auf Wunsch mit lokalen Modellen und mit menschlicher Freigabe schreibender Aktionen.

Sämtliche Daten verbleiben auf den eigenen Servern des Betreibers und damit unter eigener Kontrolle (DSGVO, digitale Souveränität). Rechenintensive Funktionen laufen lokal im Browser: IFC-Modelle per WebAssembly (web-ifc), Texterkennung für Belege per Tesseract.

## Zielgruppen

- Kommunale Gebäudewirtschaft und Eigenbetriebe (Schulen, Kitas, Rathäuser, Bauhöfe)
- Unternehmen mit eigenen Liegenschaften, Standorten oder Facility-Management-Abteilungen
- Haustechnik, Hausmeisterdienste und beauftragte Prüf- und Wartungsfirmen
- Nutzende, Mitarbeitende und Bürger*innen, die über das Service-Portal Schäden melden
- IT-Dienstleister, die Open-FM für Kommunen und Unternehmen betreiben

## Architektur

Open-FM ist ein modularer Monolith auf Laravel 13 (PHP 8.4), Filament 5 und Livewire 4. Die Oberflächen werden serverseitig gerendert, ein separates JavaScript-Frontend entfällt; das hält Wartung und Barrierefreiheit beherrschbar. Die Daten liegen in MariaDB oder MySQL; Unterstützung für PostgreSQL ist geplant. Mandantenfähigkeit (Kommune, Eigenbetrieb, Unternehmen, Dienstleister) und Rollenrechte je Mandant sind von Beginn an im Datenmodell verankert, alle Texte sind übersetzbar.

Den Kern bildet ein gemeinsames Gebäudemodell: **Objekt, Strukturbaum (Gebäude, Geschoss, Raum), Anlagen mit Prüffristen, Störungen und Kosten.** Darauf setzen Arbeitsbereiche für Gebäudebetrieb, Bauprojekte, Verträge, Energie, Abfall und das Service-Portal auf. Erweiterungen wie BIM/IFC-Viewer, Formular-Designer, QR-Codes, Gantt-Planung, Karten und Belegerfassung entstehen als eigenständige Pakete (siehe [Plugins](#plugins)). Eine REST-API mit mandantengebundenen Tokens und erzeugter OpenAPI-Dokumentation bindet Fachverfahren an.

## Module

Open-FM besteht aus sieben Oberflächen („Panels“), die sich Mandanten, Benutzer, Rollen und Stammdaten teilen. Mandanten sind voneinander getrennt; Benutzer können mehreren Mandanten angehören.

| Panel | Pfad | Inhalt |
| --- | --- | --- |
| **Gebäudemanagement** | `/fm/{mandant}` | Objekte mit Strukturbaum (Gebäude, Etagen, Räume, Zonen), Anlagen mit Typkatalog und Klassifikation (z. B. DIN 276), Störungsmeldungen mit SLA, Zuweisung und Automatisierung, Wartungspläne und -aufträge mit Katalog-Editor und Startkatalogen (DE, FR, PL), Dokumente, BIM-Modelle (IFC), QR-Codes und Etiketten, Objektakte als PDF |
| **Projektmanagement** | `/pm/{mandant}` | Projekte mit Phasen, Meilensteinen, Aufgaben, Kanban und Gantt, Budget und Ist-Kosten mit Belegerfassung, Projektvorlagen |
| **Service-Portal** | `/portal/{mandant}` | Für Melder wie Hausmeister: Störungen melden und den Stand verfolgen, nur für die zugewiesenen Objekte; Dashboard mit offenen Meldungen, Statusverlauf und Neuigkeiten |
| **Vertragsmanagement** | `/contracts/{mandant}` | Verträge mit Laufzeiten, Kündigungsstichtagen, Erinnerungen und Jahreskosten |
| **Energiemanagement** | `/energy/{mandant}` | Zähler mit Baumstruktur, Formeln und Anteilen je Anlage oder Raum, Zählerstände per Handy-Seite, CSV-Import und API, Tageswerte, Energiefluss als Sankey-Diagramm, Ziele mit Prognose |
| **Abfallmanagement** | `/waste/{mandant}` | Behälter und Abfuhrpläne mit automatischer Terminbuchung, Ausnahmemeldungen, Register gefährlicher Abfälle, Rechnungsprüfung, Behältergrößen-Optimierung, CO₂e- und ESG-Berichte, länderspezifische Regeln über importierbare Vorlagen |
| **Administration** | `/admin` | Mandanten, Benutzer und Rollen, App-Einstellungen (Name, Logo), Plugin-Verwaltung und ein Dashboard mit Systemzustand, Mandanten-Übersicht und Plugin-Status |

Weitere Eigenschaften:

- Acht Sprachen: Deutsch, Englisch, Französisch, Italienisch, Niederländisch, Polnisch, Rumänisch, Kroatisch.
- Anpassbare Dashboards je Benutzer (Raster mit frei verschiebbaren Widgets).
- Rollen und Rechte je Mandant, „eigene“ und „alle“ Datensätze bei Betreuer- und Projektleiter-Rechten.
- Aktivitätsprotokoll, DSGVO-Auskunft als PDF, CSV-Import und -Export.
- REST-API mit OpenAPI-Dokumentation.
- **Startpaket Prüf- und Wartungskataloge:** Für Deutschland, Frankreich und Polen liegen kleine Startkataloge mit den gängigsten Prüf- und Wartungspflichten (Anlagentyp, Rechtsgrundlage, Intervall, Vorlaufzeit) bei. Sie lassen sich je Mandant im Gebäudemanagement unter „Wartungsvorlagen“ importieren. Eigene Kataloge pflegen Betreiber im Katalog-Editor selbst.

### Technik im Überblick

- PHP 8.4 oder neuer, Laravel 13, Filament 5, Livewire 4
- MySQL 8 / MariaDB 10.11 für den Betrieb, SQLite für Entwicklung und Tests (PostgreSQL geplant)
- Tailwind CSS 4 über Vite (nur das Filament-Theme, keine eigene JavaScript-Anwendung)
- Berechtigungen mit spatie/laravel-permission und Filament Shield, Aktivitätsprotokoll mit spatie/laravel-activitylog, API-Tokens mit Laravel Sanctum
- Tests mit Pest, Formatierung mit Laravel Pint

## Installation

Voraussetzungen: PHP 8.4 mit den Erweiterungen `gd`, `intl`, `mbstring`, `pdo_mysql`, `zip` (für SQLite zusätzlich `pdo_sqlite`), Composer 2, Node.js 22 mit npm.

```bash
git clone https://github.com/nccirtu/Open-FM.git open-fm
cd open-fm
composer install
cp .env.example .env
php artisan key:generate
```

Datenbank in der `.env` eintragen (`DB_*`). Für einen schnellen Start genügt SQLite (`touch database/database.sqlite`), für den Betrieb wird MySQL oder MariaDB empfohlen. Dann:

```bash
php artisan migrate --seed
php artisan storage:link
npm ci
npm run build
```

Der Seeder legt den Mandanten `demo`, die Standardrollen (Admin, User, FM, PM, Melder, Dienstleister, Verträge, Abfall) und die Berechtigungen an. Den ersten Benutzer anlegen:

```bash
php artisan make:filament-user
php artisan app:assign-role admin@example.com Admin --verify-email
php artisan app:super-admin admin@example.com
```

`app:assign-role` ordnet den Benutzer dem Mandanten zu, vergibt die Rolle und bestätigt mit `--verify-email` die E-Mail-Adresse; alle Panels verlangen eine bestätigte Adresse. `app:super-admin` macht den Benutzer zusätzlich zum Super-Admin der Installation, der Plugins verwalten darf. Weitere Benutzer lädt der Admin im Admin-Panel unter „Benutzer“ ein.

Beispieldaten für die Module Energie und Abfall:

```bash
php artisan energy:demo
php artisan waste:demo
```

Lokal startet `composer run dev` Webserver, Queue-Worker, Log-Ausgabe und Vite gemeinsam.

## Betrieb und Sicherheit

- **Queue-Worker** als Dienst einrichten (`php artisan queue:work`, Datenbank-Queue genügt). Benachrichtigungen, Importe und Exporte laufen darüber.
- **Scheduler** minütlich per Cron aufrufen (`php artisan schedule:run`): SLA-Prüfung, Wartungsaufträge, Vertragsfristen und Abfuhrtermine hängen daran.
- **HTTPS** ist Pflicht, auch weil der QR-Scanner im Browser sonst nicht funktioniert.
- **Produktionseinstellungen** in der `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, ein eigener `APP_KEY`. Das Admin-Dashboard warnt, wenn der Debug-Modus in Produktion aktiv ist.
- **Proxys:** Hinter einem Reverse-Proxy oder Load-Balancer die Adressen der Proxys in `TRUSTED_PROXIES` eintragen (kommagetrennt; `*` nur, wenn der Proxy die `X-Forwarded-*`-Header zuverlässig überschreibt). Ohne Eintrag werden diese Header ignoriert.
- **Uploads:** Benutzer-Uploads (Ticketfotos und -anhänge, Wartungsprotokolle, Belege, Nachweise, Zählerfotos, Asset-Fotos, Dokumentenverwaltung, Formular-Uploads) liegen auf der privaten Platte `uploads` (`storage/app/private/uploads`) und werden nur über signierte, befristete Links (`/uploads/…`, 30 Minuten) an angemeldete, berechtigte Benutzer ausgeliefert. HTML-, SVG- und XML-Dateien werden nie inline gerendert, sondern nur als Download. Der Webserver muss `/uploads/…` an Laravel (`index.php`) weiterreichen und darf es nicht statisch bedienen. Logo und Favicon bleiben öffentlich auf der Platte `public` (`php artisan storage:link`).
- **Super-Admins** können über die Plugin-Verwaltung Code installieren und sehen alle Mandanten. Dieses Recht nur an vertrauenswürdige Personen vergeben; Workflows, Rollenverwaltung und App-Einstellungen sind ebenfalls Super-Admins vorbehalten.
- **Backups** von Datenbank und `storage/app` einplanen.

### Bekannte Einschränkungen der Beta

- Zwei-Faktor-Anmeldung und eine Content-Security-Policy fehlen noch (siehe [Roadmap](#roadmap)). Ohne Ablaufdatum gelten Sitzungen nach den Laravel-Standardeinstellungen.
- Der Upload des eingebundenen Media-Manager-Pakets filtert keine Dateitypen und prüft intern nur das Löschen per Policy; der Zugang ist an Rollen gebunden, und gefährliche Dateitypen werden nie inline ausgeliefert. Die Upload-Felder des Plugins `cafm/form-builder` begrenzen Dateitypen noch nicht.
- Ein Melder ohne Objektzuordnung sieht im Service-Portal alle Objekte und Anlagen seines Mandanten (Meldungen sind dagegen auf eigene und zugewiesene Objekte beschränkt).
- Die Kostenübersicht eines Objekts fasst Buchungen aus Projekten, Anlagen und Tickets zusammen; geschützt ist der Zugang zum Reiter, nicht jede einzelne Zeile.
- Die Tests prüfen den Betrieb mit und ohne Plugins, nicht jede Aktion jeder Plugin-Ressource unter der strikten Autorisierung.
- Der Katalog-Import lädt bisher nur die mitgelieferten Startkataloge; ein Import eigener Katalogdateien in einem offenen Format ist geplant.

Sicherheitslücken bitte nicht öffentlich als Issue melden, sondern über den Reiter „Security“ des GitHub-Repositories („Report a vulnerability“).

### Upgrade: private Benutzer-Uploads

Wer eine ältere Entwicklungsinstallation weiterbetreibt, verschiebt vorhandene Dateien einmalig: 1. Backup von `storage/app/public` anlegen. 2. Falls `FILAMENT_FILESYSTEM_DISK` oder `MEDIA_DISK` in der `.env` auf `public` stehen, entfernen oder auf `uploads` setzen. 3. `php artisan config:clear` ausführen. 4. `php artisan uploads:make-private --dry-run` prüfen und danach `php artisan uploads:make-private` als Webserver-Benutzer ausführen. Der Befehl ist idempotent, verschiebt Dateien von `public` nach `uploads`, stellt die Medienverwaltung um und lässt alle Pfade in der Datenbank unverändert. Bei Konflikten (Datei existiert auf dem Ziel mit anderer Größe) bleibt die Quelle liegen und der Befehl endet mit Fehlercode.

## Plugins

Strukturbaum, Gantt, Karte, BIM-Viewer, Formular-Designer, Zusatzfelder, Belegerfassung und QR-Codes sind **Plugins**: eigenständige Composer-Pakete (`cafm/*`), die nicht Teil dieses Repositories sind. Sie werden als quelloffene Pakete in eigenen Repositories veröffentlicht (siehe [Roadmap](#roadmap)). Open-FM läuft vollständig ohne sie; fehlt ein Plugin, zeigen die betroffenen Funktionen einen Hinweis mit dem Weg in die Plugin-Verwaltung.

- **Verwaltung:** `/admin/plugins`, nur für Super-Admins. Dort werden Plugins aus dem Plugin-Verzeichnis oder per ZIP-Upload installiert, aktualisiert, deinstalliert, aktiviert und je Mandant freigegeben (keine Auswahl = alle Mandanten).
- **Technik:** Composer läuft im Hintergrund (Queue) mit der eigenen, nicht versionierten Datei `composer.plugins.json`; `composer.json` und `composer.lock` des Projekts bleiben unverändert. ZIP-Uploads landen in einem Composer-Artefakt-Repository unter `storage/app/private/plugins/artifacts`.
- **Konsole:** `plugins:install <slug>` bzw. `plugins:install --zip=<datei> [--signature=<datei>] [--trust-unsigned]`, `plugins:update`, `plugins:remove`, `plugins:restore`, `plugins:sync`.
- **Einstellungen (`.env`):** `PLUGINS_MARKETPLACE_URL` (Plugin-Verzeichnis), `PLUGINS_PUBLIC_KEY` und `PLUGINS_REQUIRE_SIGNATURE` (Signaturprüfung der ZIPs), `PLUGINS_COMPOSER_BINARY`.

**Plugin-Verzeichnis und Ökosystem:** Über das offene Plugin-Verzeichnis können auch Dritte Erweiterungen und Vorlagen anbieten, kostenlos oder kostenpflichtig. Kern und eigene Erweiterungen von Open-FM bleiben frei. Die Regeln für Plugins Dritter (Lizenz, Signatur, Aufnahme ins Verzeichnis) werden mit dem Governance-Modell veröffentlicht.

**Core-Update:** `composer install` mit dem Projekt-Lock entfernt die Plugins aus `vendor/`. Danach `php artisan plugins:restore` ausführen; die Plugin-Verwaltung weist ebenfalls darauf hin.

**Plugin-Entwicklung:** Mit `PLUGINS_DEV_PATH=../Open-FM-Plugins/packages` installiert Open-FM Plugins als Symlink aus einem lokalen Plugin-Repository. Packen und signieren für das Verzeichnis: `php artisan plugins:keygen` (einmalig) und `php artisan plugins:pack <paketverzeichnis> --key=<geheimer Schlüssel>`.

Formular- und Klassifikationsvorlagen sowie die Länderregeln für den Abfallbereich liegen in `database/templates`, gehören nicht zu diesem Repository und werden gesondert bereitgestellt. Ohne sie läuft Open-FM, die zugehörigen Importe (`forms:import-template`, `classifications:import`, `waste:import-template`) sind dann ohne Inhalt.

## API

Die REST-API unter `/api/v1` deckt alle Ressourcen ab: Objekte und Strukturknoten, Anlagen und Anlagentypen, Klassifikation, Firmen mit Adressen und Ansprechpartnern, Störungen mit Antworten und SLA, Wartungspläne und -aufträge, Verträge, Projekte mit Phasen, Meilensteinen, Aufgaben, Budgetpositionen und Kostenbuchungen, Zähler und Zählerstände, Abfall (Behälter, Termine, Leerungen), Benutzer und den Mandanten selbst.

- **Authentifizierung:** Sanctum-Bearer-Token, in den Mandanten-Panels über das Benutzermenü „API-Zugänge“ angelegt. Jeder Token gehört zu genau einem Mandanten und trägt Fähigkeiten je Ressource (`assets:read`, `assets:write`, `tickets:read`, …).
- **Pagination:** `page` und `per_page` (Standard 25, maximal 100); Antworten enthalten `data`, `links` und `meta`.
- **Filter, Sortierung, Relationen:** `filter[feld]=wert` (mehrere Werte durch Komma), `sort=feld` beziehungsweise `sort=-feld`, `include=relation1,relation2`. Unbekannte Parameter liefern 400.
- **Ratelimit:** 120 Anfragen pro Minute je Token (`API_RATE_LIMIT_PER_MINUTE`), Überschreitung liefert 429 mit `Retry-After`.
- **Dokumentation:** OpenAPI 3.1 wird zur Laufzeit aus dem Code erzeugt (Scramble). Interaktive Doku unter `/docs/api`, Spezifikation unter `/docs/api.json`, Zugriff für angemeldete Benutzer mit Mandantenzuordnung.

## Entwicklung

```bash
composer run dev
php artisan test --compact
vendor/bin/pint --dirty
npm run format
```

Die Tests laufen gegen SQLite im Speicher und brauchen keine Datenbank. Tests für Plugin-Funktionen überspringen sich, wenn das Plugin nicht installiert ist; die Suite ist mit und ohne Plugins grün. Die GitHub-Workflows führen Pest, Pint und Prettier bei jedem Push aus.

Wichtige Konventionen: Primärschlüssel sind UUIDv7, Enums liegen in `app/Enums`, neue Übersetzungsschlüssel kommen in alle acht Sprachen unter `lang/` (Deutsch ist die Referenz), mandantengebundene Modelle werden in `ApplyTenantScopes::TENANT_MODELS` eingetragen.

## Roadmap

Open-FM soll als offene Plattform wachsen, die Kommunen, Unternehmen und IT-Dienstleister gemeinsam weiterentwickeln: Die Erweiterungen werden als quelloffene Pakete veröffentlicht, ein offenes Plugin-Verzeichnis bündelt Erweiterungen und Vorlagen, und neben MariaDB und MySQL wird künftig auch PostgreSQL unterstützt. Zugleich soll die Anwendung für KI-Agenten bedienbar werden, über einen MCP-Server mit menschlicher Freigabe schreibender Aktionen und auf Wunsch mit lokalen Modellen, und sie soll mobile Apps für iOS und Android für den Einsatz vor Ort bekommen. Weitere Themen sind die Härtung (Zwei-Faktor-Anmeldung, Content-Security-Policy), feinere Rechte und ein Löschkonzept, der anonymisierte Opt-in-Export für den Community-Datenpool sowie der Ausbau von Energie- und Abfallmanagement. Das ist eine Planung, keine Zusage; Reihenfolge und Inhalt richten sich nach Rückmeldungen aus der Beta.

## Mitwirken

Beiträge sind willkommen: Fehler und Wünsche als Issue melden, Fragen und Ideen in den [Discussions](https://github.com/nccirtu/Open-FM/discussions), Änderungen als Pull Request gegen `main`. Bitte vor dem Einreichen `vendor/bin/pint --dirty`, `npm run format` und `php artisan test --compact` ausführen und für neues Verhalten Tests mitliefern.

## Lizenz

Open-FM steht unter der **GNU Affero General Public License v3.0** (AGPL-3.0), siehe [LICENSE](LICENSE).

Das bedeutet:

- Die Software darf frei genutzt, verändert und weitergegeben werden.
- Wer Open-FM verändert und weitergibt **oder als Dienst über ein Netzwerk betreibt**, muss den vollständigen Quelltext seiner Fassung unter derselben Lizenz zugänglich machen. Der Netzwerk-Fall unterscheidet die AGPL von der GPL und ist für eine Web-Plattform der entscheidende Punkt.
- Es gibt keine Gewährleistung.

Die eingebundenen Bibliotheken und Filament-Plugins Dritter behalten ihre jeweiligen Lizenzen.

**Autorenhinweis.** Open-FM wird von Gladys Slawina und Nicolae Cristian Cirtu entwickelt und gepflegt. Alle Panels zeigen im Footer den Hinweis „Open-FM by Gladys Slawina und Nicolae Cristian Cirtu“ mit Versionsnummer, Jahr und Lizenz. Dieser Hinweis ist ein Autorenvermerk im Sinne von AGPL-3.0 §7(b) und darf in abgeleiteten Fassungen nicht entfernt oder verändert werden. Eigenes Branding (Logo, Name, Farben) ist davon unberührt und über die App-Einstellungen möglich.

### Datenlizenzmodell: Community-Datenpool

**Grundsatz: Die Teilnahme ist freiwillig. Geteilt werden ausschließlich Anlagendaten; personenbezogene oder anderweitig datenschutzrelevante Daten werden zu keinem Zeitpunkt geteilt.** Der Datenpool enthält Informationen über Anlagen als technische Objekte, nie über Menschen, Unternehmen oder Standorte.

Ziel von Open-FM ist neben der Software ein gemeinsamer, offener Datenbestand über technische Anlagen: Welche Anlagentypen welcher Hersteller wie lange halten, welche Wartungsintervalle und Prüfpflichten gelten, welche Störungen typisch sind, was Anlagen im Betrieb kosten. Damit können Betreiber gleichartige Anlagen und Hersteller vergleichen und leichter an belastbare Daten kommen.

**Was geteilt wird.** Ausschließlich sachbezogene Anlagendaten:

- Anlagentyp, Klassifikation, Hersteller, Modellbezeichnung
- Baujahr beziehungsweise Installationsjahr, Stilllegungsjahr, Zustand, Betriebsstatus
- Wartungsart, Intervalle, Rechtsgrundlagen, Ausfall- und Störungshäufigkeit
- Kostenkategorien und Kostenwerte in Preisklassen

**Was nie geteilt wird.** Alle Daten, die unter die DSGVO fallen oder Personen, Unternehmen oder einzelne Standorte identifizierbar machen: Namen, E-Mail-Adressen, Benutzer, Ansprechpartner, Adressen und Koordinaten, Seriennummern, Fotos, Dokumente, Freitexte, Vertrags- und Rechnungsdaten, Mandanten- und Firmennamen. Diese Daten verlassen die Installation des Betreibers nicht.

**Wie teilgenommen wird.** Die Teilnahme ist weder Bedingung der Software-Lizenz noch Voraussetzung für die Nutzung von Open-FM. Sie ist standardmäßig ausgeschaltet und wird je Mandant ausdrücklich aktiviert (Opt-in); die Zustimmung wird dokumentiert und kann jederzeit widerrufen werden. Der Export erfolgt anonymisiert und aggregiert. Kennzahlen werden erst veröffentlicht, wenn genügend Mandanten zu einer Anlagenart beitragen, damit kein Rückschluss auf einzelne Mandanten möglich ist; die genaue Mindestteilnehmerzahl wird mit den Regeln für den Datenpool festgelegt.

**Lizenz der geteilten Daten.** Der gemeinsame Datenbestand steht unter der **Open Database License (ODbL) 1.0**. Wer ihn nutzt, muss die Quelle nennen und abgeleitete Datenbestände wieder unter ODbL veröffentlichen. Das ist das Share-alike-Prinzip der AGPL, übertragen auf Daten.

Die technische Umsetzung von Export und Zustimmung ist Teil der [Roadmap](#roadmap).
