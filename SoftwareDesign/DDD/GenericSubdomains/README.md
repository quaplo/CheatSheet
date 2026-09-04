# Generic Subdomains (Obecné podoblasti)

> [← zpět na DDD](../)

> **V jedné větě:** Části, které musí fungovat, ale nikoho nezajímají — vytěsni je do vlastních modulů, nenech v nich stopu své specializace a nestav je nejlepšími lidmi.

---

## Problém

Fakturace. Přihlašování. Odesílání e-mailů. Číselník zemí. Nic z toho firmu neživí, všechno musí být — a všechno se to postupně proplétá s tím, čím se firma opravdu liší.

Evans:

> „Some parts of the model add complexity without capturing or communicating specialized knowledge. **Anything extraneous makes the core domain harder to discern and understand.** The model clogs up with general principles everyone knows or details that belong to specialties which are not your primary focus but play a supporting role. Yet, however generic, **these other elements are essential** to the functioning of the system and the full expression of the model."

Poslední věta je důležitá a odlišuje tenhle vzor od pohrdání. **Obecné části nejsou méněcenné, jsou nezbytné.** Jen nejsou to, čím se lišíš — a proto s nimi nemáš zacházet stejně jako s [jádrem](../CoreDomain/).

**Poznáš to podle:**

- do integrace s platební bránou šly **měsíce**, do vlastního algoritmu týdny
- máte **vlastní systém na odesílání e-mailů**, protože „ten hotový nám úplně nesedl"
- v modulu na fakturaci je **vaše doménová terminologie**, takže se nedá vytáhnout ani nahradit
- nejzkušenější člověk v týmu ladí **správu uživatelských rolí**
- na otázku „proč to píšeme sami" padne odpověď **„protože to potřebujeme trochu jinak"**

Demo měří, kam to vede:

```
Core Domain             █████░░░░░░░░░░░░░░░░░░░  21.0 %    65 dní
Supporting Subdomain    █████████░░░░░░░░░░░░░░░  37.1 %   115 dní
Generic Subdomain       ██████████░░░░░░░░░░░░░░  41.9 %   130 dní
```

**Do obecných částí teče víc času než do jádra a supporting částí dohromady.**

---

## Řešení

> „Therefore: **Identify cohesive subdomains that are not the motivation for your project. Factor out generic models of these subdomains and place them in separate modules. Leave no trace of your specialties in them.** Once they have been separated, **give their continuing development lower priority than the core domain, and avoid assigning your core developers to the tasks** (because they will gain little domain knowledge from them). **Also consider off-the-shelf solutions or published models** for these generic subdomains."

Čtyři pokyny, a tři z nich jsou o lidech, ne o kódu:

| Pokyn | Co znamená |
| ----- | ---------- |
| **Factor out into separate modules** | Vlastní modul s vlastní hranicí |
| **Leave no trace of your specialties** | Uvnitř nesmí být vaše terminologie |
| **Lower priority, not your core developers** | Nejlepší lidé sem nepatří — a nic se tu nenaučí |
| **Consider off-the-shelf** | Nejdřív se podívej, jestli to nejde koupit |

### „Leave no trace of your specialties"

Nejkonkrétnější a nejsnáz ověřitelný pokyn z celé definice. Test je jednoduchý: **dala by se ta část použít v úplně jiné firmě?**

```php
// ŠPATNĚ — obecná část zamořená specializací
final class InvoiceGenerator
{
    public function generate(Order $order, RecommendationScore $score): Invoice
    {
        // sleva podle skóre doporučení — což je naše jádro
    }
}

// SPRÁVNĚ — obecná část mluví obecně
final class InvoiceGenerator
{
    /** @param list<InvoiceLine> $lines */
    public function generate(string $number, array $lines, TaxRate $rate): Invoice
    {
        // o doporučování neví nic; slevu dostane jako hotovou položku
    }
}
```

První varianta vypadá pohodlněji — méně převodu, méně tříd. Za to ale **přestane jít vyměnit za hotové řešení** a nejde vytáhnout do balíčku. A hlavně: jádro se tím rozlézá do míst, kde ho nikdo nečeká.

### Čtyři způsoby, jak obecnou podoblast pořídit

