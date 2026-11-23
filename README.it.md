# rslanzi/laravel-agents

[English](README.md)

Linee guida per agenti AI (Claude Code, Codex, Cursor, ...) e tooling di qualità per applicazioni Laravel organizzate a moduli in stile DDD.

- **Guideline** caricate in automatico da [Laravel Boost](https://laravel.com/docs/boost) in `AGENTS.md` e `CLAUDE.md`: principi (KISS, YAGNI, SOLID, clean code), architettura a moduli, TDD, convenzioni Laravel, ADR, sicurezza, performance, errori e log, quality tooling, Git, migrazioni sicure, API, traduzioni, README dei moduli.
- **Skill** che Boost carica solo quando il compito le richiede, per tenere piccolo il contesto sempre attivo: convenzioni Laravel, contratti comportamentali, building block di dominio, migrazioni sicure, API, ADR, README dei moduli, performance, traduzioni, correzione degli errori PHPStan.
- **`php artisan agents:install`**: installa Pest, Larastan, Rector e rector-laravel (Pint c'è già in Laravel) e pubblica le config senza sovrascrivere quelle esistenti: `pint.json`, `rector.php`, `phpstan.neon`, `tests/Pest.php`, `tests/Unit/ArchitectureTest.php`, `tests/Unit/TranslationsTest.php`, CI GitHub Actions, template PR, `.claude/settings.json` senza attribuzione all'agente, indice ADR, mappa dell'architettura, changelog, file dei moduli del progetto, script composer.
- **`php artisan agents:markdown`**: scrive un `AGENTS.md` completo (guideline e skill) per i progetti che non usano Boost.

## Avvio rapido

Il pacchetto non è ancora su Packagist. Finché non lo è, aggiungi il repository al `composer.json` del progetto:

```bash
composer config repositories.laravel-agents vcs https://github.com/rslanzi/laravel-agents
composer require --dev rslanzi/laravel-agents:dev-main
composer require --dev laravel/boost          # se non è già installato

php artisan boost:install                     # seleziona rslanzi/laravel-agents tra i pacchetti
php artisan agents:install                    # tooling e config
composer fix                                  # Rector, Pint, PHPStan, Pest
```

Per aggiornare: `composer update rslanzi/laravel-agents && php artisan boost:update`, dopo aver letto [UPGRADE.md](UPGRADE.md).

## Documentazione

La documentazione completa è in inglese:

- [Installation](docs/installation.md): opzioni di `agents:install` e file pubblicati, aggiornamenti, ripubblicare le config.
- [Customization](docs/customization.md): regole del progetto, mappa dell'architettura, attribuzione, da dove vengono le regole.
- [Releasing and contributing](docs/releasing.md): sviluppo, messaggi di commit, rilasci automatici.
- [CHANGELOG.md](CHANGELOG.md) e [UPGRADE.md](UPGRADE.md).

## Script composer

| Script | Cosa fa |
| --- | --- |
| `composer fix` | Rector, Pint, PHPStan, test: da lanciare prima di chiudere un lavoro |
| `composer check` | gli stessi controlli in sola lettura, con copertura minima e `composer audit`: quello che lancia il job CI `quality` |
| `composer test:all` | tutta la suite in parallelo |
| `composer test:coverage` | Pest con copertura minima 80% (serve PCOV o Xdebug) |
| `composer test:mutate` | mutation testing con score minimo 70% (serve PCOV o Xdebug); il job CI `mutation` lo lancia sulle PR |
| `composer test:arch` | solo l'architecture test |

## Versioni di PHP

PHP 8.3, 8.4 e 8.5 sono testati a ogni modifica, con Laravel 12 e 13. PHP 8.6 gira sulla build nightly in un job che non blocca, così le incompatibilità emergono prima del rilascio.

## Licenza

MIT, vedi [LICENSE](LICENSE).
