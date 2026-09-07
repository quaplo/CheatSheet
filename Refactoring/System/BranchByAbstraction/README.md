# Branch by Abstraction

> [← zpět na Refaktoring systému](../)

> **V jedné větě:** Vlož mezi volající kód a to, co chceš vyměnit, rozhraní — a pak za ním vyměň implementaci, zatímco systém celou dobu běží.

> [!IMPORTANT]
> Název svádí k tomu, že jde o práci s větvemi ve verzovacím systému. **Je to přesně naopak.** Technika vznikla jako **alternativa k dlouhé větvi** — „větví se" v abstrakci, ne v Gitu. Právě proto ji Paul Hammant popsal v kontextu [Trunk-Based Development](../../../GitWorkflows/TrunkBasedDevelopment/).

---

## Kdy po tom sáhnout

Máš část systému, kterou je potřeba nahradit — a je moc velká na to, aby se to udělalo naráz.

**Poznáš to podle:**

- výměna by trvala **týdny**, ale systém musí mezitím fungovat
- napadlo tě založit větev a „až to bude hotové, mergnout to"
- tu část volá **hodně míst** a nedá se to vyměnit jedním commitem
- stará implementace je **nesrozumitelná**, ale funguje a nikdo si na ni netroufne
- potřebuješ **ověřit, že nová verze dává tytéž výsledky**, dřív než jí svěříš provoz
- změna se dotkne **peněz nebo zákazníka** a musí jít okamžitě vrátit

Typicky: výměna platební brány, přepis výpočtu, nová verze knihovny s jiným API, přechod z vlastního řešení na hotové.

---

## Předtím

Volající sahá přímo na konkrétní implementaci. Takových míst bývá víc.

```php
final class CheckoutService
{
    public function __construct(
        private readonly LegacyTableCalculator $shipping,   // ← konkrétní třída
    ) {}

    public function summarise(Shipment $shipment): string
    {
        $quote = $this->shipping->quoteFor($shipment);

        return sprintf('Doprava: %s', $quote->format());
    }
}
```

Dokud to takhle vypadá, **nedá se vyměnit nic** — leda naráz a všude.

---

## Mechanika

Pět kroků. Fowler je shrnuje takto: *„Create an abstraction layer […] Migrate client code […] Build a new supplier […] Switch client sections […] Remove the old supplier."*

### 0. Testy

Než se sáhne na cokoli, musí existovat testy na **současné** chování — ne na to, jak by mělo vypadat.

**Po tomhle kroku platí:** kdyby se cokoli rozbilo, poznáš to.

### 1. Vlož abstrakci

```php
interface ShippingCalculator
{
    public function quoteFor(Shipment $shipment): ShippingQuote;
}
```

Rozhraní se odvozuje **ze staré implementace**, ne z toho, jak by to mělo vypadat. Kdyby se navrhlo podle nové, první krok by rozbil to, co funguje.

**Po tomhle kroku platí:** stará implementace rozhraní splňuje a nic dalšího se nezměnilo.

### 2. Přepni volající na abstrakci

```php
public function __construct(
    private readonly ShippingCalculator $shipping,   // ← rozhraní
) {}
```

```
CheckoutService dostal:       ShippingCalculator (rozhraní)
zná LegacyTableCalculator?    ne
zná WeightBasedCalculator?    ne
```

Tenhle krok se dělá **postupně, volající po volajícím** — každý je vlastní commit.

**Po tomhle kroku platí:** žádné místo nezná konkrétní implementaci. Od téhle chvíle jde vyměnit cokoli.

### 3. Postav novou implementaci za abstrakcí

```php
final class WeightBasedCalculator implements ShippingCalculator { /* … */ }
```

Nová verze vzniká **vedle staré**, zatímco stará dál obsluhuje provoz. Není nasazená, nikdo ji nevolá.

**Po tomhle kroku platí:** obě implementace existují, obě splňují rozhraní, provoz jede beze změny.

### 4. Přepni provoz

Přepínač je sám implementací rozhraní — volající o něm neví:

