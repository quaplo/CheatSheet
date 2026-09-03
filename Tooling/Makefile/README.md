# Makefile

> [← zpět na Nástroje](../)

> **V jedné větě:** Jeden vstupní bod do projektu — `make test` místo `docker compose exec --user 1000:1000 php vendor/bin/phpunit`.

> [!NOTE]
> Make je nástroj na **sestavování programů** ze 70. let a umí sledovat, které soubory se změnily. V PHP projektu se z něj používá jen malá část: **pojmenované příkazy**. Není to zneužití — je to nejrozšířenější způsob, jak dát projektu jednotné ovládání, a je předinstalovaný skoro všude.

---

## K čemu to je

Bez Makefile vypadá znalost projektu takhle: v README je deset příkazů, půlka je zastaralá, zbytek si každý pamatuje jinak. Nový člověk se ptá na Slacku, jak se pouští testy.

**Poznáš, že ti chybí, podle:**

- příkazy pro projekt kolují **v historii shellu** a v hlavách lidí
- nový člen týmu potřebuje **půl dne**, než mu projekt naběhne
- v README je návod, který **už neplatí**, a nikdo neví od kdy
- každý pouští testy **trochu jinak** a někomu to nejde
- CI dělá **něco jiného** než lidé lokálně — a proto „u mě to prochází"
- příkazy jsou tak dlouhé, že si je lidé **zkracují** a vynechávají z nich přepínače

Makefile řeší všechny naráz tím, že příkaz dostane **jméno**. `make test` funguje stejně na notebooku i v CI, dá se dohledat a když se změní implementace, změní se na jednom místě.

Vedlejší, ale nezanedbatelný přínos: **`make help` je dokumentace, která nemůže zastarat**, protože je generovaná z toho, co se opravdu spouští.

---

## Minimum, které musíš znát

Make má vlastní syntaxi a **čtyři místa, kde se na ní každý poprvé sekne**.

### 1. Odsazuje se TABEM, ne mezerami

```makefile
test:
→   vendor/bin/phpunit
```

Ta šipka je **tabulátor**. S mezerami dostaneš:

```
Makefile:2: *** missing separator.  Stop.
```

Je to nejčastější chyba a editor ti ji umí udělat sám tím, že tab převede na mezery. Do `.editorconfig` proto patří:

```ini
[Makefile]
indent_style = tab
```

### 2. Každý řádek běží ve vlastním shellu

```makefile
# ŠPATNĚ — druhý řádek už není v tom adresáři
build:
	cd frontend
	npm run build

# SPRÁVNĚ
build:
	cd frontend && npm run build
```

Totéž platí pro proměnné shellu — nastavení z jednoho řádku na dalším neplatí.

> [!WARNING]
> Existuje direktiva `.ONESHELL:`, která tohle chování vypne, ale **na macOS nefunguje**. Přišla v GNU Make 3.82 a Apple dodává 3.81 (kvůli licenci ji neaktualizuje), zatímco Linux má 4.x. Ověřeno: na macOS projde bez chyby a **tiše se chová postaru**. Nepoužívej ji — kolegovi na Linuxu poběží a tobě ne.

### 3. `.PHONY` u každého cíle

Make je původně nástroj na sestavování souborů. Cíl `test` pro něj znamená „vyrob soubor jménem `test`". Když takový soubor nebo složka existuje, Make usoudí, že je hotovo:

```
make: 'test' is up to date.
```

V PHP projektu, kde bývá složka `tests/` i `build/`, se to stane dřív nebo později. Prevence:

```makefile
.PHONY: test
test:
	vendor/bin/phpunit
```

`.PHONY` říká „tenhle cíl není soubor, spusť ho vždycky". **Piš ho ke každému cíli**, nestojí to nic.

### 4. Proměnné: `$(VAR)` je Make, `$$VAR` je shell

```makefile
PHP := docker compose exec php

test:
	$(PHP) vendor/bin/phpunit      # ← dosadí Make
	echo "Domov je $$HOME"         # ← dosadí shell; jedno $ by snědl Make
```

