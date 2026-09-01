# GoF — Structural

> [← zpět na GoF](../)

**Strukturální vzory** řeší, jak z menších objektů skládat větší celky, aniž by vznikla nepřehledná změť závislostí. Typicky pracují s **kompozicí místo dědičnosti** — objekt obalí jiný objekt a něco k němu přidá, něco skryje nebo něco přeloží.

Většina z nich vypadá na diagramu podobně (A drží B a deleguje na něj); liší se **záměrem**, ne strukturou. Proto u nich dáváme velký důraz na sekci *Související patterny*.

## Patterny

| Pattern | K čemu | Obtížnost | Stav |
| ------- | ------ | --------- | ---- |
| Adapter | Přizpůsobení cizího rozhraní tomu, co očekává náš kód | | ⬜ |
| Bridge | Oddělení abstrakce od implementace, aby šly měnit nezávisle | | ⬜ |
| Composite | Strom objektů, se kterým se pracuje jako s jedním prvkem | | ⬜ |
| Decorator | Přidání chování obalením objektu, bez dědičnosti | | ⬜ |
| Facade | Jednoduché rozhraní před složitým subsystémem | | ⬜ |
| Flyweight | Sdílení paměťově náročných dat mezi instancemi | | ⬜ |
| Proxy | Zástupce objektu, který řídí přístup k němu | | ⬜ |

<sub>⬜ plánováno · 🚧 rozpracováno · ✅ hotovo</sub>