```php
final class SwitchingCalculator implements ShippingCalculator
{
    public function quoteFor(Shipment $shipment): ShippingQuote
    {
        return $this->pickNew($shipment)
            ? $this->new->quoteFor($shipment)
            : $this->old->quoteFor($shipment);
    }
}
```

Přepíná se **postupně**, ne naráz:

```
nastaveno       obslouženo novou      skutečný podíl
0 %             0 z 200               0.0 %
10 %            21 z 200              10.5 %
25 %            51 z 200              25.5 %
50 %            112 z 200             56.0 %
100 %           200 z 200             100.0 %
```

**Po tomhle kroku platí:** provoz jde po nové implementaci, stará je nedotčená a připravená převzít zpět.

### 5. Smaž starou implementaci

Až po nějaké době provozu, ne hned. A na závěr se **můžeš** zbavit i abstrakce — Fowler: *„you may also choose to delete the abstraction layer once you no longer need it for migration."*

Obvykle se nechává, protože se hodí příště.

**Po tomhle kroku platí:** hotovo.

> [!NOTE]
> **Kde se dá zastavit:** po **každém** kroku. Demo to shrnuje na konci — krok 1 a 2 jsou samy o sobě zlepšením, krok 3 nechá novou implementaci ležet nepoužitou (což nevadí) a po kroku 4 je stará verze zálohou. **Tohle je hlavní rozdíl proti přepisu ve větvi**, kde se mezistav nedá vydat.

---

## Průběh a návratová cesta

| | |
| --- | --- |
| **Jak dlouho to trvá** | Týdny až měsíce; kroky 1–2 dny, krok 3 nejdéle |
| **Co vidí uživatel** | Nic, dokud se nepřepne. Pak postupně novou verzi |
| **Jak se vrátit** | **Přepnutí podílu na nulu** — změna konfigurace, ne nasazení |
| **Jak dlouho žije mezistav** | Od kroku 3 do kroku 5; obě implementace existují vedle sebe |

Návratová cesta je to hlavní, co technika dává. **Vrátit se neznamená revert ani hotfix** — znamená to přepnout číslo. To je rozdíl mezi minutou a půl dnem, a u změny, která se dotýká peněz, je to rozdíl podstatný.

Cenou je, že mezi kroky 3 a 5 **existují dvě implementace téhož** a obě se musí udržovat. Proto se krok 5 nemá odkládat donekonečna.

---

## Jak ověřit, že to funguje

**Porovnávací režim** je nejsilnější nástroj, který technika nabízí. Než se přepne provoz, spouštějí se obě implementace a jejich výsledky se porovnávají — ale odpovídá pořád stará:

```
zásilek:               5
rozdílů:               4

    CZ 400 g / 1 290 Kč → stará PPL, 99,00 Kč, 2 dny · nová PPL, 79,00 Kč, 2 dny
    CZ 3200 g / 890 Kč → stará PPL, 99,00 Kč, 2 dny · nová PPL, 109,00 Kč, 2 dny
    SK 1500 g / 1 990 Kč → stará PPL, 149,00 Kč, 3 dny · nová PPL, 141,00 Kč, 3 dny
    DE 800 g / 1 490 Kč → stará DHL, 249,00 Kč, 4 dny · nová DHL, 199,00 Kč, 4 dny
```

Z výpisu je vidět něco, co se z testů nedozvíš: **nová verze mění ceny.** Někde nahoru, někde dolů. To není chyba implementace — je to rozhodnutí, které má udělat byznys, a technika ho vytáhla na světlo **dřív, než to zjistil zákazník**.

Když se porovnávací režim nechá běžet na skutečném provozu, dělá se z toho samostatná technika: **Parallel Run**.

Dál platí obvyklé:

- **Testy na rozhraní, ne na implementaci** — tytéž testy musí projít proti oběma verzím.
- **Deterministické rozdělení provozu** (viz níž), jinak se výsledky nedají porovnávat.

---

## Co to stojí

