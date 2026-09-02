# PoEAA — Patterns of Enterprise Application Architecture

> Martin Fowler · **2002** · *Patterns of Enterprise Application Architecture*

## Původ

Fowler sesbíral vzory z podnikových aplikací konce 90. let — systémů, které mají netriviální doménovou logiku a přitom musí data někam ukládat a odněkud číst. Kniha vznikla z pozorování, že tahle hranice mezi doménou a databází je místo, kde se projekty rozpadají nejčastěji: buď se doména utopí v SQL, nebo se kolem ní postaví tolik vrstev, že se v tom nikdo nevyzná.

Většina vzorů v knize je dnes zabudovaná v ORM — Doctrine ti Data Mapper, Unit of Work i Identity Map dává, aniž bys je psal. **To ale neznamená, že je nemusíš znát:** bez nich nepochopíš, proč `flush()` dělá to, co dělá, proč se tentýž objekt načtený dvakrát rovná sám sobě, ani kde přesně tvoje repository končí a ORM začíná.

Sbírka **nemá kategorie** — je to plochý katalog.

## Patterny

| Pattern | K čemu | Obtížnost | Stav |
| ------- | ------ | --------- | ---- |
| [**Repository**](Repository/) | Kolekcí se tvářící rozhraní nad persistencí | ●●●○○ | ✅ |
| [**Service Layer**](ServiceLayer/) | Hranice aplikace jako sada operací — dnes spíš třída na use-case | ●●○○○ | ✅ |
| [**Data Mapper**](DataMapper/) | Překlad mezi objektem a řádkem, bez vazby v obou směrech | ●●●○○ | ✅ |
| Unit of Work | Sledování změn a jeden zápis na konci | | ⬜ |
| Identity Map | Tentýž záznam načtený dvakrát je tentýž objekt | | ⬜ |
| Active Record | Objekt, který si sám umí uložit — protipól Data Mapperu; [srovnání](DataMapper/#data-mapper-vs-active-record) je u něj | | ⬜ |
| Money | Konkrétní value object pro peníze | | ⬜ |
| Lazy Load | Data se načtou, až když je někdo potřebuje | | ⬜ |
| Special Case | Zvláštní instance místo `null` | | ⬜ |

<sub>⬜ plánováno · 🚧 rozpracováno · ✅ hotovo</sub>

## Zdroje

- Martin Fowler: *Patterns of Enterprise Application Architecture*, Addison-Wesley, 2002
- [martinfowler.com/eaaCatalog](https://martinfowler.com/eaaCatalog/) — katalog vzorů z knihy online
