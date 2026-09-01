# Domain Service (Doménová služba)

> [← zpět na DDD](../)

> **V jedné větě:** Doménová operace, která nepatří žádné entitě ani hodnotě — dostane vlastní jméno a vlastní třídu, ale zůstává doménou: bez transakcí, bez databáze, bez infrastruktury.

---

## Problém

Narazíš na pravidlo, které je nepochybně doménové, ale **nemá kde bydlet**. Týká se dvou objektů naráz, nebo je to operace, kterou žádný z nich „nedělá“.

**Poznáš to podle:**

- pravidlo se týká **dvou agregátů** a ani jeden z nich není tím správným vlastníkem
- vzniknou třídy jménem `XManager`, `XHelper`, `XUtils` — jména, která neříkají nic
- nebo se pravidlo přilepí na entitu a ta pak **sahá do cizího agregátu**
- nebo skončí v aplikační vrstvě, kde ho [obejde druhá cesta dovnitř](../../PoEAA/ServiceLayer/#proč-pravidlo-v-use-case-nestačí)
- v code review se opakuje otázka *„a kam to teda dát?“* a odpověď je pokaždé jiná

```php
// Varianta A: přilepit na entitu
$alice->transferTo($bob, 500);
// Proč zdroj a ne cíl? A hlavně — Alice teď mění Boba, tedy cizí agregát.

// Varianta B: do aplikační vrstvy
final class TransferPointsHandler
{
    public function handle(...): void
    {
        if ($points < 100) { /* … */ }        // ← doménové pravidlo mimo doménu
        // …a import z CSV o něm neví
    }
}

// Varianta C: statická pomůcka
PointsUtils::transfer($alice, $bob, 500);
// Doménový pojem? Ne. Vyměnitelné v testu? Ne. Za rok tam bude všechno.
```

---

## Řešení

Dej té operaci **vlastní jméno z jazyka domény a vlastní třídu**. Zůstává to doména — jen se to nedá pověsit na žádný objekt.

```php
final readonly class LoyaltyPointsTransfer
{
    private const int MINIMUM_POINTS = 100;
    private const int FEE_PERCENT = 10;

    public function transfer(Customer $from, Customer $to, int $points): TransferReceipt
    {
        // Pravidla o DVOJICI — to je přesně to, co nemá kde jinde bydlet.
        if ($from->id->equals($to->id)) {
            throw new TransferRejected('Nelze převádět body sám sobě.');
        }

        if ($points < self::MINIMUM_POINTS) {
            throw new TransferRejected(/* … */);
        }

        if ($from->isActive() === false || $to->isActive() === false) {
            throw new TransferRejected('Převod je možný jen mezi aktivními zákazníky.');
        }

        $fee = intdiv($points * self::FEE_PERCENT, 100);

        // Vlastní pravidla si pak hlídá každý agregát sám.
        $from->redeemPoints($points);
        $to->earnPoints($points - $fee);

        return new TransferReceipt(debited: $points, fee: $fee, credited: $points - $fee);
    }
}
```

Rozdělení odpovědností je čisté a demo ho ukazuje: **služba zná pravidla o dvojici**, agregáty si dál hlídají svá.

```
Bob má jen 750 bodů, nelze uplatnit 100000.
↑ tahle výjimka přišla z Customer::redeemPoints(), ne ze služby
```

### Hranice: jeden kontext, nikdy napříč

**Nejdůležitější omezení, a v běžných popisech tohohle patternu chybí.**

Doménová služba žije **uvnitř jednoho [ohraničeného kontextu](../BoundedContext/)** a nikdy ho nepřekračuje. Smí sáhnout na víc agregátů — ale jen na ty, které do jejího kontextu patří.

```
✅ Fakturace: doménová služba počítá slevu ze slevového programu a objednávky
   → oba pojmy patří fakturaci

❌ Doménová služba, která sáhne do skladu, aby ověřila dostupnost
   → sklad je jiný kontext, má vlastní model a vlastní pravidla
```

Důvod je ten, který stojí za celým [Bounded Contextem](../BoundedContext/): cizí kontext má **vlastní invarianty a vlastní jazyk**. Ven o sobě říká jen tolik, kolik chce — přes své API, události nebo publikovaný kontrakt. Doménová služba, která si do něj sáhne, obchází jeho hranici stejně, jako by ji obcházelo přímé volání jeho repository.

**Kam tedy patří koordinace napříč kontexty:**

| Situace | Kam to patří |
| ------- | ------------ |
| Víc agregátů **téhož** kontextu | Doménová služba (tenhle pattern) |
| Víc kontextů, potřebuješ odpověď hned | [Use-case](../../PoEAA/ServiceLayer/), který volá API druhého kontextu |
| Víc kontextů, stačí konzistence za chvíli | [Doménová událost](../DomainEvent/) a reakce na druhé straně |
| Dlouhý proces přes víc kontextů s kompenzacemi | **Process manager / Saga** |
| Cizí model se liší od tvého | [Antikorupční vrstva](../AnticorruptionLayer/) na hranici |

Tvoje intuice, že cross-doménová orchestrace patří do aplikační vrstvy, je tedy **správná** — a tenhle pattern jí neodporuje. Jen řeší o úroveň níž otázku, kam s pravidlem, které je uvnitř jednoho kontextu a nepatří žádnému jeho objektu.

### Dvě rodiny, a jen o jedné panuje shoda

Pod jménem „doménová služba“ se skrývají dvě dost odlišné věci. Vyplatí se je rozlišovat, protože jedna je nesporná a druhá se v praxi zpochybňuje právem:

| | **A — výpočet nad hodnotami** | **B — operace nad víc agregáty** |
| --- | --- | --- |
| Příklad | `ExchangeRateConverter`, `PriceCalculator` | `LoyaltyPointsTransfer`, `TransferFunds` |
| Vstup a výstup | Hodnoty dovnitř, hodnota ven | Agregáty dovnitř, změněné agregáty ven |
| Mění stav | **Ne** | Ano |
| Transakce | Otázka nevzniká | **Otevřená otázka** |
| Shoda v praxi | **Úplná** | Sporná |

**Rodina A je nesporná.** Převod měny má svoje pravidlo — jak a ve prospěch koho se zaokrouhluje — a to pravidlo nepatří `Money` (musela by znát kurzy), ani `ExchangeRate` (musel by znát zaokrouhlovací politiku), ani `Currency` (je to enum). Operace je **mezi** nimi. Demo to ukazuje na rozdílu, který nikdo jiný rozhodnout nemůže:

```
kurz:  1 EUR = 24.5678 CZK
částka: 19,99 EUR

ve prospěch zákazníka     491,11 CZK
ve prospěch firmy         491,12 CZK
```

**Rodina B je sporná, a je dobré vědět proč.** Služba, která mění dva agregáty, si s sebou nese otázku, kterou sama nezodpoví: **uloží se oba v jedné transakci?**

- **Ano** → porušil jsi pravidlo [jedna transakce = jeden agregát](../Aggregate/).
- **Ne** → co když druhý zápis selže? Body zmizely a nikdo neví kde.

Existují tři legitimní odpovědi a rozhoduje se mezi nimi podle toho, **jak daleko od sebe ty agregáty jsou**:

| Řešení | Kdy | Cena |
| ------ | --- | ---- |
| **Doménová služba + jedna transakce** | Agregáty v jedné databázi, konflikty vzácné | Vědomé porušení pravidla o agregátech |
| **Vlastní agregát** `PointsTransfer` | Převod má vlastní stav a historii | Nový pojem v modelu, zákazníci se mění reakcí na událost |
| **Use-case + eventuální konzistence** | Agregáty v různých kontextech nebo službách | Složitější, ale jediná poctivá odpověď |

Praktické doporučení: **u rodiny B si nejdřív ověř, jestli to není chybějící agregát.** Slova jako *převod*, *rezervace*, *objednávka*, *žádost* často popisují věc, která si zaslouží vlastní identitu a životní cyklus — a jakmile ji dostane, doménová služba přestane být potřeba.

### Tři vlastnosti, které z toho dělají *doménovou* službu

| Vlastnost | Proč |
| --------- | ---- |
| **Bezstavová** | Jedna instance obslouží libovolně mnoho operací a nic si mezi nimi nepamatuje. Stav patří agregátům. |
| **Bez infrastruktury** | Žádné repository, žádná transakce, žádný HTTP klient. Když by je potřebovala, je to use-case, ne doménová služba. |
| **Mluví doménou** | Dovnitř jdou doménové objekty, ven doménový výsledek nebo doménová výjimka. Žádná pole, žádné skaláry bez významu. |

### Kam co patří — pořadí otázek

Nejužitečnější věc na celém patternu je vědět, **kdy ho nepoužít**. Ptej se v tomhle pořadí a doménovou službu zvol až jako poslední:

| # | Otázka | Když ano |
| - | ------ | -------- |
| 1 | Patří ta operace jedné **entitě**? | Metoda na [entitě](../Entity/) |
| 2 | Patří **hodnotě**? | Metoda na [value objectu](../ValueObject/) |
| 3 | Je to jen pravidlo **ano/ne**? | [Specification](../Specification/) |
| 4 | Je to orchestrace, transakce, načítání, události? | [Use-case](../../PoEAA/ServiceLayer/) v aplikační vrstvě |
| 5 | Doménová operace nad víc objekty, bez infrastruktury? | **Doménová služba** |

Většina „doménových služeb“ v reálných projektech neprojde už na první nebo druhé otázce. Proto ten pořádek.

### Není to application service

Dvojice, která se plete pravidelně — obojí totiž končí na „service“. Rozdíl je [rozebraný u aplikační vrstvy](../../PoEAA/ServiceLayer/#application-service-není-domain-service), ve zkratce:

| | **Doménová služba** | **Aplikační služba** (use-case) |
| --- | --- | --- |
| Vrstva | **Doména** | Aplikace |
| Obsahuje | **Doménovou logiku** | Orchestraci |
| Zná transakce, DB, fronty | **Ne** | Ano |
| Kdy vzniká | Pravidlo nepatří žádné entitě | Pro každou operaci aplikace |
| Příklad | `LoyaltyPointsTransfer` | `TransferPointsHandler` |

V praxi spolu obvykle sousedí: use-case načte agregáty, předá je doménové službě, uloží výsledek a publikuje událost. **Doménová služba přitom o žádném z těch kroků neví.**

### Proč to není metoda na entitě

Nabízí se `$alice->transferTo($bob, 500)` a čte se to hezky. Skrývá to ale dvě potíže — a druhá je vážná:

1. **Asymetrie.** Proč metoda patří zdroji a ne cíli? Pro doménu jsou obě strany rovnocenné; kód tvrdí něco jiného. To je jen signál.
2. **Cizí agregát.** Alice sahá do Boba a mění ho. Tím padá hranice [agregátu](../Aggregate/) i pravidlo *jedna transakce = jeden agregát* — a nikdo neřeší, co se stane, když druhý zápis selže.

Doménová služba tenhle problém **neruší**, jen ho pojmenuje a posune na správné místo: rozhodnutí o transakci patří aplikační vrstvě, služba vyjadřuje jen pravidlo. Že je to kompromis a ne čisté řešení, rozebírá [rodina B](#dvě-rodiny-a-jen-o-jedné-panuje-shoda) výše.

### Smí si sáhnout do repository?

Otázka, na které se neshodne ani literatura. Evans to připouští, Vernon před tím varuje. Praktické pořadí:

1. **Nejlépe:** use-case agregáty načte a **předá je službě jako parametry**. Služba zůstane čistě doménová a testuje se bez čehokoli.
2. **Když to nejde** (služba potřebuje rozhodnout, co načíst): dej jí **úzký port, který si vlastní doména** — ne celé repository. Například `ActiveCampaigns::forDate()`, ne `CampaignRepository`.
3. **Nikdy:** injektované repository, ze kterého si služba tahá, co ji napadne. Tím se z ní stane use-case pod falešným jménem.

Pravidlo palce: **když má služba v konstruktoru něco, co potřebuje databázi, zpozorni.**

### Jméno rozhoduje

Nejspolehlivější test kvality doménové služby: **dá se o ní mluvit s produkťákem?**

| ❌ | ✅ |
| --- | --- |
| `PointsManager` | `LoyaltyPointsTransfer` |
| `OrderHelper` | `ShippingCostCalculator` |
| `CurrencyUtils` | `ExchangeRateConverter` |
| `CustomerService` | `CustomerMerger` |

Jména na `-Manager`, `-Helper`, `-Utils` a `-Service` nepopisují operaci, jen prozrazují, že autor nevěděl, co to je. Navíc **přitahují smetí**: do třídy s takovým jménem přibude za rok všechno, co se nikam nevešlo.

---

## Účastníci

| Role | V příkladu | Odpovědnost |
| ---- | ---------- | ----------- |
| **Doménová služba — rodina A** | `ExchangeRateConverter` | Výpočet nad hodnotami; nic nemění |
| **Doménová služba — rodina B** | `LoyaltyPointsTransfer` | Operace nad víc agregáty; sporná, viz výše |
| **Agregáty** | `Customer` | Vlastní pravidla si hlídají dál samy |
| **Výsledek** | `TransferReceipt` | Hodnota popisující, co se stalo |
| **Doménová chyba** | `TransferRejected` | Odmítnutí v pojmech domény |
| **Use-case** | volající | Načte, zavolá službu, uloží, publikuje |

---

## Implementace v PHP

```php
final readonly class LoyaltyPointsTransfer
{
    private const int MINIMUM_POINTS = 100;
    private const int FEE_PERCENT = 10;

    public function transfer(Customer $from, Customer $to, int $points): TransferReceipt
    {
        // …pravidla o dvojici…

        $fee = intdiv($points * self::FEE_PERCENT, 100);

        $from->redeemPoints($points);
        $to->earnPoints($points - $fee);

        return new TransferReceipt(debited: $points, fee: $fee, credited: $points - $fee);
    }
}
```

`final readonly class` bez jediné vlastnosti je záměr — **bezstavovost je součást definice**. Kdyby si služba mezi voláními něco pamatovala, dvě souběžné operace by se ovlivnily.

A jak ji použije aplikační vrstva:

```php
final readonly class TransferPointsHandler
{
    public function __construct(
        private CustomerRepository $customers,
        private LoyaltyPointsTransfer $transfer,     // doménová služba jako závislost
        private EventPublisher $events,
    ) {
    }

    public function handle(TransferPoints $command): void
    {
        $from = $this->customers->get($command->fromId);
        $to = $this->customers->get($command->toId);

        $receipt = $this->transfer->transfer($from, $to, $command->points);   // ← doména rozhodne

        $this->customers->save($from);
        $this->customers->save($to);

        $this->events->publish('points.transferred', ['fee' => $receipt->fee]);
    }
}
```

Use-case načítá, ukládá a publikuje. **Rozhoduje doménová služba.**

---

## Kdy použít

- ✅ Operace je **nepochybně doménová**, ale nepatří žádné entitě ani hodnotě.
- ✅ Pravidlo se týká **víc objektů** a ani jeden z nich není přirozeným vlastníkem.
- ✅ Operace má **v byznysu jméno** — převod, konverze, sloučení, výpočet ceny.
- ✅ Chceš to pravidlo testovat bez infrastruktury.

## Kdy nepoužít

- ❌ **Operace patří entitě nebo hodnotě.** To je nejčastější případ; projdi si [pořadí otázek](#kam-co-patří--pořadí-otázek).
- ❌ **Je to jen pravidlo ano/ne.** To je [Specification](../Specification/).
- ❌ **Potřebuje transakci, databázi nebo cizí API.** To je use-case, ne doménová služba.
- ❌ **Sahá do jiného bounded contextu.** Nikdy. Tam patří [use-case, událost nebo antikorupční vrstva](#hranice-jeden-kontext-nikdy-napříč).
- ❌ **Ta operace je ve skutečnosti chybějící agregát.** Převod, rezervace, žádost — když to má vlastní stav a historii, dej tomu identitu.
- ❌ **Jako výchozí místo pro logiku.** Tudy vede cesta k [anemickému modelu](../Entity/): entity se stanou datovými strukturami a všechno chování skončí ve „službách“. To je přesně to, čemu se DDD snaží vyhnout.

> Ta poslední odrážka je hlavní riziko celého patternu. Doménová služba je legitimní nástroj a zároveň **nejpohodlnější způsob, jak si nechtěně vyrobit anemický model** — protože do ní jde vždycky všechno.

---

## Časté chyby

| Chyba | Proč vadí | Jak správně |
| ----- | --------- | ----------- |
| **Služba jako výchozí místo pro logiku** | Entity zůstanou datové struktury; vznikne anemický model | Projdi pořadí otázek; služba je poslední možnost |
| Jméno `XManager`, `XHelper`, `XUtils`, `XService` | Neříká, co to dělá, a přitahuje všechno, co se nikam nevešlo | Jméno operace z jazyka domény |
| Služba je stavová | Dvě souběžné operace se ovlivní | `final readonly`, žádné vlastnosti |
| Injektované repository | Stane se z ní use-case pod falešným jménem | Agregáty předej parametrem; nanejvýš úzký doménový port |
| Zná transakce nebo databázi | Není to doména; nejde testovat izolovaně | Infrastruktura patří do aplikační vrstvy |
| **Sahá do jiného bounded contextu** | Obchází cizí hranici i jeho invarianty stejně jako přímé volání jeho repository | Uvnitř jednoho kontextu; napříč patří use-case nebo událost |
| Mění dva agregáty a nikdo neřeší transakci | Buď porušené pravidlo o agregátech, nebo tichá ztráta dat při selhání | Vědomě zvol jednu ze [tří variant](#dvě-rodiny-a-jen-o-jedné-panuje-shoda) |
| Přebírá pravidla agregátů | Pravidlo je pak na dvou místech a rozejde se | Služba řeší dvojici, agregát sebe |
| Statická metoda místo objektu | Nejde vyměnit ani obalit, nejde ji dostat přes DI | Objekt se závislostmi, i když je nemá |
| Vrací pole nebo skalár bez významu | Volající si musí domýšlet, co dostal | Doménový výsledek jako hodnota |

---

## V praxi

- **Symfony DI** — doménová služba je obyčejná bezstavová služba; autowiring ji předá use-case bez konfigurace.
- **Testy** — poznávací znamení dobré doménové služby: test se obejde **bez mocků**. Dovnitř jdou skutečné doménové objekty.
- **Typické případy** — přepočet měn, výpočet ceny podle víc vstupů, slučování zákazníků, převody mezi účty, rozdělení částky mezi příjemce.
- **U nás** — dobrý test při review: kdyby ta třída měla v konstruktoru repository nebo klienta cizí služby, patří do aplikační vrstvy, ne do domény.

---

## Související patterny

| Pattern | Vztah |
| ------- | ----- |
| [Service Layer](../../PoEAA/ServiceLayer/) | **Nezaměňovat.** Aplikační služba orchestruje a zná infrastrukturu; doménová služba obsahuje logiku a nezná nic. Obvykle spolu sousedí. |
| [Entity](../Entity/) | První místo, kam se operace pokus dát. Doménová služba je až pro to, co se tam nevejde. |
| [Value Object](../ValueObject/) | Druhé místo. Spousta „služeb“ je ve skutečnosti chybějící hodnota. |
| [Specification](../Specification/) | Když je operace jen pravidlo ano/ne, patří sem, ne do služby. |
| [Aggregate](../Aggregate/) | Určuje hranici, kterou služba nesmí obejít — agregáty si dál hlídají svá pravidla sama. |
| [Domain Event](../DomainEvent/) | Když se dva agregáty nemají měnit v jedné transakci, konzistenci dožene událost. |
| [Bounded Context](../BoundedContext/) | **Hranice, kterou doménová služba nikdy nepřekročí.** Koordinace napříč kontexty patří o vrstvu výš. |
| [Service Composition](../../Architecture/ServiceComposition/) | **Kam patří to, co doménová služba nesmí** — koordinace napříč kontexty. Volá jen jejich veřejné use-case. |
| [Saga](../../Architecture/Saga/) | Když ta koordinace navíc **mění stav** ve víc kontextech a potřebuje kompenzace. |
| [Strategy](../../GoF/Behavioral/Strategy/) | Doménová služba s víc variantami výpočtu je Strategy — jen pojmenovaná doménově. |

---

## Vztah k principům

| Princip | Jak souvisí |
| ------- | ----------- |
| [SRP](../../Principles/SOLID.md#single-responsibility-principle-srp) | Jedna operace = jedna třída. Proti tomu stojí `CustomerService` se čtrnácti nesouvisejícími metodami. |
| [Tell, Don't Ask](../../Principles/ObjectDesign.md#tell-dont-ask) | **Pozor na obrácení.** Když služba tahá stav z entit a rozhoduje za ně, je to signál, že ta logika patřila do nich. |
| [Zviditelni implicitní](../../Principles/ObjectDesign.md#zviditelni-implicitní) | Operace, která do té doby neměla jméno, ho dostane — a dá se o ní mluvit s produkťákem. |
| [KISS](../../Principles/Simplicity.md#kiss--keep-it-simple) | Doménová služba je poslední možnost, ne první. Většina operací patří na entitu. |

---

## Demo

```bash
php DDD/DomainService/demo/run.php
```

Demo má dvě části podle [obou rodin](#dvě-rodiny-a-jen-o-jedné-panuje-shoda):

**A — nesporná:** převod měny, kde se stejná částka převede dvěma způsoby zaokrouhlení (491,11 vs 491,12 CZK). To rozhodnutí nepatří ani `Money`, ani `ExchangeRate`, ani `Currency`.

**B — sporná:** převod bodů mezi zákazníky. Ukáže pravidla o dvojici, to že **výjimka o nedostatku bodů přijde z agregátu, ne ze služby**, proč to není metoda na entitě (Alice mění Boba), bezstavovost — a na konci **otevřeně přizná, v čem je ta varianta problematická** a jaké tři odpovědi na to existují.

---

## Původ

|               |                                                    |
| ------------- | -------------------------------------------------- |
| **Zdroj**     | *Domain-Driven Design*, kapitola 5                  |
| **Autor**     | Eric Evans                                          |
| **Rok**       | 2003                                                |
| **Kategorie** | Taktické stavební bloky                             |
| **Obtížnost** | ●●○○○                                               |

Evans zavedl doménovou službu jako **výjimku, ne jako nástroj první volby**. Jeho argument byl, že objektový návrh stojí na tom, že chování bydlí u dat — ale některé operace prostě žádnému objektu nepatří, a **násilné přilepení je horší** než přiznat, že jde o samostatnou operaci. Uvedl tři podmínky: operace se vztahuje k doménovému pojmu, je bezstavová, a její rozhraní je definované v jazyce domény.

Zároveň varoval před tím, co se stalo. Slovo „service“ se ujalo natolik, že se z něj stalo výchozí místo pro veškerou logiku — a výsledkem byly modely, kde entity nedělaly nic a všechno bylo ve službách. To je přesně ten [anemický model](../Entity/), který o rok později pojmenoval Martin Fowler.

Praktický důsledek pro dnešek: **když někdo navrhne doménovou službu, první otázka má být, proč ta operace nepatří entitě.** Ve většině případů se ukáže, že patří.

---

## Zdroje

- Eric Evans: *Domain-Driven Design*, Addison-Wesley, 2003 — kapitola 5, Services
- Vaughn Vernon: *Implementing Domain-Driven Design*, Addison-Wesley, 2013 — kapitola 7
- Martin Fowler: *AnemicDomainModel*, 2003 — [martinfowler.com/bliki/AnemicDomainModel.html](https://martinfowler.com/bliki/AnemicDomainModel.html)

---

<details>
<summary>Metadata patternu</summary>

```yaml
name: DomainService
name_cs: Doménová služba
category: Taktické stavební bloky
source: DDD – Domain-Driven Design
authors: Eric Evans
year: 2003
difficulty: 2
tags: [doménová logika, bezstavovost, pojmenování, anemický model, hranice kontextu]
principles: [SRP, TellDontAsk, KISS]
related: [ServiceLayer, Entity, ValueObject, Specification, Aggregate, DomainEvent, BoundedContext, Strategy]
status: done
```

</details>