Evans zmiňuje hotová řešení a publikované modely; v praxi jsou možnosti čtyři a liší se cenou i kontrolou:

| Způsob | Kdy | Cena |
| ------ | --- | ---- |
| **Hotová služba** (Stripe, mailová brána) | Standardní problém, kde nepotřebuješ kontrolu | Nejnižší úsilí, závislost na dodavateli |
| **Knihovna** | Standardní problém, ale chceš to mít u sebe | Nízké úsilí, údržba aktualizací |
| **Publikovaný model** | Existuje popsané řešení, implementace je na tobě | Střední; získáš ověřený model |
| **Napsat sám, jednoduše** | Nic z výše uvedeného nesedí | Nejvyšší — a proto až poslední |

**Pořadí není náhodné.** Poslední možnost je legitimní, ale má být volbou z nouze, ne výchozím stavem — a v praxi bývá výchozím stavem skoro vždycky.

Demo počítá, co by uvolnilo posunutí poloviny obecných částí na hotová řešení:

```
generické podoblasti    130 dní   42 %
    Platby                  45 dní
    Fakturace a DPH         35 dní
    Odesílání e-mailů       20 dní
    Správa uživatelů        30 dní

kdyby se polovina z toho koupila:   65 dní zpět do jádra
jádro by pak mělo:                  42 % místo 21 %
```

### „Avoid assigning your core developers"

Pokyn, který zní tvrdě, ale Evans u něj rovnou uvádí důvod: *„because they will gain little domain knowledge from them."*

Nejde o hierarchii. Jde o to, že **čas strávený na obecné části nepřidá znalost domény** — a znalost domény je to, co dělá z vývojáře člověka, který jádru rozumí. Když nejzkušenější člověk stráví čtvrtletí na správě uživatelských rolí, tým o to čtvrtletí zestárnul, ale nic se nenaučil.

