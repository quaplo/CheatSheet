# CLAUDE.md

## O projektu

Týmový katalog **návrhových vzorů** s ukázkami v PHP. Slouží jako interní příručka: juniorům jako výuka od problému k řešení, seniorům jako rychlé připomenutí. Není to knihovna ani balíček — je to **dokumentace se spustitelnými příklady**.

Patterny se přidávají postupně, ad hoc podle toho, které tým zrovna považuje za důležité. Repozitář proto nikdy není „hotový“ a nekompletnost není chyba.

## Jazyková konvence — dodržuj bez výjimky

| Co | Jazyk |
| -- | ----- |
| Texty v README, popisy, vysvětlení | **čeština** |
| Komentáře v PHP kódu, PHPDoc popisy | **čeština** |
| Názvy tříd, metod, proměnných, konstant | **angličtina** |
| Názvy složek a souborů | **angličtina** |
| Názvy patternů | **angličtina** (český překlad jen v závorce u nadpisu) |

Komunikace s uživatelem: **slovensky**.

## Struktura

```
README.md                      # rozcestník: sbírky (každá s tabulkou svých patternů) + index podle problému
CLAUDE.md
_template/
    README.md                  # postup přidání patternu + checklist
    PATTERN.md                 # šablona popisu patternu
    demo/run.php               # kostra spustitelného dema
<Zdroj>/
    README.md                  # původ sbírky (kdo, kdy, proč) + katalog patternů
    <Kategorie>/               # jen u sbírek, které kategorie mají (GoF)
        README.md
        <PatternName>/
            README.md          # samotný pattern podle šablony
            demo/              # jen u složitějších implementací
```

Členění kopíruje **původ** patternu, ne abecedu. GoF má kategorie (`Creational` / `Structural` / `Behavioral`), novější sbírky často ne — pak je struktura `<Zdroj>/<PatternName>/`.

Plánované sbírky: GoF (1994) · PoEAA (Fowler, 2002) · DDD (Evans, 2003) · EIP (Hohpe & Woolf, 2003) · Architecture (Hexagonal, Clean, CQRS, Event Sourcing).

## Struktura popisu patternu

Šablona je v `_template/PATTERN.md`. Povinné sekce: **Původ** (zdroj, autoři, rok, kategorie, obtížnost + kontext vzniku) · **Problém** (včetně ukázky „špatného“ kódu) · **Řešení** (+ Mermaid diagram) · **Účastníci** · **Implementace v PHP** · **Kdy použít / Kdy nepoužít** · **Časté chyby** · **Související patterny**. Nepovinné: *V praxi*, *Demo*, *Zdroje*.

Každý pattern má **na konci souboru** blok `Metadata patternu` — `<details>` s YAML fencem (`name`, `name_cs`, `category`, `source`, `authors`, `year`, `difficulty`, `tags`, `related`, `status`). Drž klíče konzistentní, do budoucna z nich půjde generovat přehledové tabulky. **Nedávej metadata jako YAML frontmatter na začátek** — GitHub ho vykreslí jako tabulku nad nadpisem a zaclání obsahu.

Referenční hotový pattern: **`GoF/Behavioral/Strategy/README.md`**. Když si nejsi jistý tónem, hloubkou nebo formátem, řiď se jím.

## Psaní obsahu

- Vysvětluj **od problému, ne od definice**. Junior musí nejdřív poznat situaci ve vlastním kódu.
- Sekce *Kdy nepoužít* a *Časté chyby* jsou stejně důležité jako popis řešení — právě ony brání tomu, aby junior cpal patterny všude.
- Piš konkrétně: skutečné třídy, skutečné knihovny. Žádné `Foo`/`Bar` a žádné obecné fráze.
- Doménový příklad je napříč patterny **jednotný — e-shop / objednávky** — aby juniorovi odpadalo přepínání kontextu.
- Zmiň, jestli pattern v moderním PHP ještě dává smysl, nebo ho nahradil jazykový prvek (enum, closure, first-class callable) či DI kontejner.

## Kód

- PHP **8.3+**, `declare(strict_types=1)`, striktní typy všude.
- **Bez frameworků a bez závislostí** — ukázka musí jít zkopírovat a spustit.
- Demo se spouští `php <cesta>/demo/run.php`, závislosti přes `require`, žádný composer.
- Peníze v haléřích jako `int`, nikdy `float`.
- Po každé změně v `demo/` spusť skript a ověř, že proběhne bez chyby.

## Přidání nového patternu — povinný postup

Po vytvoření nebo úpravě patternu **vždy aktualizuj i navazující dokumenty**, jinak je pattern neviditelný:

1. `<Zdroj>/<Kategorie>/<PatternName>/README.md` — vyplněná šablona
2. `demo/` — jen když je implementace na víc než ~60 řádků nebo má víc spolupracujících tříd; ověř spuštěním
3. **`README.md` v kořeni** → sekce *Sbírky*, tabulka patternů **pod popisem odpovídající sbírky**
   (jde-li o první pattern dané sbírky: sundej u ní `⬜`, založ tabulku a doplň pod ni odkaz na kompletní katalog)
4. **`README.md` v kořeni** → *Index podle problému* (aspoň jeden řádek, formulovaný jako symptom, ne jako název patternu)
5. **`README.md` v kořeni** → *Kudy začít*, pokud jde o pattern vhodný pro začátečníky
6. **`<Zdroj>/README.md`** → katalog, přepni stav `⬜` → `✅` a přidej odkaz
7. **`<Zdroj>/<Kategorie>/README.md`** → totéž, včetně obtížnosti
8. **Sekce *Související patterny*** u všech patternů, na které nový odkazuje — odkazy dělej **obousměrné**
9. Zkontroluj, že žádný odkaz nevede na neexistující složku (na nehotové patterny odkazuj **tučným textem, ne odkazem**)

Legenda stavů v katalozích: `⬜` plánováno · `🚧` rozpracováno · `✅` hotovo.