K tomu dvě drobnosti, které se hodí hned:

- **`@` na začátku řádku** potlačí vypsání příkazu — hodí se u `help`, kde chceš vidět jen výstup
- **`-` na začátku** znamená „pokračuj i při chybě"
- **`?=`** přiřadí, jen pokud proměnná ještě nemá hodnotu (jde přebít zvenčí)

---

## Použití v Dockeru

Tady je celý smysl Makefile v našem prostředí: **skrýt, že se příkaz pouští v kontejneru.**

### Základ

```makefile
DOCKER_COMPOSE := docker compose
PHP := $(DOCKER_COMPOSE) exec php

.PHONY: test
test:
	$(PHP) vendor/bin/phpunit
```

`make test` teď funguje, aniž bys musel mít PHP nainstalované.

> [!NOTE]
> Compose v2 se volá **`docker compose`** (dvě slova), starší v1 byl `docker-compose` s pomlčkou. Když se v týmu potkávají obě, vyplatí se to mít v proměnné — mění se pak jeden řádek.

### `exec` nebo `run --rm`?

| | `exec` | `run --rm` |
| --- | --- | --- |
| Vyžaduje běžící kontejner | **ano** | ne |
| Rychlost | **rychlejší** | startuje nový kontejner |
| Použití | běžná práce | příkazy před `up`, jednorázové úlohy |
| Když projekt neběží | spadne | funguje |

```makefile
PHP     := $(DOCKER_COMPOSE) exec php
PHP_RUN := $(DOCKER_COMPOSE) run --rm php
```

**Výchozí volba je `exec`**, protože při běžné práci kontejner běží a je to znatelně rychlejší. `run --rm` si nech na věci, které se dělají, když projekt ještě nestojí. `--rm` je důležité — bez něj se ti hromadí zastavené kontejnery.

### Práva k souborům

Nejčastější a nejotravnější problém: kontejner běží jako `root`, takže **soubory, které vytvoří, patří rootovi** — a ty je pak lokálně nesmažeš ani neupravíš.

Týká se to všeho, co něco zapisuje: `composer install` (složka `vendor/`), generátory kódu, migrace, cache.

```makefile
UID := $(shell id -u)
GID := $(shell id -g)

PHP := $(DOCKER_COMPOSE) exec --user $(UID):$(GID) php
```

Demo to ukazuje na skutečném výstupu:

```
docker compose exec --user 501:20 php vendor/bin/phpunit
```

Alternativně se dá uživatel nastavit rovnou v `docker-compose.yml` (`user: "${UID}:${GID}"`), ale v Makefile to funguje bez toho, aby si každý musel exportovat proměnné.

### CI: `-T`

V CI není žádné terminálové zařízení, takže příkaz spadne na:

```
the input device is not a TTY
```

Řeší to přepínač `-T`, který vypne přidělení TTY:

```makefile
ifdef CI
	PHP := $(DOCKER_COMPOSE) exec -T --user $(UID):$(GID) php
endif
```

Proměnnou `CI` nastavuje GitHub Actions i GitLab CI samy, takže se o nic starat nemusíš. Rozdíl je vidět v demu:

```
lokálně:  docker compose exec --user 501:20 php vendor/bin/phpunit
v CI:     docker compose exec -T --user 501:20 php vendor/bin/phpunit
```

---

## Užitečné cíle

Kompletní Makefile je v [demu](demo/Makefile); tady to podstatné z něj.

### `help` — nápověda, která se udržuje sama

```makefile
.DEFAULT_GOAL := help

.PHONY: help
help: ## Vypíše tenhle přehled
	@grep -E '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| LC_ALL=C sort \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-18s\033[0m %s\n", $$1, $$2}'
```

Vytahuje komentáře uvozené `##` přímo z Makefile. **Nemůže zastarat**, protože popis je u příkazu, který se opravdu spouští.

`.DEFAULT_GOAL := help` způsobí, že samotné `make` vypíše nápovědu — jinak by spustilo první cíl v souboru, což bývá překvapení.

