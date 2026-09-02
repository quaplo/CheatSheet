# GoF — Behavioral

> [← zpět na GoF](../)

**Vzory chování** řeší, jak spolu objekty komunikují a jak si mezi sebou rozdělují odpovědnost. Zatímco [Creational](../Creational/) patterny řeší *vznik* objektů a [Structural](../Structural/) jejich *skládání*, tyhle řeší **tok řízení a komunikace** za běhu.

Společný jmenovatel většiny z nich: místo aby jedna třída věděla všechno a rozhodovala se podle podmínek, rozpadne se odpovědnost mezi víc objektů a rozhodnutí se přesune do polymorfismu.

## Patterny

| Pattern | K čemu | Obtížnost | Stav |
| ------- | ------ | --------- | ---- |
| [**Chain of Responsibility**](ChainOfResponsibility/) | Řetěz zpracovatelů, požadavek putuje k tomu, kdo ho umí obsloužit; v moderním PHP známý jako middleware | ●●●○○ | ✅ |
| [**Command**](Command/) | Operace zabalená do objektu — jde předat, zařadit do fronty, vrátit zpět; pozor na záměnu s commandem z CQRS | ●●○○○ | ✅ |
| Interpreter | Vyhodnocení vět jednoduchého jazyka | | ⬜ |
| [**Iterator**](Iterator/) | Průchod kolekcí bez znalosti její vnitřní struktury; v PHP hlavně generátory a práce s daty, která se nevejdou do paměti | ●○○○○ | ✅ |
| Mediator | Prostředník, přes kterého objekty komunikují místo napřímo | | ⬜ |
| Memento | Uložení a obnovení stavu objektu bez porušení zapouzdření | | ⬜ |
| [**Observer**](Observer/) | Objekt informuje odběratele o své změně; předchůdce doménových událostí | ●●○○○ | ✅ |
| [**State**](State/) | Objekt mění chování podle vnitřního stavu; v PHP 8.1+ často stačí enum | ●●●○○ | ✅ |
| [**Strategy**](Strategy/) | Zaměnitelné algoritmy za jedním rozhraním | ●●○○○ | ✅ |
| Template Method | Kostra algoritmu v předkovi, kroky v potomcích | | ⬜ |
| Visitor | Nová operace nad strukturou objektů bez zásahu do jejich tříd | | ⬜ |

<sub>⬜ plánováno · 🚧 rozpracováno · ✅ hotovo</sub>
