# rslanzi/laravel-agents

[English](README.md)

Linee guida per agenti AI (Claude Code, Codex, Cursor, ...) e tooling di qualità per applicazioni Laravel organizzate a moduli in stile DDD.

- **Guideline** caricate in automatico da [Laravel Boost](https://laravel.com/docs/boost) in `AGENTS.md` e `CLAUDE.md`: principi (KISS, YAGNI, SOLID), architettura a moduli, TDD, convenzioni Laravel, ADR, sicurezza, performance, errori e log, quality tooling, Git, migrazioni sicure, API, traduzioni, README dei moduli.
- **Skill** che Boost carica solo quando il compito le richiede, per tenere piccolo il contesto sempre attivo: contratti comportamentali, building block di dominio, migrazioni sicure, API, ADR, README dei moduli, performance, traduzioni, correzione degli errori PHPStan.
- **`php artisan agents:install`**: installa Pest, Larastan, Rector e rector-laravel (Pint c'è già in Laravel) e pubblica le config senza sovrascrivere quelle esistenti: `pint.json`, `rector.php`, `phpstan.neon`, `tests/Pest.php`, `tests/Unit/ArchitectureTest.php`, `tests/Unit/TranslationsTest.php`, CI GitHub Actions, template PR, `.claude/settings.json` senza attribuzione all'agente, indice ADR, file dei moduli del progetto, script composer.
- **`php artisan agents:markdown`**: scrive un `AGENTS.md` completo (guideline e skill) per i progetti che non usano Boost.

## Installazione

Il pacchetto non è ancora su Packagist. Nel frattempo aggiungi il repository al `composer.json` del progetto:

```bash
composer config repositories.laravel-agents vcs https://github.com/rslanzi/laravel-agents
composer require --dev rslanzi/laravel-agents:dev-main
```

Poi:

```bash
composer require --dev laravel/boost          # se non c'è già

php artisan boost:install                     # scegli rslanzi/laravel-agents tra i pacchetti
php artisan agents:install                    # tooling e config
composer fix                                  # Rector, Pint, PHPStan, Pest
```

Se Boost è già configurato, `agents:install` aggiunge `rslanzi/laravel-agents` a `boost.json`; poi basta `php artisan boost:update`.

Opzioni di `agents:install`:

| Opzione | Effetto |
| --- | --- |
| `--only=<gruppo>` | pubblica solo un gruppo di file (`quality`, `tests`, `ci`, `claude`, `docs`); ripetila per più gruppi |
| `--dry-run` | mostra cosa cambierebbe, senza scrivere nulla |
| `--no-tools` | non lancia `composer require` per gli strumenti |
| `--force` | sovrascrive config e script composer esistenti |

Su un progetto con PHPUnit, `agents:install` rimuove `phpunit/phpunit` dai require-dev perché Pest porta la sua versione; i test PHPUnit esistenti continuano a girare con Pest. Se Composer fallisce, `composer.json` e `composer.lock` vengono ripristinati.

I nuovi script composer vengono aggiunti alla sezione `scripts` esistente e il resto di `composer.json` resta identico. Solo `--force` su uno script già presente riscrive tutto il file.

## Aggiornare le regole in tutti i progetti

```bash
composer update rslanzi/laravel-agents
php artisan boost:update
```

Le config pubblicate (Rector, PHPStan, CI...) restano del progetto. Per prendere la versione nuova di un gruppo, ripubblicalo e controlla il diff:

```bash
php artisan vendor:publish --tag=agents-ci --force   # oppure agents-quality, agents-tests, agents-claude, agents-docs, agents
```

## Script composer

| Script | Cosa fa |
| --- | --- |
| `composer fix` | Rector, Pint, PHPStan, test: da lanciare prima di chiudere un lavoro |
| `composer check` | gli stessi controlli in sola lettura, con copertura minima e `composer audit`: quello che lancia il job CI `quality` |
| `composer test:all` | tutta la suite in parallelo |
| `composer test:coverage` | Pest con copertura minima 80% (serve PCOV o Xdebug) |
| `composer test:mutate` | mutation testing con score minimo 70% (serve PCOV o Xdebug); il job CI `mutation` lo lancia sulle PR |
| `composer test:arch` | solo l'architecture test |

## Commit e PR a tuo nome

Le guideline vietano all'agente `Co-Authored-By`, footer "Generated with..." e link di sessione. Claude Code aggiunge l'attribuzione da solo: `.claude/settings.json` pubblicato da `agents:install` la disattiva (`attribution.commit` e `attribution.pr` vuoti, `sessionUrl: false`). Per averlo in tutti i progetti mettilo in `~/.claude/settings.json`. Per tenere l'attribuzione, installa senza quel gruppo: `--only=quality --only=tests --only=ci --only=docs`.

## Personalizzare per progetto

Le regole del pacchetto valgono per tutti i progetti. Quelle di un singolo progetto vanno in `.ai/guidelines/20-project-contexts.md` (moduli, glossario, eccezioni), che Boost mette in cima alle istruzioni.

## Sviluppo del pacchetto

Le guideline sono in `resources/boost/guidelines/`, le skill in `resources/boost/skills/{nome}/SKILL.md`, i file pubblicati in `stubs/`.

```bash
composer install
composer test        # Pest
composer analyse     # PHPStan
composer format      # Pint
```

Il workflow `Integration` installa il pacchetto in un'applicazione Laravel nuova, lancia `agents:install` due volte (la seconda non deve cambiare nulla), poi `composer check` e la mutation testing.

## Versioni di PHP

PHP 8.3, 8.4 e 8.5 sono testati a ogni modifica, con Laravel 12 e 13. PHP 8.6 gira sulla build nightly in un job che non blocca, così le incompatibilità emergono prima del rilascio.

## Licenza

MIT, vedi [LICENSE](LICENSE).