> [!TIP]
> `LC_ALL=C` u `sort` tam není pro parádu. V českém a slovenském prostředí se **„ch" řadí jako samostatné písmeno za „h"**, takže bez toho skončí cíl `check` až za `help`. Narazil jsem na to při psaní tohohle dokumentu.

### Start projektu

```makefile
.PHONY: init
init: ## První spuštění projektu — od nuly po běžící aplikaci
	cp -n .env.example .env || true
	$(DOCKER_COMPOSE) build
	$(DOCKER_COMPOSE) up -d
	$(MAKE) composer-install
	$(MAKE) db-migrate
```

**Tohle je ten nejcennější cíl.** Onboarding se zkrátí na `git clone` a `make init`. Když se do něj něco přidá, má to nový člověk automaticky.

`cp -n` nepřepíše existující soubor, `|| true` zajistí, že to neshodí celý cíl. `$(MAKE)` volá Make rekurzivně — používej ho místo `make`, protože předá parametry běhu.

### Příkazy s argumenty

Make neumí předat argumenty přirozeně, ale proměnná stačí:

```makefile
.PHONY: test
test: ## Spustí testy; lze zúžit: make test ARGS="--filter=OrderTest"
	$(PHP) vendor/bin/phpunit $(ARGS)

.PHONY: composer
composer: ## Libovolný composer příkaz: make composer CMD="require foo/bar"
	$(PHP) composer $(CMD)
```

```bash
make test ARGS="--filter=OrderTest"
make composer CMD="require doctrine/orm"
```

Existuje trik, jak psát `make test --filter=X` bez `ARGS=` (přes `$(MAKECMDGOALS)`), ale **je křehký a rozbíjí nápovědu** — nestojí to za ušetřená čtyři písmena. Napiš do popisu, jak se to volá, a je to.

### Skládání cílů

```makefile
.PHONY: check
check: cs stan test ## Vše, co má projít před odesláním k review
```

Cíle za dvojtečkou jsou **závislosti** — spustí se v pořadí, v jakém stojí, a když jeden selže, zbytek neběží. Jeden `make check` před [odesláním k review](../../Processes/CodeReview/Author/) ušetří celé jedno kolo připomínek.

### Stejné příkazy lokálně i v CI

```makefile
.PHONY: ci
ci: ## Totéž co spouští pipeline — spustitelné i lokálně
	$(MAKE) cs
	$(MAKE) stan
	$(MAKE) test
```

A v pipeline:

```yaml
# .github/workflows/ci.yml
- run: make ci
```

**Tím zmizí „u mě to prochází".** Konfigurace pipeline se scvrkne na jeden řádek a co se spouští, je vidět v Makefile — kde to má každý po ruce.

### Databáze

```makefile
.PHONY: db-reset
db-reset: ## Smaže databázi a postaví ji znovu i s fixturami
	$(PHP) bin/console doctrine:database:drop --force --if-exists
	$(PHP) bin/console doctrine:database:create
	$(MAKE) db-migrate
	$(PHP) bin/console doctrine:fixtures:load --no-interaction
```

Cíl, který se používá častěji, než by člověk čekal — pokažená lokální databáze je otázka na dvě minuty místo na půl hodiny.

### Shell do kontejneru

```makefile
.PHONY: sh
sh: ## Otevře shell v PHP kontejneru
	$(PHP) sh
```

Drobnost, která ušetří spoustu psaní. (V odlehčených obrazech je `sh`, ne `bash` — `bash` tam často není nainstalovaný.)

---

## Časté chyby

