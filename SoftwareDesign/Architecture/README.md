# Architecture

Vzory, které se netýkají jedné třídy nebo hrstky objektů, ale **tvaru celé aplikace** — kudy vedou závislosti, co na čem smí záviset a kde jsou hranice.

## Původ

Tahle sbírka nemá jednu mateřskou knihu. Vzory v ní vznikaly samostatně, obvykle jako článek nebo přednáška konkrétního autora, a teprve časem se ukázalo, že mluví o témže z různých stran. Proto u každého uvádíme jeho vlastní původ a rok zvlášť.

Společné mají jedno pozorování: **byznys logika je to jediné, co v aplikaci opravdu stárne pomalu.** Frameworky, databáze i protokoly se mění mnohem rychleji než pravidla domény — a architektura má tenhle rozdíl respektovat, ne ho rozmazat.

## Patterny

| Pattern | Autor, rok | K čemu | Obtížnost | Stav |
| ------- | ---------- | ------ | --------- | ---- |
| [**Layered Architecture**](LayeredArchitecture/) | Buschmann a kol., 1996 | Vrstvy, z nichž každá sahá jen pod sebe; předchůdce hexagonu | ●●○○○ | ✅ |
| [**Ports & Adapters**](PortsAndAdapters/) | Alistair Cockburn, 2005 | Jádro nezávislé na okolí; závislosti míří dovnitř | ●●●●○ | ✅ |
| [**Rules Engine**](RulesEngine/) | Forgy 1979, Fowler 2009 | Byznysová pravidla jako seznam objektů, ne jako hromada `if`ů | ●●●●○ | ✅ |
| [**CQRS**](CQRS/) | Meyer 1988, Greg Young 2010 | Oddělený model pro zápis a pro čtení | ●●●●○ | ✅ |
| [**Service Composition**](ServiceComposition/) | Peltz 2003, Erl 2009 | Poskládá **čtení** z víc kontextů do jednoho celku | ●●●○○ | ✅ |
| [**Saga**](Saga/) | Garcia-Molina & Salem 1987; Richardson 2018 | **Zápis** přes víc kontextů s kompenzačními akcemi | ●●●●○ | ✅ |
| [**Batching**](Batching/) | nemá jediný autor; RFC 896 (1984), EIP (2003) | Zprávy po dávkách — vyprázdni při počtu, nebo po čase | ●●●○○ | ✅ |
| [**Circuit Breaker**](CircuitBreaker/) | Michael Nygard, 2007 | Po sérii selhání přestaň volat — a pak zkus jediný pokus | ●●●○○ | ✅ |
| [**Retry**](Retry/) | praxe; backoff a jitter — Brooker, 2015 | Zopakuj, ale jen přechodnou chybu, na jedné vrstvě a s náhodou | ●●○○○ | ✅ |
| [**Clean Architecture**](CleanArchitecture/) | Robert C. Martin, 2012 | Soustředné kruhy s pravidlem závislosti; entity zvlášť od use cases | ●●●●○ | ✅ |
| Onion Architecture | Jeffrey Palermo, 2008 | Blízký příbuzný obou výše | | ⬜ |
| [**Event Sourcing**](EventSourcing/) | Martin Fowler, 2005 | Stav jako posloupnost událostí, ne jako snímek | ●●●●● | ✅ |

<sub>⬜ plánováno · 🚧 rozpracováno · ✅ hotovo</sub>

## Kam pokračovat od kompozice

Tři vzory níž tvoří jednu řadu a je dobré vědět, v jakém pořadí se po nich sahá:

1. [**Service Composition**](ServiceComposition/) — poskládá **čtení** z víc kontextů. Bezpečné, běžné, začni tady.
2. [**Saga**](Saga/) — když skládáš **zápis** a potřebuješ kompenzace při částečném selhání.
3. [**Process Manager**](Saga/#process-manager-sága-která-si-pamatuje) — sága, která si drží stav procesu. Není to samostatný vzor, je to sága, která přežije restart.

Čtvrtou možností je se orchestraci vyhnout úplně a nechat kontexty reagovat na [události](../DDD/DomainEvent/) — tomu se říká **choreografie**, je to protipól všech tří výše a [Saga](Saga/#orchestrace-nebo-choreografie) ji rozebírá.

## Poznámka k obtížnosti

Architektonické vzory mají obtížnost systematicky vyšší než ostatní patterny, a není to tím, že by byly těžké na pochopení. Jsou těžké na **rozhodnutí**: platí se za ně předem, výhoda přijde až za rok, a když se zavedou tam, kam nepatří, zůstane jen ta cena. U každého proto čti sekci *Kdy nepoužít* dřív než ostatní.
