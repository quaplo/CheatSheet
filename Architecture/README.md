# Architecture

Vzory, které se netýkají jedné třídy nebo hrstky objektů, ale **tvaru celé aplikace** — kudy vedou závislosti, co na čem smí záviset a kde jsou hranice.

## Původ

Tahle sbírka nemá jednu mateřskou knihu. Vzory v ní vznikaly samostatně, obvykle jako článek nebo přednáška konkrétního autora, a teprve časem se ukázalo, že mluví o témže z různých stran. Proto u každého uvádíme jeho vlastní původ a rok zvlášť.

Společné mají jedno pozorování: **byznys logika je to jediné, co v aplikaci opravdu stárne pomalu.** Frameworky, databáze i protokoly se mění mnohem rychleji než pravidla domény — a architektura má tenhle rozdíl respektovat, ne ho rozmazat.

## Patterny

| Pattern | Autor, rok | K čemu | Obtížnost | Stav |
| ------- | ---------- | ------ | --------- | ---- |
| [**Ports & Adapters**](PortsAndAdapters/) | Alistair Cockburn, 2005 | Jádro nezávislé na okolí; závislosti míří dovnitř | ●●●●○ | ✅ |
| [**Rules Engine**](RulesEngine/) | Forgy 1979, Fowler 2009 | Byznysová pravidla jako seznam objektů, ne jako hromada `if`ů | ●●●●○ | ✅ |
| Clean Architecture | Robert C. Martin, 2012 | Soustředné vrstvy s pravidlem závislosti | | ⬜ |
| Onion Architecture | Jeffrey Palermo, 2008 | Blízký příbuzný obou výše | | ⬜ |
| CQRS | Greg Young, 2010 | Oddělený model pro zápis a pro čtení | | ⬜ |
| Event Sourcing | Martin Fowler, 2005 | Stav jako posloupnost událostí, ne jako snímek | | ⬜ |
| Saga | Garcia-Molina, Salem, 1987 | Dlouhá transakce přes víc služeb, s kompenzacemi | | ⬜ |

<sub>⬜ plánováno · 🚧 rozpracováno · ✅ hotovo</sub>

## Poznámka k obtížnosti

Architektonické vzory mají obtížnost systematicky vyšší než ostatní patterny, a není to tím, že by byly těžké na pochopení. Jsou těžké na **rozhodnutí**: platí se za ně předem, výhoda přijde až za rok, a když se zavedou tam, kam nepatří, zůstane jen ta cena. U každého proto čti sekci *Kdy nepoužít* dřív než ostatní.