| Chyba | Projev | Jak správně |
| ----- | ------ | ----------- |
| Odsazení mezerami | `*** missing separator. Stop.` | TAB; do `.editorconfig` `indent_style = tab` |
| Chybí `.PHONY` | `make: 'test' is up to date.` | `.PHONY` u každého cíle |
| Víc řádků jako jeden skript | `cd` na dalším řádku už neplatí | `&&` na jednom řádku |
| `.ONESHELL:` | Na macOS se tiše chová postaru | Nepoužívat; `&&` funguje všude |
| Chybí `-T` v CI | `the input device is not a TTY` | `ifdef CI` a přidat `-T` |
| Chybí `--user` | `vendor/` patří rootovi a nejde smazat | `--user $(UID):$(GID)` |
| Jedno `$` u shellové proměnné | Make ji sní a vloží prázdno | `$$HOME` |
| `make` bez cíle spustí první v pořadí | Někdo omylem smaže databázi | `.DEFAULT_GOAL := help` |
| Cíl bez `## popisu` | V `make help` chybí a nikdo o něm neví | Popis ke každému veřejnému cíli |
| Makefile jen obaluje composer scripts | Dvě místa, kde se totéž definuje | Buď jedno, nebo druhé — [viz níž](#kdy-to-nepotřebuješ) |
| Cíl, který se chová jinak lokálně a v CI | „U mě to prochází" | Jeden cíl `ci`, který pouští pipeline i člověk |
| `docker-compose` natvrdo v každém cíli | Přechod na v2 znamená přepsat celý soubor | Proměnná `DOCKER_COMPOSE` |

---

## Kdy to nepotřebuješ

Makefile je levný, ale ne zadarmo — je to další soubor, který se musí udržovat.

**Composer scripts** stačí, když všechno běží v PHP a lokálně:

```json
{
  "scripts": {
    "test": "phpunit",
    "check": ["@cs", "@stan", "@test"]
  }
}
```

Výhoda: nic navíc, každý PHP vývojář to zná. Nevýhoda: **nepustíš přes ně nic mimo PHP** — Docker, migrace přes `docker compose`, práci s frontendem. A `composer test` musíš stejně spustit uvnitř kontejneru, takže se ta dlouhá věta vrací.

| | **Composer scripts** | **Makefile** |
| --- | --- | --- |
| Umí volat Docker | ne (jen oklikou) | **ano** |
| Zná ho každý v PHP týmu | **ano** | většinou |
| Nápověda | `composer list` | `make help` |
| Předání argumentů | přirozené (`--`) | přes proměnnou |
| Funguje bez PHP na stroji | **ne** | **ano** |

**Nedělej obojí na totéž.** Když `make test` jen volá `composer test`, které volá `phpunit`, máš tři vrstvy a dvě místa, kde hledat. Vyber jedno.

**Novější alternativy** — [`just`](https://github.com/casey/just) a [Task](https://taskfile.dev/) — řeší přesně to, co je na Make nepohodlné: argumenty, žádné TAB, čitelnější syntaxe. Jsou lepší jako nástroj, ale **musí se instalovat**, kdežto `make` je na Linuxu i macOS předinstalovaný a v každém CI obrazu. U interního projektu, kde si tým nastavení řídí, dávají smysl; u projektu, do kterého chodí lidé zvenčí, je nulová instalace silný argument.

---

## Demo

```bash
bash Tooling/Makefile/demo/run.sh
```

Kompletní Makefile pro PHP projekt v Dockeru a ukázka, co dělá. Cíle sahající na Docker se spouštějí přes `make -n`, takže **je vidět skutečný příkaz, ale nic se nespustí** — vyzkoušet si to jde i bez nastartovaného projektu.

Sedm částí: nápověda generovaná ze souboru, co se skrývá za `make test`, předání argumentů, libovolný composer příkaz, cíl složený z jiných cílů, rozdíl mezi během lokálně a v CI, a celý start projektu jedním příkazem.

Samotný Makefile je [`demo/Makefile`](demo/Makefile) — dá se z něj vyjít a smazat, co nepotřebuješ.

---

## Zdroje

- [GNU Make Manual](https://www.gnu.org/software/make/manual/make.html)
- [Docker Compose: `exec`](https://docs.docker.com/reference/cli/docker/compose/exec/) · [`run`](https://docs.docker.com/reference/cli/docker/compose/run/)
- [`just`](https://github.com/casey/just) a [Task](https://taskfile.dev/) — modernější alternativy