| Cena | Kdy se vyplatí |
| ---- | -------------- |
| **Abstrakce navíc**, kterou by tam jinak nebyla potřeba | Když je co vyměňovat; jinak je to vrstva pro nic |
| **Dvě implementace téhož** po dobu migrace | Když je návratová cesta cennější než dočasná duplicita |
| **Delší celková doba** než u přepisu naráz | Když si nemůžeš dovolit výpadek |
| **Mezistav je ošklivější** než začátek i konec | Když se počítá s tím, že se to dokončí |
| **Přepínač je kód navíc**, který se pak maže | Když se přepíná postupně nebo se porovnává |

Poslední řádek předposlední tabulky stojí za zdůraznění: **mezi krokem 3 a 5 vypadá kód hůř než na začátku.** Kdo to nečeká, techniku uprostřed opustí — a zůstane přesně v tom nejhorším bodě, se dvěma implementacemi a bez rozhodnutí.

---

## Kdy to nedělat

- ❌ **Výměna se dá udělat jedním commitem.** Abstrakce, přepínač a dvojí implementace kvůli změně na hodinu jsou režie bez užitku.
- ❌ **Tu část volá jediné místo.** Pak stačí vyměnit ji přímo.
- ❌ **Nemáš testy a nechceš je psát.** Bez nich technika nedává jistotu, jen zdání.
- ❌ **Nová verze má úplně jiné rozhraní.** Když se nedá najít společné rozhraní, které dává smysl oběma, technika nesedí — spíš potřebuješ [Strangler Fig](../StranglerFig/).
- ❌ **Stará implementace se má zachovat natrvalo.** Pak to není migrace, ale [Strategy](../../../SoftwareDesign/GoF/Behavioral/Strategy/) — a abstrakce zůstává napořád.

---

## Časté chyby

