## Beschreibung

Was ändert dieser Pull Request und warum? Verweis auf das zugehörige Issue (z. B. `Closes #123`).

## Art der Änderung

- [ ] Fehlerbehebung
- [ ] Neue Funktion
- [ ] Änderung an Datenbank oder Berechtigungen (Migration, Policy)
- [ ] Dokumentation oder Übersetzung
- [ ] Sonstiges

## Checkliste

- [ ] Tests für das neue oder geänderte Verhalten sind vorhanden (`php artisan test --compact`)
- [ ] `vendor/bin/pint --dirty` und `npm run format` wurden ausgeführt
- [ ] Neue Texte stehen in allen acht Sprachen unter `lang/` (Deutsch ist die Referenz)
- [ ] Mandantengebundene Modelle sind in `ApplyTenantScopes::TENANT_MODELS` eingetragen und haben eine Policy
- [ ] README oder Dokumentation sind angepasst, falls nötig