Demo připomíná, proč se to děje samo od sebe — je to Evansova past z definice [Core Domain](../CoreDomain/#problém): nejlepší lidé přirozeně tíhnou k technicky zajímavým, dobře ohraničeným úlohám, které jdou pochopit bez ptaní se doménového experta.

---

## Účastníci

| Role | Odpovědnost |
| ---- | ----------- |
| **Obecná podoblast** | Vlastní modul, obecný jazyk, žádná stopa vaší specializace |
| **Jádro** | Používá ji, ale nedovolí, aby prosákla dovnitř |
| **[Překladová vrstva](../AnticorruptionLayer/)** | Hranice, pokud jde o hotové řešení s cizím modelem |
| **Tým** | Ví, že sem nepatří ambice ani nejlepší lidé |

---

## Implementace v PHP

### Hotové řešení patří za hranici

Knihovna nebo služba přinese vlastní model. Když se pustí do systému napřímo, naimportuješ si ho do svého:

```php
// Špatně: doména mluví jazykem Stripe
public function pay(\Stripe\PaymentIntent $intent): void

// Správně: doména mluví svým jazykem, adaptér překládá
public function pay(Payment $payment): PaymentResult
```

Je to [Anticorruption Layer](../AnticorruptionLayer/) a u obecných podoblastí se vyplatí skoro vždy — právě proto, že **hotové řešení chceš mít možnost vyměnit**.

### Vlastní modul, i když ho píšeš sám

Když se obecná část píše ručně, má stejně dostat vlastní hranici:

```
src/
    Recommendation/     ← jádro
    Pricing/            ← jádro
    Invoicing/          ← obecné; uvnitř žádné „doporučení" ani „skóre"
    Notification/       ← obecné
```

Hranice se hlídá stejně jako u [odděleného jádra](../SegregatedCore/#hranice-se-hlídá-nástrojem-ne-dohodou) — statickou analýzou. Test „dala by se použít v jiné firmě" se totiž nedá spustit; test „neobsahuje slovo `Recommendation`" ano.

### Kdy je to ještě supporting a kdy už generic

Rozdíl, který se často stírá:

| | **Supporting Subdomain** | **Generic Subdomain** |
| --- | --- | --- |
| Odlišuje nás | ne | ne |
| Je to specifické pro nás | **ano** | ne |
| Dělala by to jiná firma stejně | ne | **ano** |
| Dá se koupit | obvykle ne | **ano** |
| Co s tím | stavět sami, ale jednoduše | koupit, stáhnout, zadat ven |

**Katalog produktů** je supporting: každý e-shop ho má, ale tvůj vypadá jinak než ostatní a hotové řešení ti nesedne. **Fakturace** je generic: DPH se počítá všude stejně a existuje na to deset knihoven.

---

## Kdy použít

- ✅ Část systému **není důvod, proč produkt existuje**, ale musí fungovat.
- ✅ **Existuje na to hotové řešení** a vy ho přesto píšete.
- ✅ Obecná část se **proplétá s jádrem** a nejde vytáhnout.
- ✅ Chceš uvolnit **kapacitu pro jádro** a nevíš odkud.
- ✅ Nejzkušenější lidé dělají na věcech, ze kterých se **nic nenaučí**.

## Kdy nepoužít

- ❌ **Ta část vás ve skutečnosti odlišuje.** Pak je to [jádro](../CoreDomain/) a koupit ho znamená přestat se lišit.
- ❌ **Je to specifické pro vás, byť ne klíčové.** To je supporting — stavět jednoduše, ale stavět.
- ❌ **Vytěsnění by teď stálo víc, než přinese.** Vzor je průběžná práce, ne refaktoring všeho naráz.
- ❌ **Hotové řešení by přineslo závislost, kterou neunesete** (regulace, dostupnost, cena při růstu).

---

## Časté chyby

| Chyba | Proč vadí | Jak správně |
| ----- | --------- | ----------- |
| „Napíšeme si to sami, potřebujeme to trochu jinak" | Nejdražší varianta zvolená z pohodlí | Nejdřív se podívat po hotovém |
| V obecné části je vaše terminologie | Nejde vyměnit ani vytáhnout; jádro se rozlézá | **Leave no trace of your specialties** |
| Hotové řešení se pustí do domény napřímo | Naimportuješ si cizí model | [Anticorruption Layer](../AnticorruptionLayer/) |
| Nejlepší lidé na obecných částech | Ztracený čas bez přírůstku doménové znalosti | Jádro |
| Obecná část se leští jako jádro | Investice, která se nevrátí | Jednoduše a hotovo |
| Záměna supporting a generic | Koupí se to, co je specifické, nebo píše to, co jde koupit | [Rozlišení výš](#kdy-je-to-ještě-supporting-a-kdy-už-generic) |
| Vytěsnění bez pojmenování jádra | Není podle čeho rozhodovat | Nejdřív [Core Domain](../CoreDomain/) |
| Obecná část bez vlastní hranice | Za rok je zase propletená | Vlastní modul, hlídaný staticky |

---

## V praxi

- **Stripe, GoPay, platební brány** — obecná podoblast v učebnicové podobě; nikdo si ji nepíše sám.
- **Symfony Security, přihlašování přes OAuth** — autentizace je všude stejná.
- **Knihovny na fakturaci a DPH** — pravidla jsou daná zákonem, ne vaší doménou.
- **Doctrine, mailer, logger** — technická infrastruktura, kterou nikdo nepovažuje za doménu, ale platí pro ni totéž.
- **Číselníky (země, měny, jazyky)** — Evansovy „general principles everyone knows".

---

## Související patterny

| Pattern | Vztah |
| ------- | ----- |
| [Core Domain](../CoreDomain/) | **Druhá strana téže mince.** Bez pojmenovaného jádra není podle čeho rozhodovat, co je obecné. |
| [Cohesive Mechanism](../CohesiveMechanism/) | Také vytěsňuje — ale výpočty, ne celé podoblasti. |
| [Segregated Core](../SegregatedCore/) | Poslední krok destilace; Evans ho doporučuje **až po** vytěsnění obecných částí. |
| [Anticorruption Layer](../AnticorruptionLayer/) | Čím se hotové řešení pouští do systému, aby jeho model neprosákl. |
| [Bounded Context](../BoundedContext/) | Obecná podoblast bývá vlastním kontextem s vlastním jazykem. |
| [Adapter](../../GoF/Structural/Adapter/) (GoF) | Technická podoba překladu na hranici. |
| [Active Record](../../PoEAA/ActiveRecord/) | Rozumná volba tam, kde se obecná část píše ručně a model je tabulka. |

---

## Vztah k principům

| Princip | Jak souvisí |
| ------- | ----------- |
| [YAGNI](../../Principles/Simplicity.md#yagni--you-arent-gonna-need-it) | Do obecné části se nestaví nic navíc. Ambice sem nepatří. |
| [KISS](../../Principles/Simplicity.md#kiss--keep-it-simple) | Nejjednodušší řešení je tu to správné, ne nouzové. |
| [Nízká provázanost](../../Principles/CohesionAndCoupling.md#stupnice-provázanosti) | „Leave no trace of your specialties" je požadavek na nulovou vazbu na jádro. |
| [DIP](../../Principles/SOLID.md#dependency-inversion-principle-dip) | Hotové řešení se pouští dovnitř přes rozhraní, které si určuje doména. |

---

## Demo

```bash
php SoftwareDesign/DDD/CoreDomain/demo/run.php
```

Demo je společné s [Core Domain](../CoreDomain/), protože oba vzory odpovídají na tutéž otázku z opačných stran. Klasifikuje podoblasti e-shopu, ukáže u každé doporučení a **spočítá, že do obecných částí teče 42 % úsilí** — víc než do jádra. Pak vyčíslí, co by uvolnilo pořízení poloviny z nich hotově: 65 dní zpátky do jádra a jeho podíl z 21 % na 42 %.

---

## Původ

|               |                                                   |
| ------------- | ------------------------------------------------- |
| **Zdroj**     | *Domain-Driven Design: Tackling Complexity in the Heart of Software* |
| **Autor**     | Eric Evans                                        |
| **Rok**       | 2003                                              |
| **Kategorie** | Strategický návrh — destilace (kapitola 15)       |
| **Obtížnost** | ●●○○○                                             |

V kapitole **Distillation** následuje hned po [Core Domain](../CoreDomain/) a je jeho přímým důsledkem: jakmile víš, co je jádro, víš i to, co jím není — a tenhle vzor říká, co s tím.

Evans ho staví jako **první a nejlevnější destilační krok**. Vytěsnit obecné části jde obvykle bez zásahu do jádra, kdežto [Segregated Core](../SegregatedCore/) je řez celým modelem. Sám to shrnuje větou, která to pořadí vysvětluje:

> „**Factoring out generic subdomains reduces clutter**, and cohesive mechanisms serve to encapsulate complex operations. This leaves behind a more focused model."

Obtížnost je dvojka ze stejného důvodu jako u jádra — technicky není co vymýšlet. Těžké jsou dvě věci a obě jsou lidské:

- **Přiznat, že „potřebujeme to trochu jinak" obvykle není pravda.** Většina požadavků na vlastní fakturaci nebo vlastní přihlašování vznikla z pohodlí, ne z nutnosti.
- **Nedat na to nejlepší lidi**, přestože to bývá technicky zajímavější než jádro.

Za zmínku stojí, že Evansovo doporučení „consider off-the-shelf solutions" bylo v roce 2003 podstatně odvážnější než dnes. Tehdy znamenalo nakoupit software; dnes je většina obecných podoblastí dostupná jako služba na pár řádků kódu — a tím se argument pro psaní vlastního řešení ještě zeslabil.

---

## Zdroje

- Eric Evans: *Domain-Driven Design*, Addison-Wesley, 2003 — kapitola 15, *Distillation*
- Eric Evans: [*Domain-Driven Design Reference*](https://www.domainlanguage.com/wp-content/uploads/2016/05/DDD_Reference_2015-03.pdf) (PDF, 2015) — souhrn definic, pod licencí CC BY 4.0

---

<details>
<summary>Metadata patternu</summary>

```yaml
name: Generic Subdomains
name_cs: Obecné podoblasti
category: Strategický návrh — destilace
source: DDD – Domain-Driven Design
authors: Eric Evans
year: 2003
difficulty: 2
tags: [destilace, build vs buy, moduly, hranice, priority]
principles: [YAGNI, KISS, CohesionAndCoupling, DIP]
related: [CoreDomain, CohesiveMechanism, SegregatedCore, AnticorruptionLayer, BoundedContext, Adapter, ActiveRecord]
status: done
```

</details>
