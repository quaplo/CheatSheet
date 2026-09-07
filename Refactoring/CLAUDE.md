# CLAUDE.md — sekce Refaktoring

Pravidla pro tuhle sekci. Společná pravidla celého repozitáře jsou v [kořenovém `CLAUDE.md`](../CLAUDE.md).

## O sekci

Jak měnit kód, který už běží. Dvě úrovně s vlastními složkami:

- **[`Code/`](Code/)** — změny v jednom procesu, které nemění chování. Vybíráme **jen ty, které vedou k některému ze vzorů v [`SoftwareDesign/`](../SoftwareDesign/)**; kompletní katalog vede Fowler a nepřepisujeme ho.
- **[`System/`](System/)** — změny za provozu, které trvají týdny a musí jít vrátit.
- **[`TwoHats/`](TwoHats/)** — pravidlo, na kterém stojí všechny ostatní.
- **[`TddRefactoring/`](TddRefactoring/)**, **[`PreparatoryRefactoring/`](PreparatoryRefactoring/)**, **[`CharacterizationTests/`](CharacterizationTests/)**, **[`ComprehensionRefactoring/`](ComprehensionRefactoring/)**, **[`LitterPickupRefactoring/`](LitterPickupRefactoring/)** **[`PlannedRefactoring/`](PlannedRefactoring/)** a **[`LongTermRefactoring/`](LongTermRefactoring/)** — *kdy* se refaktoring do práce dostává a *s čím*; leží na úrovni sekce, protože se netýkají jedné úrovně, ale všech technik. Dohromady pokrývají sedm workflow, která popsal Fowler.

## Struktura

Cesty níž jsou relativní k `Refactoring/`.

```
README.md                      # rozcestník, obě úrovně, kdy vůbec refaktorovat
CLAUDE.md                      # tenhle soubor
_template/
    README.md                  # postup přidání + checklist
    REFACTORING.md             # šablona
TwoHats/                          # pravidlo nad oběma úrovněmi
    README.md
    demo/
TddRefactoring/                   # třetí krok cyklu — mimo obě úrovně
    README.md
    demo/
PreparatoryRefactoring/           # kdy refaktorovat — mimo obě úrovně
    README.md
    demo/
CharacterizationTests/            # s čím — mimo obě úrovně
    README.md
    demo/
ComprehensionRefactoring/         # jak porozumět — mimo obě úrovně
    README.md
    demo/
LitterPickupRefactoring/          # kde přestat — mimo obě úrovně
    README.md
    demo/
PlannedRefactoring/               # vyhrazený čas — mimo obě úrovně
    README.md
    demo/
LongTermRefactoring/              # měsíce v hlavní větvi — mimo obě úrovně
    README.md
    demo/
Code/
    README.md
    <RefactoringName>/README.md
System/
    README.md
    <TechniqueName>/README.md
    <TechniqueName>/demo/      # u technik, kde jde postup ukázat spuštěním
```

Složka se jmenuje **anglicky podle zavedeného názvu** (`BranchByAbstraction`, `StranglerFig`, `ReplaceConditionalWithPolymorphism`).

## Tvar dokumentu

Šablona je v `_template/REFACTORING.md`. **Dodržuj její pořadí sekcí:**

1. Nadpis + odkaz zpět + shrnutí *V jedné větě*
2. **Kdy po tom sáhnout** — situace, ne definice (povinné)
3. **Předtím** — stav, ze kterého se vychází, včetně kódu (povinné)
4. **Mechanika** — očíslované kroky, u každého co po něm platí (povinné)
5. **Průběh a návratová cesta** — **povinné v `System/`**, nepovinné v `Code/`
6. **Jak ověřit, že to funguje** (povinné)
7. **Co to stojí** (povinné)
8. **Kdy to nedělat** (povinné)
9. **Časté chyby** (povinné)
10. **Kam to vede** — odkaz na výsledný vzor; **povinné v `Code/`**
11. *Demo* (nepovinné)
12. **Související** (povinné)
13. **Původ** (povinné)
14. *Zdroje* (nepovinné)
15. *Metadata* — `<details>` s YAML na konci souboru, nikdy jako frontmatter

## Psaní obsahu

Platí [pravidla psaní pro celý repozitář](../CLAUDE.md#psaní-obsahu--platí-v-celém-repozitáři). Navíc pro tuhle sekci:

- **Mechanika je jádro dokumentu, ne dodatek.** Čtenář sem chodí pro postup, ne pro popis cíle. U každého kroku napiš, **co po něm musí platit** — jinak nikdo nepozná, kdy je bezpečné pokračovat.
- **Každý krok musí být bezpečný sám o sobě.** Postup s mezistavem „a teď hodinu nic nefunguje" není refaktoring. Když technika takový mezistav má, je to její hlavní nevýhoda a patří do *Co to stojí*.
- **Testy nejsou volitelné.** Refaktoring bez testů je změna chování, o které nevíš. Kde testy chybí, je jejich doplnění krok nula.
- **Napiš, co technika stojí.** Abstrakce navíc, dvojí implementace, delší doba do dokončení, kód, který je půl roku ošklivější než na začátku. Bez toho vypadá každá technika lákavěji, než je.
- **U `System/` je návratová cesta povinná.** Otázka „co když to nevyjde" je u velkých změn důležitější než „jak to udělat".
- **U `Code/` dokument končí odkazem na vzor.** Refaktoring je cesta, vzor je cíl — a ten se vysvětluje jen jednou, v [`SoftwareDesign/`](../SoftwareDesign/).
- **Nepřepisuj Fowlerův katalog.** Je online zadarmo. Přidávej to, co v něm není: kroky v našem kontextu, PHP a odkaz na vzor.
- **Náročnost měř rizikem a délkou, ne počtem kroků.** Technika o třech krocích, která běží tři měsíce na produkci a dá se udělat tiše špatně, není jednička.

## Přidání nové techniky — povinný postup

1. `<Úroveň>/<Název>/README.md` — vyplněná šablona
2. `demo/` — u technik, kde jde postup ukázat spuštěním; ověř spuštěním
3. **`README.md` sekce** → tabulka v příslušné úrovni, stav `⬜` → `✅`
4. **`Code/README.md`** nebo **`System/README.md`** → totéž, včetně náročnosti
5. **U `Code/`**: odkaz na cílový vzor a **zpětný odkaz z něj** — vzor má u sebe zmínit, jak se k němu dá dojít
6. **Sekce *Související*** u všech dokumentů, na které nový odkazuje — obousměrně
7. Zkontroluj, že žádný odkaz nevede na neexistující složku (na nehotové **tučně, ne odkazem**)
