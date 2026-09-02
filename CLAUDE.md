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
README.md                      # rozcestník: principy → slovníček → sbírky → index podle problému
Glossary.md                    # obecné pojmy bez vlastního dokumentu
CLAUDE.md
_template/
    README.md                  # postup přidání patternu + checklist
    PATTERN.md                 # šablona popisu patternu
    demo/run.php               # kostra spustitelného dema
Principles/
    README.md                  # rozcestník principů
    SOLID.md                   # SRP, OCP, LSP, ISP, DIP
    Simplicity.md              # KISS, YAGNI, DRY, pravidlo tří
    ObjectDesign.md            # Tell Don't Ask, Demeter, kompozice, CQS, Fail Fast
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

Šablona je v `_template/PATTERN.md`. **Dodržuj její pořadí sekcí:**

1. Nadpis + odkaz zpět + shrnutí *V jedné větě*
2. **Problém** — včetně ukázky „špatného“ kódu (povinné)
3. **Řešení** — + Mermaid diagram (povinné)
4. **Účastníci** (povinné)
5. **Implementace v PHP** (povinné)
6. **Kdy použít / Kdy nepoužít** (povinné)
7. **Časté chyby** (povinné)
8. *V praxi* (nepovinné)
9. **Související patterny** (povinné)
10. **Vztah k principům** (povinné, pokud pattern nějaký princip řeší — což platí skoro vždy)
11. *Demo* (nepovinné)
12. **Původ** — zdroj, autoři, rok, kategorie, obtížnost + kontext vzniku (povinné)
13. *Zdroje* (nepovinné)
14. *Metadata patternu* — `<details>` s YAML

Dokument jde **od problému k řešení**. Vše referenční — původ, zdroje, metadata — patří **na konec**; čtenář sem chodí kvůli tomu, co má napsat v kódu, ne kvůli tomu, kdo pattern v roce 1994 popsal.

Každý pattern má **na konci souboru** blok `Metadata patternu` — `<details>` s YAML fencem (`name`, `name_cs`, `category`, `source`, `authors`, `year`, `difficulty`, `tags`, `related`, `status`). Drž klíče konzistentní, do budoucna z nich půjde generovat přehledové tabulky. **Nedávej metadata jako YAML frontmatter na začátek** — GitHub ho vykreslí jako tabulku nad nadpisem a zaclání obsahu.

Referenční hotový pattern: **`GoF/Behavioral/Strategy/README.md`**. Když si nejsi jistý tónem, hloubkou nebo formátem, řiď se jím.

## Principy vs. patterny

Ve složce `Principles/` žijí **principy návrhu** (zatím SOLID). Platí striktní dělba:

- **Princip se vysvětluje na jediném místě — v `Principles/`.** Nikdy ho nevysvětluj znovu v README patternu.
- Pattern na princip **odkazuje**: v sekci *Vztah k principům* tabulkou `Princip | Jak souvisí`, kde je **jedna věta o vztahu**, ne definice principu.
- Odkazuj vždy na **kotvu konkrétního principu**, ne na celý dokument:
  `[OCP](../../../Principles/SOLID.md#openclosed-principle-ocp)`
  Počet `../` odpovídá zanoření: z `<Zdroj>/<Kategorie>/<Pattern>/` tři (GoF),
  z `<Zdroj>/<Pattern>/` dva (sbírky bez kategorií). **Po napsání odkaz ověř**, ne odhadni.
- Principy jsou ve **třech souborech podle tématu**, každý princip má vlastní kotvu:
  - `SOLID.md` — `#single-responsibility-principle-srp` · `#openclosed-principle-ocp` ·
    `#liskov-substitution-principle-lsp` · `#interface-segregation-principle-isp` ·
    `#dependency-inversion-principle-dip`
  - `Simplicity.md` — `#kiss--keep-it-simple` · `#yagni--you-arent-gonna-need-it` ·
    `#dry--dont-repeat-yourself` · `#pravidlo-tří`
  - `ObjectDesign.md` — `#tell-dont-ask` · `#zákon-demeter-law-of-demeter` ·
    `#kompozice-před-dědičností` · `#cqs--command-query-separation` · `#fail-fast` ·
    `#zviditelni-implicitní`
- Když „špatný“ kód v sekci *Problém* porušuje nějaký princip, **pojmenuj ho a odlinkuj rovnou tam**, u konkrétního symptomu. Junior tak vidí souvislost v momentě, kdy problém poznává.
- Zkratky principů patří i do metadat (`principles: [OCP, DIP]`).
- **Nový princip nezakládej jen kvůli jednomu patternu.** Chybí-li princip, na který chceš odkázat, buď ho doplň do **existujícího tematického souboru** (nezakládej nový soubor na jeden princip), nebo o něm napiš prostým textem bez odkazu — a pak si to poznamenej jako dluh.

## Psaní obsahu

