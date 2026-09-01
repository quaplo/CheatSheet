# Slovníček — naše platforma

Patterny v tomhle katalogu jsou obecné. Sekce **„U nás“** u některých z nich je ale spojuje s konkrétními věcmi na naší platformě — a tenhle soubor vysvětluje pojmy, které se v nich objevují.

> [!NOTE]
> Tohle je **orientační vysvětlení pro čtení katalogu**, ne závazná dokumentace platformy. Autoritativní popis kontraktů, verzování a konkrétních postupů hledej v dokumentaci platformy; když se to rozejde, platí ona.

---

## DX zpráva

**DX = data exchange.** Asynchronní zpráva, kterou služba **publikuje** o změně svých dat, aby si z ní ostatní služby mohly poskládat vlastní pohled.

Podstatné vlastnosti:

| | |
| --- | --- |
| **Směr** | Producent publikuje, konzumenti si berou. Producent nezná své konzumenty. |
| **Tvar** | *Dokumentový* — nese ucelený stav entity, ne jen popis změny („objednávka teď vypadá takhle“, ne „změnil se sloupec X“). |
| **Kontrakt** | Veřejný a **verzovaný**. Změna tvaru je změna dohody, ne interní úprava. |
| **Doručení** | Asynchronní, přes frontu. Konzistence je eventuální. |

**V pojmech tohohle katalogu** je DX zpráva [integrační událost](DDD/DomainEvent/#doménová-událost-není-integrační) — tedy něco jiného než [doménová událost](DDD/DomainEvent/), která zůstává uvnitř služby. Z pohledu [mapy kontextů](DDD/ContextMap/) je to **Published Language**: publikovaný jazyk, kterým spolu služby mluví.

Praktický důsledek, který stojí za zapamatování: **doménovou událost ven neposílej.** Kdyby DX zpráva kopírovala vnitřní model služby, stal by se z toho modelu veřejné API a nešel by měnit bez koordinace se všemi konzumenty.

---

## SDK balíček

Knihovna, kterou služba vydává pro ty, kdo ji volají — hotový klient jejího API včetně typů požadavků a odpovědí.

**V pojmech katalogu** je to [Open Host Service](DDD/ContextMap/#katalog-vztahů): publikovaný kontrakt pro víc konzumentů najednou, místo aby si každý psal integraci po svém.

A jedno upřesnění, které se plete: **SDK není [antikorupční vrstva](DDD/AnticorruptionLayer/).** SDK mluví pojmy té *druhé* služby — je to pohodlnější způsob, jak si zavolat cizí API, ale cizí model tím do tvé domény pořád může prosáknout. Pokud se jejich model liší od tvého, patří překlad na tvou stranu.

---

## Read-model služba

Služba (nebo její část), která si z přijatých DX zpráv skládá vlastní pohled na data, optimalizovaný pro čtení.

**V pojmech katalogu** je to [CQRS](Architecture/CQRS/) na [čtvrtém stupni](Architecture/CQRS/#škála-na-které-si-vyber): oddělené čtecí úložiště plněné projekcemi. Se vším, co k tomu patří — hlavně s **eventuální konzistencí**, se kterou se musí počítat i v rozhraní pro uživatele.

---

## Služba na platformě

Samostatně nasazovaná aplikace s vlastní doménou a vlastním úložištěm.

**V pojmech katalogu** je každá služba [ohraničený kontext](DDD/BoundedContext/) — a plyne z toho, že **týž pojem znamená v každé službě něco trochu jiného**, a je to tak správně. „Objednávka“ ve fakturaci a „objednávka“ ve skladu nejsou tentýž model a nemají se sjednocovat.

Uvnitř služby je členění [hexagonální](Architecture/PortsAndAdapters/): doménové jádro, kolem něj porty, na okrajích adaptéry.
