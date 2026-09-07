# Refaktoring systému

> [← zpět na Refaktoring](../)

Změny, které **běží za provozu**, trvají týdny až měsíce a jejich mezistav vidí zákazník. Tím se liší od [refaktoringu kódu](../Code/) ve všem podstatném.

---

## Čím se to liší

U velké změny se nejdůležitější otázka posouvá. Není to *„jak to udělat"*, ale:

> **Co se stane, když se to nepovede — a jak dlouho bude trvat, než se vrátíme?**

Proto má každý dokument v téhle úrovni povinnou sekci *Průběh a návratová cesta*. Technika bez ní není technika, ale plán s nadějí.

---

## Katalog

| Technika | K čemu | Návratová cesta | Náročnost | Stav |
| -------- | ------ | --------------- | --------- | ---- |
| [**Branch by Abstraction**](BranchByAbstraction/) | Výměna implementace za běhu; obě verze existují vedle sebe za společným rozhraním | přepnutí zpět, okamžité | ●●●○○ | ✅ |
| [**Strangler Fig**](StranglerFig/) | Postupné obalení starého systému novým, dokud starý nezmizí | část po části | ●●●●○ | ✅ |
| [**Parallel Run**](ParallelRun/) | Obě verze běží současně, výsledky se porovnávají; nová **nikdy neodpovídá** | vypnutí experimentu | ●●○○○ | ✅ |
| [**Expand–Contract**](ExpandContract/) | Změna rozhraní nebo schématu bez výpadku; obě verze fungují vedle sebe | vratné až do fáze contract | ●●●○○ | ✅ |

<sub>⬜ plánováno · 🚧 rozpracováno · ✅ hotovo</sub>

---

## Co mají společné

Všechny čtyři stojí na téže myšlence: **místo jedné velké změny udělej řadu malých, z nichž každá je sama o sobě bezpečná** — a mezi nimi se dá kdykoli zastavit.

Praktické důsledky, které platí u všech:

- **Obě verze nějakou dobu existují vedle sebe.** To je cena, ne chyba návrhu.
- **Přepínání patří za [feature flag](../../GitWorkflows/Glossary.md#feature-flag)**, ne za nasazení. Nasazení a vydání jsou dvě různé věci.
- **Mezistav je ošklivější než začátek i konec.** Kdo to nečeká, techniku uprostřed opustí — a zůstane v tom nejhorším bodě.
- **Bez [průběžné integrace](../../GitWorkflows/TrunkBasedDevelopment/) to nefunguje.** Změna, která žije měsíce ve větvi, není postupná.
