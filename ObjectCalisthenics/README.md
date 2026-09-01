# Object Calisthenics

> Jeff Bay · **2008** · *The ThoughtWorks Anthology*

## Původ

Jeff Bay sepsal devět záměrně přísných pravidel objektového návrhu — ne jako předpis pro produkci, ale jako **cvičení**. Zadání znělo: napiš tisícřádkový projekt tak, že všech devět dodržíš do posledního písmene. Ne proto, že by se tak mělo programovat, ale proto, že tě to donutí sáhnout po řešeních, na která bys jinak nepřišel. Odtud „calisthenics“ — rozcvička.

Většina pravidel je v produkci neudržitelná (žádné `else`, nejvýš dvě instanční proměnné) a to je záměr. Dvě z nich se ale osamostatnila a dnes se běžně používají jako plnohodnotné patterny: **First Class Collections** a **Wrap all primitives** (dnes známé jako Value Object).

Sbírka **nemá kategorie** — je to prostý seznam devíti pravidel.

## Devět pravidel

| # | Pravidlo | Poznámka | Stav |
| - | -------- | -------- | ---- |
| 1 | Only one level of indentation per method | Cvičení. V praxi vede k rozumnému rozpadu metod. | — |
| 2 | Don't use the ELSE keyword | Cvičení. Zbytek je early return a polymorfismus. | — |
| 3 | Wrap all primitives and strings | Osamostatnilo se jako **Value Object** (DDD). | ⬜ |
| 4 | [**First class collections**](FirstClassCollection/) | Osamostatnilo se jako plnohodnotný pattern. | ✅ |
| 5 | One dot per line | Jiná formulace **Law of Demeter**. | ⬜ |
| 6 | Don't abbreviate | Trvale platné. Není z čeho dělat pattern. | — |
| 7 | Keep all entities small | Cvičení (max 50 řádků na třídu). | — |
| 8 | No classes with more than two instance variables | Nejtvrdší pravidlo. Čistě cvičení. | — |
| 9 | No getters/setters/properties | Souvisí s principem **Tell, Don't Ask**. | ⬜ |

<sub>⬜ plánováno · 🚧 rozpracováno · ✅ hotovo · — nebude zpracováno jako samostatný pattern</sub>

## Zdroje

- Jeff Bay: *Object Calisthenics*, in: *The ThoughtWorks Anthology*, Pragmatic Bookshelf, 2008