| Chyba | Proč vadí | Jak správně |
| ----- | --------- | ----------- |
| Rozhraní se navrhne podle **nové** implementace | První krok rozbije to, co funguje | Odvodit ho ze staré; upravit až později |
| Kroky 1–3 se udělají ve větvi | Vzniklo přesně to, čemu se technika vyhýbá | Každý krok samostatně do hlavní větve |
| Náhodné rozdělení provozu | Zákazník při obnovení stránky vidí jinou cenu | Deterministicky, podle otisku vstupu |
| Přepne se rovnou na 100 % | Zahodil jsi hlavní přínos techniky | Postupně, s možností vrátit |
| Krok 5 se odkládá donekonečna | Dvě implementace téhož navždy | Naplánovat smazání, ne doufat |
| Abstrakce prosákne detaily staré implementace | Nová verze se do ní nevejde | Rozhraní mluví o záměru, ne o postupu |
| Přepínač se řeší nasazením | Návrat zpět trvá půl dne místo minuty | [Feature flag](../../../GitWorkflows/Glossary.md#feature-flag) |
| Nová verze se testuje jen jednotkově | Rozdíly proti staré se ukážou až v provozu | Porovnávací režim |
| Technika se použije na věc, kterou lze vyměnit hned | Vrstva a přepínač kvůli hodinové změně | Vyměnit přímo |

---

## Demo

```bash
php Refactoring/System/BranchByAbstraction/demo/run.php
```

Výměna výpočtu dopravy — ze staré tabulky sazeb na sazbu podle hmotnosti. Demo ukáže, že volající **nezná ani jednu implementaci** (měřeno na kódu bez komentářů), postaví obě verze vedle sebe a spustí **porovnávací režim**, ze kterého je vidět, že nová verze mění ceny. Pak přepíná provoz po částech na dvou stech zásilkách (0 → 10 → 25 → 50 → 100 %), ověří, že **táž zásilka dostane vždy touž odpověď**, a nakonec projde všech pět kroků s odpovědí, jestli se v nich dá zůstat.

---

## Související

| Dokument | Vztah |
| -------- | ----- |
| [Trunk-Based Development](../../../GitWorkflows/TrunkBasedDevelopment/) | **Domovský kontext techniky.** Hammant ji popsal právě jako způsob, jak dělat velké změny bez dlouhých větví. |
| [Feature flag](../../../GitWorkflows/Glossary.md#feature-flag) | Čím se přepíná. Bez něj je návrat zpět nasazení, ne změna konfigurace. |
| [Strategy](../../../SoftwareDesign/GoF/Behavioral/Strategy/) (GoF) | Výsledná struktura je táž — rozhraní a zaměnitelné implementace. Rozdíl je v záměru: Strategy je cíl, tady je to **dočasný stav**. |
| [Ports & Adapters](../../../SoftwareDesign/Architecture/PortsAndAdapters/) | Kde už abstrakce existuje, je krok 1 hotový — a technika začíná rovnou krokem 3. |
| [Cohesive Mechanism](../../../SoftwareDesign/DDD/CohesiveMechanism/) (DDD) | Typický kandidát na výměnu: výpočet za rozhraním, který jde nahradit lepším. |
| [Strangler Fig](../StranglerFig/) | **Nejbližší příbuzný.** Pro případy, kdy se společné rozhraní najít nedá a nahrazuje se celý systém zvenčí — šev je na hranici, ne uvnitř kódu. |
| **Parallel Run** | Porovnávací režim dotažený do samostatné techniky. *(zatím nezpracováno)* |

---

## Původ

|             |                    |
| ----------- | ------------------ |
| **Autor**   | pojmenoval Stacy Curl, popsal Paul Hammant |
| **Rok**     | 2007               |
| **Zdroj**   | blog Paula Hammanta; později [Martin Fowler](https://martinfowler.com/bliki/BranchByAbstraction.html) |
| **Náročnost** | ●●●○○            |

Techniku **pojmenoval Stacy Curl v roce 2007**, zdokumentoval a rozšířil ji **Paul Hammant** — a připsal autorství jména jemu. Fowler o ní později napsal článek a poznamenal, že **postup existoval dávno předtím, než dostal jméno**; týmy ho používaly, jen mu neříkaly nijak.

Kontext vzniku vysvětluje ten matoucí název. Hammant ji popsal v době, kdy se prosazoval [Trunk-Based Development](../../../GitWorkflows/TrunkBasedDevelopment/), jako odpověď na námitku *„ale velké změny se bez dlouhé větve udělat nedají"*. Odtud i slovo *branch*: **větví se v kódu, ne ve verzovacím systému.**

Náročnost je trojka a není v mechanice — ta má pět kroků a každý je jednoduchý. Cena je jinde:

- **Rozhodnout, kudy vede rozhraní**, je nejtěžší část a udělá se jen jednou.
- **Mezistav trvá dlouho** a vypadá hůř než začátek. Vyžaduje to trpělivost, kterou projekty pod tlakem nemají.
- **Krok 5 se odkládá.** Dokončit migraci nikoho nezajímá, protože „to už přece funguje" — a dvě implementace zůstanou napořád.

Poslední bod je nejčastější způsob, jak techniku pokazit: **provede se dobře až po krok 4 a tam se skončí.** Systém pak nese obojí a nikdo neví, která verze je ta pravá.

---

## Zdroje

- Paul Hammant: [*Introducing Branch By Abstraction*](https://paulhammant.com/blog/branch_by_abstraction.html), 2007 — první popis
- [Branch by Abstraction](https://trunkbaseddevelopment.com/branch-by-abstraction/) — trunkbaseddevelopment.com
- Martin Fowler: [*Branch By Abstraction*](https://martinfowler.com/bliki/BranchByAbstraction.html)

---

<details>
<summary>Metadata techniky</summary>

```yaml
name: Branch by Abstraction
level: system
author: Stacy Curl (název), Paul Hammant (popis)
year: 2007
duration: týdny až měsíce
reversible: ano — přepnutím podílu na nulu
requires_tests: ano
difficulty: 3
tags: [migrace, abstrakce, feature flag, postupná změna, návratová cesta]
leads_to: []
related: [TrunkBasedDevelopment, Strategy, PortsAndAdapters, StranglerFig, ParallelRun]
status: done
```

</details>
