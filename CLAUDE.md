# CLAUDE.md

## O repozitáři

**Interní edukační dokumentace týmu.** Sbírka věcí, které se opakovaně vysvětlují — návrhové vzory, procesy, postupy. Juniorům slouží jako výuka od problému k řešení, seniorům jako rychlé připomenutí.

Není to knihovna ani balíček. Je to **dokumentace se spustitelnými příklady**, kde je to možné.

Obsah přibývá ad hoc, podle toho, co je zrovna potřeba vysvětlit. Repozitář proto nikdy není „hotový“ a **nekompletnost není chyba**.

## Struktura

```
README.md                      # rozcestník sekcí
CLAUDE.md                      # tenhle soubor — pravidla celého repozitáře
<Sekce>/
    README.md                  # rozcestník sekce
    CLAUDE.md                  # pravidla, jak se v téhle sekci píše
    _template/                 # šablona a checklist (má-li sekce vlastní tvar dokumentu)
    …
```

Sekce je **složka v kořeni s vlastním `README.md`**. Každá má svůj vlastní tvar dokumentu, svůj checklist a svoje `CLAUDE.md` — pravidla pro popis návrhového vzoru se nedají použít na popis firemního procesu a naopak.

**Nejdřív si přečti `CLAUDE.md` té sekce, ve které pracuješ.** Tenhle soubor obsahuje jen to, co platí všude.

Existující sekce:

| Sekce | Čím se řídí |
| ----- | ----------- |
| [`SoftwareDesign/`](SoftwareDesign/) | [`SoftwareDesign/CLAUDE.md`](SoftwareDesign/CLAUDE.md) |
| [`GitWorkflows/`](GitWorkflows/) | [`GitWorkflows/CLAUDE.md`](GitWorkflows/CLAUDE.md) |
| [`Processes/`](Processes/) | [`Processes/CLAUDE.md`](Processes/CLAUDE.md) |

## Jazyková konvence — dodržuj bez výjimky

| Co | Jazyk |
| -- | ----- |
| Texty v README, popisy, vysvětlení | **čeština** |
| Komentáře v kódu, PHPDoc popisy | **čeština** |
| Názvy tříd, metod, proměnných, konstant | **angličtina** |
| Názvy složek a souborů | **angličtina** |

Komunikace s uživatelem: **slovensky**.

## Psaní obsahu — platí v celém repozitáři

- **Vysvětluj od problému, ne od definice.** Čtenář musí nejdřív poznat situaci ve vlastní práci. Teprve pak dává řešení smysl.
- **Sekce „kdy to nepoužít“ a „časté chyby“ jsou stejně důležité jako popis řešení.** Bez nich se nový nástroj cpe všude.
- **Piš konkrétně.** Skutečné třídy, skutečné knihovny, skutečné situace. Žádné `Foo`/`Bar` a žádné obecné fráze.
- **Termín vysvětli dřív, než ho použiješ.** Nový pojem nesmí poprvé zaznít v tabulce, v porovnání nebo v seznamu — tam už musí být známý. A vysvětlení začni tím, **co to je**, ne tím, co to stojí nebo kdy to nepoužít.
- **Tvrzení podlož, neodhaduj.** Když píšeš, že je něco pomalé, drahé nebo časté, přilož měření nebo zdroj. Odhad vydávaný za fakt je horší než přiznaná neznalost.
- **Ukotvi novou věc v něčem, co čtenář už zná.** Nejrychlejší cesta od definice k pochopení vede přes příklad, který už viděl.
- **Nepiš nic o konkrétní firmě, platformě nebo interních nástrojích, co není podložené.** Když něco tvrdíš o tom, jak to funguje „u nás“, musí to být ověřené, ne odvozené.
- **Prolinkovávej.** Pojem se vysvětluje na jednom místě; odjinud se na něj odkazuje. **Po napsání odkaz ověř**, ne odhadni.
- **Na nehotové dokumenty odkazuj tučným textem, ne odkazem.** Odkaz na neexistující složku je rozbitý odkaz.

## Přidání nové sekce

1. Založ složku a v ní `README.md` — co sekce obsahuje, pro koho je, rozcestník na dokumenty.
2. Založ `<Sekce>/CLAUDE.md` — tvar dokumentu, checklist při přidání, na co si dát pozor.
3. Zvaž `<Sekce>/_template/` se šablonou a checklistem. **Šablona je to, co drží kvalitu**, když dokumentů přibývá.
4. Přidej řádek do tabulky sekcí v kořenovém [`README.md`](README.md) i v tomhle souboru.

## Práce s repozitářem

- **Commituj po jednom dokumentu.** Jeden vzor, jeden proces, jeden commit — včetně všech navazujících aktualizací.
- **Po každé změně ověř odkazy.** Ani jeden nesmí vést na neexistující soubor nebo kotvu.
- **Po každé změně ve spustitelných ukázkách je spusť.** Ukázka, která nedoběhne, je horší než žádná.
