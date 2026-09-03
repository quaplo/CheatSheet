# EIP — Enterprise Integration Patterns

> Gregor Hohpe, Bobby Woolf · **2003** · *Enterprise Integration Patterns*

## Původ

Kniha vznikla z pozorování, že integrace systémů se řešila pořád dokola stejnými způsoby, ale **bez společného slovníku** — každý si to pojmenoval po svém a domluva mezi týmy stála víc energie než samotná práce. Hohpe s Woolfem sesbírali 65 vzorů zasílání zpráv a dali jim jména, symboly a jednotné schéma. Přínos je stejný jako u [GoF](../GoF/): ne nové nápady, ale **společná řeč**.

Kniha je psaná pro dobu podnikových sběrnic a JMS, jenže vzory samotné dobu přežily beze změny. Message Router, Idempotent Receiver nebo Dead Letter Channel dnes najdeš v RabbitMQ, Kafce i v Symfony Messengeru — jen se tomu neříká EIP.

Sbírka **nemá kategorie** — je to plochý katalog.

## Patterny

| Pattern | K čemu | Obtížnost | Stav |
| ------- | ------ | --------- | ---- |
| Scatter-Gather | Rozešli dotaz víc příjemcům a posbírej odpovědi | | ⬜ |
| Process Manager | Koordinátor procesu, který si drží jeho stav — viz [Saga](../Architecture/Saga/#process-manager-sága-která-si-pamatuje) | | 🚧 |
| Message Router | Zpráva jde tam, kam podle svého obsahu patří | | ⬜ |
| Publish-Subscribe | Jedna zpráva, libovolně mnoho odběratelů | | ⬜ |
| Idempotent Receiver | Opakované doručení nesmí vyrobit druhý následek | | ⬜ |
| Dead Letter Channel | Kam s tím, co nešlo doručit ani po opakování | | ⬜ |
| Message Translator | Překlad mezi cizím a naším tvarem zprávy | | ⬜ |
| Competing Consumers | Víc konzumentů si rozebírá jednu frontu | | ⬜ |
| Correlation Identifier | Jak poznat, ke které konverzaci odpověď patří | | ⬜ |

<sub>⬜ plánováno · 🚧 rozpracováno · ✅ hotovo</sub>

## Kde už tyhle vzory v katalogu jsou

Několik z nich jsme popsali dřív, než vznikla tahle složka — protože se do katalogu dostaly z jiné strany:

| Vzor | Kde je popsaný |
| ---- | -------------- |
| **Scatter-Gather** | Mechanika čtecí [kompozice](../Architecture/ServiceComposition/): rozešli dotaz, posbírej odpovědi. Samostatný dokument zatím nemá. |
| **Process Manager** | Jako součást [Ságy](../Architecture/Saga/#process-manager-sága-která-si-pamatuje) — je to sága, která si drží stav. |
| **Idempotent Receiver** | [Sága](../Architecture/Saga/#idempotence-není-volitelná) — bez idempotence vyrobí opakované doručení druhý dobropis. |
| **Message Translator** | [Antikorupční vrstva](../DDD/AnticorruptionLayer/) je jeho doménová podoba. |
| **Publish-Subscribe** | [Doménová událost](../DDD/DomainEvent/) v integrační podobě. |

## Zdroje

- Gregor Hohpe, Bobby Woolf: *Enterprise Integration Patterns*, Addison-Wesley, 2003
- [enterpriseintegrationpatterns.com](https://www.enterpriseintegrationpatterns.com/) — katalog vzorů online
