# CLAUDE.md — sekce Git Workflows

Pravidla pro tuhle sekci. Společná pravidla celého repozitáře jsou v [kořenovém `CLAUDE.md`](../CLAUDE.md).

## O sekci

Katalog **modelů větvení a práce s Gitem v týmu** — GitFlow, GitHub Flow, Trunk-Based Development, GitLab Flow, OneFlow. Slouží k tomu, aby si tým uměl vybrat a aby junior pochopil, proč u nás vypadá práce s větvemi tak, jak vypadá.

Sekce **nedoporučuje jeden správný workflow.** Každý je odpovědí na jinou situaci a rozdíl mezi nimi je v tom, co si vyžadují a co za to dávají. Dokument, který končí větou „tenhle je nejlepší“, je špatně napsaný dokument.

## Struktura

Cesty níž jsou relativní k `GitWorkflows/`.

```
README.md                      # rozcestník: srovnání → jak vybrat → katalog
Glossary.md                    # merge/rebase/squash, fast-forward, protected branch…
CLAUDE.md                      # tenhle soubor
_template/
    README.md                  # postup přidání workflow + checklist
    WORKFLOW.md                # šablona popisu
<WorkflowName>/
    README.md                  # samotný workflow podle šablony
```

Složka se jmenuje **anglicky a bez mezer** podle zavedeného názvu workflow (`GitFlow`, `GitHubFlow`, `TrunkBasedDevelopment`, `GitLabFlow`, `OneFlow`).

## Struktura popisu workflow

Šablona je v `_template/WORKFLOW.md`. **Dodržuj její pořadí sekcí:**

1. Nadpis + odkaz zpět + shrnutí *V jedné větě*
2. **Pro koho a proč vznikl** — situace, na kterou je odpovědí (povinné)
3. **Větve a jejich role** — tabulka (povinné)
4. **Diagram** — Mermaid `gitGraph` (povinné)
5. **Běžný den** — konkrétní příkazy od zadání po nasazení (povinné)
6. **Vydání a hotfix** — čím se workflow liší nejvíc (povinné)
7. **Co si to vyžaduje** — infrastrukturní a lidské předpoklady (povinné)
8. **Pro jaký tým a projekt / Kdy nepoužít** (povinné)
9. **Časté chyby** (povinné)
10. **Nastavení v GitHubu / GitLabu** (povinné)
11. *Přechod na jiný workflow* (nepovinné)
12. **Související workflow** (povinné)
13. **Původ** — autor, rok, kontext vzniku (povinné)
14. *Zdroje* (nepovinné)
15. *Metadata* — `<details>` s YAML

Dokument jde **od situace k řešení**. Vše referenční — původ, zdroje, metadata — patří **na konec**, stejně jako u patternů. **Metadata nedávej jako YAML frontmatter na začátek** — GitHub ho vykreslí jako tabulku nad nadpisem.

## Psaní obsahu

Platí [pravidla psaní pro celý repozitář](../CLAUDE.md#psaní-obsahu--platí-v-celém-repozitáři). Navíc pro tuhle sekci:

- **Žádný workflow není „ten správný“.** Piš o kompromisu, ne o vítězi. Když u nějakého nedokážeš napsat poctivou sekci *Kdy nepoužít*, nerozumíš mu dost.
- **Předpoklady jsou důležitější než popis větví.** Trunk-Based bez CI a feature flagů není Trunk-Based, je to chaos na `main`. GitFlow bez někoho, kdo řídí vydání, je ceremonie bez užitku. Sekce *Co si to vyžaduje* je jádro dokumentu, ne dodatek.
- **Provozní náročnost měř cenou v běžném provozu**, ne počtem větví na diagramu. Počítá se do ní, kolik toho musí tým dodržet, jak snadno se to dá udělat tiše špatně a co se stane, když se na to vykašle.
- **Příkazy piš tak, jak se opravdu píšou** — včetně `git push -u origin`, ne jen abstraktní popis kroku.
- **Řekni, co se stane, když se pravidlo poruší.** „Nemergeuj `develop` do feature větve“ je bez následku jen zákaz; s následkem je to poučení.
- **Jména větví drž konzistentní napříč dokumenty** — `main`, `develop`, `feature/*`, `release/*`, `hotfix/*`. Kde workflow používá jiné, uveď to výslovně.
- **Opakovaný pojem patří do [`Glossary.md`](Glossary.md)** a odkazuje se na kotvu (rebase, squash, fast-forward, protected branch, feature flag…).
- **Nepiš, jak to máme nastavené u nás**, dokud to není ověřené. Sekce *Nastavení v GitHubu / GitLabu* popisuje možnosti nástroje, ne naši konfiguraci.

## Diagramy

Každý workflow má **Mermaid `gitGraph`** — GitHub ho vykresluje nativně.

- Drž diagram **malý**: dva až tři commity na větev stačí. Diagram, na kterém je vidět dvacet commitů, neukazuje nic.
- Ukaž na něm **celý cyklus** — vznik větve, práci, návrat a značku vydání, je-li jaká.
- Používej `checkout` a `merge` tak, aby odpovídaly tomu, co je popsané v *Běžném dni*. **Diagram a příkazy si nesmí odporovat.**
- Commity pojmenovávej česky a konkrétně (`commit id: "Fakturace: výpočet DPH"`), ne `commit id: "C1"`.

## Přidání nového workflow — povinný postup

1. `<WorkflowName>/README.md` — vyplněná šablona
2. **`README.md` sekce** → srovnávací tabulka (přidej sloupec nebo řádek) a katalog, přepni stav `⬜` → `✅`
3. **`README.md` sekce** → *Jak vybrat*, pokud nový workflow mění rozhodování
4. **`Glossary.md`** → doplň pojmy, které dokument zavádí a používají je i ostatní
5. **Sekce *Související workflow*** u všech, na které nový odkazuje — odkazy dělej **obousměrné**
6. Zkontroluj, že žádný odkaz nevede na neexistující složku (na nehotové workflow odkazuj **tučným textem, ne odkazem**)
7. Ověř, že se Mermaid diagram vykreslí a odpovídá sekci *Běžný den*

Legenda stavů v katalogu: `⬜` plánováno · `🚧` rozpracováno · `✅` hotovo.
