# rslanzi/laravel-agents

Linee guida per agenti AI (Claude Code, Codex, Cursor, ...) e tooling di qualità per applicazioni Laravel organizzate a moduli in stile DDD.

- **Guideline** caricate in automatico da [Laravel Boost](https://laravel.com/docs/boost) in `AGENTS.md` e `CLAUDE.md`: principi (KISS, YAGNI, SOLID), architettura a moduli, TDD, convenzioni Laravel, ADR, sicurezza, performance, errori e log, quality tooling, Git, migrazioni sicure, API, traduzioni, README dei moduli.
- **`php artisan agents:install`**: installa Pest, Larastan, Rector e rector-laravel (Pint c'è già in Laravel) e pubblica le config senza sovrascrivere quelle esistenti: `pint.json`, `rector.php`, `phpstan.neon`, `tests/Pest.php`, `tests/Unit/ArchitectureTest.php`, CI GitHub Actions, template PR, `.claude/settings.json` senza attribuzione all'agente, indice ADR, file dei moduli del progetto, script composer.
- **`php artisan agents:markdown`**: scrive un `AGENTS.md` completo per i progetti che non usano Boost.

## Installazione

```bash
composer require --dev rslanzi/laravel-agents
composer require --dev laravel/boost          # se non c'è già

php artisan boost:install                     # scegli rslanzi/laravel-agents tra i pacchetti
php artisan agents:install                    # tooling e config
composer fix                                  # Rector, Pint, PHPStan, Pest
```

Il pacchetto è su [Packagist](https://packagist.org/packages/rslanzi/laravel-agents). Finché non c'è una release taggata, installa il ramo principale con `composer require --dev rslanzi/laravel-agents:dev-main`.

Se Boost è già configurato, `agents:install` aggiunge `rslanzi/laravel-agents` a `boost.json`; poi basta `php artisan boost:update`.

Opzioni di `agents:install`:

| Opzione | Effetto |
| --- | --- |
| `--no-tools` | non lancia `composer require` per gli strumenti |
| `--force` | sovrascrive config e script composer esistenti |

Su un progetto con PHPUnit, `agents:install` rimuove `phpunit/phpunit` dai require-dev perché Pest porta la sua versione. I test PHPUnit esistenti continuano a girare con Pest.

## Aggiornare le regole in tutti i progetti

```bash
composer update rslanzi/laravel-agents
php artisan boost:update
```

Le config pubblicate (rector, phpstan, CI...) restano del progetto: per riprendere le nuove versioni usa `php artisan agents:install --no-tools --force` e controlla il diff.

## Script composer

| Script | Cosa fa |
| --- | --- |
| `composer fix` | Rector, Pint, PHPStan, test: da lanciare prima di chiudere un lavoro |
| `composer check` | gli stessi controlli in sola lettura, con copertura minima e `composer audit`: quello che gira in CI |
| `composer test:coverage` | Pest con copertura minima 80% (serve PCOV o Xdebug) |
| `composer test:mutate` | mutation testing con score minimo 70% (serve PCOV o Xdebug) |
| `composer test:arch` | solo l'architecture test |

## Commit e PR a tuo nome

Le guideline vietano all'agente `Co-Authored-By`, footer "Generated with..." e link di sessione. Claude Code aggiunge l'attribuzione da solo: `.claude/settings.json` pubblicato da `agents:install` la disattiva (`attribution.commit` e `attribution.pr` vuoti, `sessionUrl: false`). Per averlo in tutti i progetti mettilo in `~/.claude/settings.json`.

## Personalizzare per progetto

Le regole del pacchetto valgono per tutti i progetti. Quelle di un singolo progetto vanno in `.ai/guidelines/20-project-contexts.md` (moduli, glossario, eccezioni), che Boost mette in cima alle istruzioni.

## Sviluppo del pacchetto

Le guideline sono in `resources/boost/guidelines/`, le config pubblicate in `stubs/`.

```bash
composer install
vendor/bin/pest
vendor/bin/pint
```
