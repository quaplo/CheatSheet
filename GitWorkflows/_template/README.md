# Šablona — jak přidat workflow

> [← zpět na Git Workflows](../)

## Postup

1. **Založ složku** pojmenovanou anglicky podle zavedeného názvu: `GitFlow`, `GitHubFlow`, `TrunkBasedDevelopment`.
2. **Zkopíruj šablonu** jako `README.md`:
   ```bash
   cp _template/WORKFLOW.md <WorkflowName>/README.md
   ```
3. **Vyplň ji** — pořadí sekcí neměň, komentáře `<!-- … -->` po vyplnění smaž.
4. **Projdi checklist** níž.

---

## Na co si dát pozor

**Sekce *Co si to vyžaduje* je jádro, ne dodatek.** Modely větvení vypadají na diagramu podobně a liší se právě tím, co musí být okolo. Když ji odbudeš, dokument nepomůže vybrat — a to je jeho jediný účel.

**Diagram a *Běžný den* si nesmí odporovat.** Když diagram ukazuje merge do `develop` a příkazy `git switch main`, čtenář neví, čemu věřit. Po napsání obojí porovnej.

**Ke každému pravidlu napiš následek.** „Nemergeuj `develop` do feature větve“ je zákaz. „Nemergeuj `develop` do feature větve — historie se zamotá a v PR pak vidíš i cizí commity“ je poučení, které si někdo zapamatuje.

**Nedělej sekci „Výhody a nevýhody“.** Už tam je — v *Co si to vyžaduje*, *Kdy nepoužít* a *Častých chybách*, jen formou, podle které se dá jednat. Zvlášť sepsaná výhoda navíc nemá v jednom dokumentu voči čemu být: „bezpečný“ dává smysl jen oproti něčemu jinému. Porovnání patří do [srovnávací tabulky](../README.md) v rozcestníku.

**Piš o kompromisu, ne o vítězi.** Když u workflow nedokážeš napsat poctivou sekci *Kdy nepoužít*, ještě mu dost nerozumíš. Model, který nemá nevýhody, je model, který jsi nepochopil.

**Provozní náročnost není počet větví.** Měř ji tím, kolik toho musí tým dodržet, jak snadno se to dá udělat tiše špatně a co se stane, když se na to vykašle. Model o dvou větvích, který bez zelené CI a feature flagů rozbije produkci, není jednička.

---

## Checklist

Po vytvoření nebo úpravě workflow projdi celý seznam — jinak zůstane dokument neviditelný nebo nekonzistentní.

- [ ] `<WorkflowName>/README.md` podle šablony, pořadí sekcí zachované
- [ ] Mermaid `gitGraph` se vykresluje a **odpovídá sekci *Běžný den***
- [ ] Příkazy jsou konkrétní a spustitelné, ne abstraktní popis kroků
- [ ] Sekce *Co si to vyžaduje* má u každého předpokladu napsané, **co se stane bez něj**
- [ ] Sekce *Kdy nepoužít* není formalita — obsahuje aspoň dva reálné důvody
- [ ] Tabulka *Pro jaký tým a projekt* má vyplněný řádek **Stabilizační fáze** — rozhoduje víc než metodika
- [ ] Dokument **nemá** sekci „Výhody a nevýhody“ — patří do srovnání v rozcestníku
- [ ] *Časté chyby* mají u každé konkrétní následek, ne jen „je to špatně“
- [ ] *Nastavení v GitHubu / GitLabu* popisuje možnosti nástroje, ne naši konfiguraci
- [ ] **[`README.md`](../README.md) sekce** → srovnávací tabulka doplněná
- [ ] **[`README.md`](../README.md) sekce** → katalog, stav přepnutý `⬜` → `✅`
- [ ] **[`README.md`](../README.md) sekce** → *Jak vybrat*, pokud nový workflow mění rozhodování
- [ ] **[`Glossary.md`](../Glossary.md)** → doplněné pojmy, které dokument zavádí
- [ ] *Související workflow* — odkazy **obousměrné** u všech zmíněných
- [ ] Žádný odkaz nevede na neexistující složku; na nehotová workflow **tučný text, ne odkaz**
- [ ] Metadata v `<details>` **na konci souboru**, ne jako frontmatter