- Vysvětluj **od problému, ne od definice**. Junior musí nejdřív poznat situaci ve vlastním kódu.
- Sekce *Kdy nepoužít* a *Časté chyby* jsou stejně důležité jako popis řešení — právě ony brání tomu, aby junior cpal patterny všude.
- Piš konkrétně: skutečné třídy, skutečné knihovny. Žádné `Foo`/`Bar` a žádné obecné fráze.
- Doménový příklad je napříč patterny **jednotný — e-shop / objednávky** — aby juniorovi odpadalo přepínání kontextu.
- **Technický pojem nepoužívej bez vysvětlení.** Buď ho vysvětli na místě jednou větou, nebo — když se opakuje ve víc patternech — přidej heslo do [`Glossary.md`](Glossary.md) a odkazuj na kotvu (idempotence, invariant, eventuální konzistence…). **Pojem, který má vlastní pattern, do slovníčku nepatří** — odkazuje se rovnou na ten dokument.
- **Nepiš nic o konkrétní firmě, platformě nebo interních nástrojích.** Katalog je obecný. Sekce *V praxi* uvádí veřejně ověřitelné věci (Symfony, Doctrine, PSR, knihovny) — ne to, jak je to zařízené „u nás“. Kdyby to někdo chtěl doplnit, musí to být podložené, ne odvozené.
- Zmiň, jestli pattern v moderním PHP ještě dává smysl, nebo ho nahradil jazykový prvek (enum, closure, first-class callable) či DI kontejner.
- **Termín vysvětli dřív, než ho použiješ.** Nový pojem nesmí poprvé zaznít v tabulce, v porovnání nebo v seznamu — tam už musí být známý. A vysvětlení začni tím, **co to je**, ne tím, co to stojí nebo kdy to nepoužít.
- **Obtížnost měř cenou správného nasazení v produkci, ne složitostí té třídy.** Počítá se do ní i infrastruktura, kterou si pattern táhne s sebou (transakce, fronty, idempotence, migrace, provoz) a to, jak snadno se dá udělat tiše špatně. Pattern, který je na jednu `readonly` třídu, ale správně funguje až s Unit of Work a outboxem, není dvojka.
- **Ukotvi pattern v něčem, co junior už zná.** Existuje-li v nativním PHP nebo v běžné knihovně věc, která pattern splňuje, ukaž ji — je to nejrychlejší cesta od definice k pochopení. `DateTimeImmutable` je Value Object, `usort($items, $comparator)` je Strategy, `foreach` je Iterator. Když k tomu jazyk nabízí i **protipříklad** (`DateTime` vedle `DateTimeImmutable`), použij ho: rozdíl učí líp než definice.

## Varianty patternu a výkon

- **Zásadní varianta patternu není druhý pattern.** Neměnná vs. měnitelná kolekce, synchronní vs. asynchronní zpracování — pokud se liší jen *jedním rozhodnutím* a zbytek (problém, řešení, účastníci, principy) je společný, popiš ji jako **podsekci v *Implementaci v PHP***, ne jako samostatnou složku. Dva dokumenty by z 80 % duplicitní obsah rozešly a junior by musel vybírat složku dřív, než pochopí rozdíl.
- Variantu vždy uzavři **tabulkou kompromisů** (`| | Varianta A | Varianta B |`) a větou, která je **výchozí volba**.
- **Výkonnostní tvrzení podlož měřením, nikdy odhadem.** Když u patternu tvrdíš, že je něco pomalé, přilož `demo/benchmark.php`, spusť ho a do README dej naměřenou tabulku s uvedenou verzí PHP a poznámkou, že jde o řád, ne absolutní čísla.
- U výkonu vždy rozliš, **co je skutečná příčina**. Často to není vlastnost patternu, ale způsob použití — a pak je správná odpověď lepší API, ne opuštění patternu.

## Kód

- PHP **8.3+**, `declare(strict_types=1)`, striktní typy všude.
- **Bez frameworků a bez závislostí** — ukázka musí jít zkopírovat a spustit.
- Demo se spouští `php <cesta>/demo/run.php`, závislosti přes `require`, žádný composer.
- Peníze v haléřích jako `int`, nikdy `float`.
- Ve výpisech dema pozor na dvě opakující se pasti: **`$proměnná` uvnitř dvojitých uvozovek** se interpoluje (chceš-li ji vypsat, použij jednoduché uvozovky) a **`printf('%-20s')` počítá bajty**, takže diakritika rozhodí zarovnání — na to je `mb_str_pad()`.
- Po každé změně v `demo/` spusť skript a ověř, že proběhne bez chyby.

## Přidání nového patternu — povinný postup

Po vytvoření nebo úpravě patternu **vždy aktualizuj i navazující dokumenty**, jinak je pattern neviditelný:

1. `<Zdroj>/<Kategorie>/<PatternName>/README.md` — vyplněná šablona
2. `demo/` — jen když je implementace na víc než ~60 řádků nebo má víc spolupracujících tříd; ověř spuštěním
3. **`README.md` v kořeni** → sekce *Sbírky*, tabulka patternů **pod popisem odpovídající sbírky**
   (pořadí sekcí je záměrné: *Principy* jsou nahoře, protože jsou stabilní a je jich pár, kdežto *Sbírky* rostou — nepřehazuj to)
   (jde-li o první pattern dané sbírky: sundej u ní `⬜`, založ tabulku a doplň pod ni odkaz na kompletní katalog)
4. **`README.md` v kořeni** → *Index podle problému* (aspoň jeden řádek, formulovaný jako symptom, ne jako název patternu)
5. **`README.md` v kořeni** → *Kudy začít*, pokud jde o pattern vhodný pro začátečníky
6. **`<Zdroj>/README.md`** → katalog, přepni stav `⬜` → `✅` a přidej odkaz
7. **`<Zdroj>/<Kategorie>/README.md`** → totéž, včetně obtížnosti
8. **Sekce *Související patterny*** u všech patternů, na které nový odkazuje — odkazy dělej **obousměrné**
9. **Sekce *Vztah k principům*** — ověř, že kotvy do `Principles/SOLID.md` existují a sedí
10. Zkontroluj, že žádný odkaz nevede na neexistující složku (na nehotové patterny odkazuj **tučným textem, ne odkazem**)

Legenda stavů v katalozích: `⬜` plánováno · `🚧` rozpracováno · `✅` hotovo.
