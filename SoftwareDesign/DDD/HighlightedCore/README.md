# Highlighted Core (Zvýrazněné jádro)

> [← zpět na DDD](../)

> **V jedné větě:** Označ prvky [jádra](../CoreDomain/) přímo v modelu, aby na jeden pohled bylo poznat, co do něj patří — bez toho zůstane [vize domény](../DomainVisionStatement/) jen textem, který si každý vyloží jinak.

---

## Problém

[Vize domény](../DomainVisionStatement/) je napsaná, tým ji četl. A přesto — když se zeptáš pěti lidí, které konkrétní třídy tvoří jádro, dostaneš pět různých seznamů.

Evans:

> „A domain vision statement identifies the core domain in broad terms, but it **leaves the identification of the specific core model elements up to the vagaries of individual interpretation.** Unless there is an exceptionally high level of communication on the team, the vision statement alone will have little impact. Even though team members may know broadly what constitutes the core domain, **different people won't pick out quite the same elements, and even the same person won't be consistent from one day to the next.**"

Ta poslední půlvěta je nepříjemně přesná: **nekonzistentní není jen tým, ale i jednotlivec sám se sebou** ob den.

A pokračuje tím, co to stojí:

> „**The mental labor of constantly filtering the model to identify the key parts absorbs concentration better spent on design thinking**, and it requires comprehensive knowledge of the model."

**Poznáš to podle:**

- vize domény existuje, ale nikdo podle ní **nerozhoduje o konkrétním kódu**
- při review nikdo neví, jestli je tahle změna **velká věc, nebo detail**
- nový člověk musí přečíst půlku modelu, než pozná, **co je důležité**
- „to je přece jádro" a „to zas tak důležité není" o **téže třídě**
- jádro se pozná jen tak, že se **zeptáš toho zkušeného** — a ten nemusí být po ruce

---

## Řešení

Evans nabízí **dvě formy** a dají se použít obě najednou.

> „Therefore (as one form of highlighted core): **Write a very brief document (three to seven sparse pages)** that describes the core domain and the primary interactions among core elements."
>
> „and/or (as another form of highlighted core): **Flag the elements of the core domain within the primary repository of the model**, without particularly trying to elucidate its role. **Make it effortless for a developer to know what is in or out of the core.**"

| Forma | Co to je | Kdy |
| ----- | -------- | --- |
| **Destilační dokument** | 3–7 řídkých stran o jádru a hlavních vazbách mezi jeho prvky | Když je potřeba pochopit **souvislosti** |
| **Značky v modelu** | Prvky jádra označené přímo v kódu | Když je potřeba **poznat na první pohled** |

Klíčová věta je *„make it effortless"*. Nejde o dokumentaci, jde o to, **aby to nestálo žádné úsilí** — protože co stojí úsilí, to se dělat přestane.

### Značky v modelu — PHP podoba

V PHP 8 se druhá forma dělá atributem:

```php
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class CoreDomain
{
    public function __construct(
        /** Krátce proč — ne co třída dělá, ale proč je klíčová. */
        public string $why,
    ) {
    }
}
```

```php
#[CoreDomain('Důvod, proč k nám zákazník chodí — najde zboží, které by sám nenašel.')]
final class RecommendationEngine { /* … */ }
```

Atribut **záměrně nic nedělá**. Evans na to má poznámku: *„without particularly trying to elucidate its role"* — značka není dokumentace, je to ukazatel.

Seznam jádra se pak nečte z dokumentu, ale **z modelu**:

```
Recommendation\RecommendationEngine
    Důvod, proč k nám zákazník chodí — najde zboží, které by sám nenašel.
Recommendation\SimilarityScore
    Míra podobnosti chování zákazníků; jádro doporučovacího modelu.
Pricing\DynamicPrice
    Vyvažuje marži, konkurenceschopnost a důvěru zákazníka.
Pricing\PriceFloor
    Spodní hranice, pod kterou cena nesmí klesnout — chrání důvěru i marži.
```

**Takový seznam nemůže zastarat**, protože se nepíše — vzniká z toho, co je v kódu.

### Hranice nevede podle složek

Nejužitečnější vlastnost značek a důvod, proč nestačí rozdělení do modulů:

```
Catalog             0 z 2 v jádru
Invoicing           0 z 2 v jádru
Pricing             2 z 2 v jádru
Recommendation      2 z 3 v jádru
    ● Recommendation\RecommendationEngine
    ● Recommendation\SimilarityScore
    ○ Recommendation\ViewHistory
```

Modul `Recommendation` má tři třídy, ale **jen dvě jsou jádro**. `ViewHistory` je obyčejný sběr dat — vypadal by stejně v jakémkoli e-shopu. **Značka to rozliší, složka ne.**

Tohle je zároveň rozdíl proti [Segregated Core](../SegregatedCore/): ten hranici staví strukturálně a je to zásah do modelu; tenhle vzor ji jen **zviditelní tam, kde model zůstává, jak je**.

### Kontrola velikosti zdarma

Když je jádro označené, dá se změřit:

```
tříd v modelu             14
z toho jádro              4
podíl jádra               29 %

Jádro je malé — přesně jak Evans žádá: „make the core small".
```

Demo tuhle kontrolu obsahuje a při překročení prahu varuje. **Je to nejlevnější způsob, jak uhlídat nejčastější chybu** — že se za jádro postupně označí půlka modelu a značka přestane nést informaci.

---

## Nejpraktičtější důsledek: co s tím při změně

Tahle část definice se cituje málo a přitom je z celého vzoru nejpoužitelnější:

> „If the distillation document outlines the essentials of the core domain, then it serves as **a practical indicator of the significance of a model change.** When a model or code change affects the distillation document, **it requires consultation with other team members.** When the change is made, it requires immediate notification of all team members […]. **Changes outside the core or to details not included in the distillation document can be integrated without consultation or notification** […]. Then the developers have **the full autonomy that most Agile processes suggest.**"

Ze značek se tím stane **pravidlo o tom, kdy se má o změně mluvit**:

```
Změna v                           jádro?    jak s ní naložit
změna vzorce podobnosti           ano       projednat s týmem, dát vědět
přidání indexu do historie        ne        integrovat bez konzultace
nová sazba DPH                    ne        integrovat bez konzultace
změna spodní hranice ceny         ano       projednat s týmem, dát vědět
```

Pozor na směr, kterým to Evans myslí. **Není to o zavádění schvalování** — je to naopak: mimo jádro dostávají vývojáři *„full autonomy"*. Vzor tedy **zmenšuje** počet věcí, o kterých se musí diskutovat, tím, že je pojmenuje.

V praxi se to dá napojit rovnou na [code review](../../../Processes/CodeReview/): pull request, který sahá na označené třídy, si zaslouží pozornější čtení a víc než jednoho recenzenta. To se dá i zautomatizovat — `CODEOWNERS` nebo kontrola v CI.

---

## Účastníci

| Role | Odpovědnost |
| ---- | ----------- |
| **Značka** | Označuje prvek jádra; nic nedělá |
| **Prvky jádra** | Označené třídy — ty, na kterých stojí hodnota produktu |
| **Destilační dokument** | 3–7 stran o jádru a vazbách mezi jeho prvky |
| **Tým** | Podle značek pozná, kdy je změna velká věc |

---

## Implementace v PHP

### Atribut, nebo komentář?

| | **Atribut** `#[CoreDomain]` | **Komentář / PHPDoc** |
| --- | --- | --- |
| Dá se vypsat programově | **ano** | jen textovým hledáním |
| Přežije refaktoring | **ano** | ano |
| Vidí to IDE | ano | ano |
| Dá se hlídat v CI | **ano** | obtížně |
| Náklad | jedna třída | nula |

**Výchozí volba je atribut**, protože umožní to podstatné — seznam, který se nepíše ručně, a kontrolu v CI. Komentář stačí u malého modelu, kde je seznam přehledný i tak.

### Co do značky psát

```php
// Špatně: opakuje název třídy
#[CoreDomain('Doporučovací engine.')]

// Správně: říká PROČ je to jádro
#[CoreDomain('Důvod, proč k nám zákazník chodí — najde zboží, které by sám nenašel.')]
```

Značka bez důvodu se za rok stane šumem, protože nikdo neví, na základě čeho tam je — a nikdo si ji netroufne odebrat.

### Kontrola v CI

Když je jádro označené, dá se hlídat, že nepřerůstá:

```php
// tests/CoreSizeTest.php
public function testCoreStaysSmall(): void
{
    $ratio = count($this->coreClasses()) / count($this->allDomainClasses());

    self::assertLessThan(
        0.35,
        $ratio,
        'Jádro přerostlo třetinu modelu — buď se rozlézá, nebo je špatně označené.',
    );
}
```

Není to hlídání kvality, ale **hlídání smyslu značky**. Jakmile je jádrem půlka modelu, značka nerozlišuje nic.

---

## Kdy použít

- ✅ **[Vize domény](../DomainVisionStatement/) existuje**, ale nepromítá se do rozhodování o kódu.
- ✅ Tým se neshodne, **které konkrétní třídy jsou to důležité**.
- ✅ Chceš rozlišit, které změny **vyžadují pozornost** a které ne.
- ✅ [Segregated Core](../SegregatedCore/) by byl správný, ale **teď na něj není čas**.
- ✅ Nový člověk potřebuje vědět, **kde začít číst**.

## Kdy nepoužít

- ❌ **Model je malý a přehledný.** Deset tříd se značkovat nemusí.
- ❌ **[Jádro není určené](../CoreDomain/).** Značkovat bez rozhodnutí, co je jádro, znamená označit to, co se komu zdá.
- ❌ **Značka by byla jediné, co se udělá.** Sama o sobě nic nezlepší — musí se podle ní jednat.
- ❌ **Struktura už jádro ukazuje** ([Segregated Core](../SegregatedCore/)). Pak je značka duplicitní informace, která se může rozejít.

---

## Časté chyby

| Chyba | Proč vadí | Jak správně |
| ----- | --------- | ----------- |
| Jádrem je postupně půlka modelu | Značka přestane rozlišovat | Kontrola podílu v CI |
| Značka bez důvodu | Za rok nikdo neví, proč tam je, a nikdo ji neodebere | Napsat **proč**, ne co třída dělá |
| Označí se celý modul | Hranice nevede podle složek | Značkovat třídy, ne balíčky |
| Značky nikdo nepoužívá při rozhodování | Je to jen dekorace | Napojit na [review](../../../Processes/CodeReview/) a na pravidlo o změnách |
| Destilační dokument na dvacet stran | Nikdo ho nepřečte a nikdo neaktualizuje | Evans: **tři až sedm řídkých stran** |
| Značkuje se místo [oddělení jádra](../SegregatedCore/) natrvalo | Značka je náhrada, ne cíl | Značka je krok k struktuře, ne místo ní |
| Značka se nikdy nereviduje | Jádro se v čase mění, značky ne | Revidovat, když se mění [vize](../DomainVisionStatement/) |

---

## V praxi

- **PHP atributy** — od PHP 8 nejpřirozenější podoba druhé formy vzoru.
- **`CODEOWNERS`** — vynutí, že změny v jádru uvidí konkrétní lidé; Evansovo „requires consultation" v podobě, kterou zvládne nástroj.
- **ArchUnit / Deptrac** — kontrola, že označené třídy nezávisí na tom, na čem nemají.
- **README v modulu** — nejjednodušší podoba destilačního dokumentu; často stačí odstavec.
- **Štítky v issue trackeru** — táž myšlenka na úrovni práce, ne kódu.

---

## Související patterny

| Pattern | Vztah |
| ------- | ----- |
| [Domain Vision Statement](../DomainVisionStatement/) | **Předchůdce.** Vize popisuje jádro slovy; tenhle vzor dopisuje, které konkrétní prvky to jsou. |
| [Core Domain](../CoreDomain/) | Rozhodnutí, které se tímhle vzorem zviditelňuje. Bez něj není co značkovat. |
| [Segregated Core](../SegregatedCore/) | **Silnější, ale dražší varianta téhož.** Ten jádro odděluje strukturálně; tenhle ho jen zviditelní tam, kde model zůstává, jak je. |
| [Generic Subdomains](../GenericSubdomains/) | Neoznačené části jsou kandidáti na vytěsnění. |
| [Code review](../../../Processes/CodeReview/) | Kam se pravidlo o významnosti změny promítne v praxi. |
| [Bounded Context](../BoundedContext/) | Značky fungují i tam, kde se jádro táhne přes víc kontextů a struktura ho neukáže. |

---

## Vztah k principům

| Princip | Jak souvisí |
| ------- | ----------- |
| [Zviditelni implicitní](../../Principles/ObjectDesign.md#zviditelni-implicitní) | Vzor v čisté podobě: „všichni víme, co je důležité" se stane strojově čitelným faktem. |
| [KISS](../../Principles/Simplicity.md#kiss--keep-it-simple) | Jeden atribut a jeden řádek. Evans: *make it effortless*. |
| [Vysoká soudržnost](../../Principles/CohesionAndCoupling.md#stupnice-soudržnosti) | Značky ukážou, jestli jádro drží pohromadě, nebo je rozeseté po modelu. |

---

## Demo

```bash
php SoftwareDesign/DDD/HighlightedCore/demo/run.php
```

Model e-shopu se čtrnácti třídami, ze kterých jsou čtyři označené atributem `#[CoreDomain]`. Demo **vypíše jádro přečtené z modelu** (ne z ručně psaného seznamu), spočítá jeho podíl a zkontroluje, že nepřerostlo — 29 %, tedy v pořádku. Pak ukáže, že **hranice nevede podle složek**: modul `Recommendation` má tři třídy, ale jen dvě jsou jádro. Čtvrtá část je Evansovo pravidlo o významnosti změny v akci — u čtyř konkrétních změn rozhodne, které se mají projednat a které jdou integrovat bez konzultace.

---

## Původ

|               |                                                   |
| ------------- | ------------------------------------------------- |
| **Zdroj**     | *Domain-Driven Design: Tackling Complexity in the Heart of Software* |
| **Autor**     | Eric Evans                                        |
| **Rok**       | 2003                                              |
| **Kategorie** | Strategický návrh — destilace (kapitola 15)       |
| **Obtížnost** | ●●○○○                                             |

Vzor navazuje na [Domain Vision Statement](../DomainVisionStatement/) a Evans ho zavádí právě proto, že samotný text nestačí — každý si pod ním představí jiné třídy.

Nejzajímavější je ale zdůvodnění, proč vůbec existuje vedle [Segregated Core](../SegregatedCore/), který dělá totéž důkladněji:

> „**Significant structural changes to the code are the ideal way of identifying the core domain, but they are not always practical in the short term.** In fact, **such major code changes are difficult to undertake without the very view the team is lacking.**"

Druhá věta popisuje slepou uličku, do které se týmy dostávají pravidelně: **strukturální oddělení jádra vyžaduje vědět, co je jádro — a to je přesně ta informace, která chybí.** Zvýraznění je cesta ven. Je levné, dá se udělat dnes, a teprve na jeho základě jde rozhodnout, kudy povede skutečná hranice.

Obtížnost je dvojka a je celá v udržení, ne v zavedení. Označit třídy je hodina práce; **udržet značky pravdivé rok znamená revidovat je při každé změně [vize](../DomainVisionStatement/)** a bránit se tomu, aby se jádrem postupně stalo všechno. Bez kontroly podílu se to stane skoro jistě — proto ji demo obsahuje.

---

## Zdroje

- Eric Evans: *Domain-Driven Design*, Addison-Wesley, 2003 — kapitola 15, *Distillation*
- Eric Evans: [*Domain-Driven Design Reference*](https://www.domainlanguage.com/wp-content/uploads/2016/05/DDD_Reference_2015-03.pdf) (PDF, 2015) — souhrn definic, pod licencí CC BY 4.0
- [PHP: Attributes](https://www.php.net/manual/en/language.attributes.php)

---

<details>
<summary>Metadata patternu</summary>

```yaml
name: Highlighted Core
name_cs: Zvýrazněné jádro
category: Strategický návrh — destilace
source: DDD – Domain-Driven Design
authors: Eric Evans
year: 2003
difficulty: 2
tags: [destilace, jádro, atributy, viditelnost, významnost změny]
principles: [MakeImplicitExplicit, KISS, CohesionAndCoupling]
related: [DomainVisionStatement, CoreDomain, SegregatedCore, GenericSubdomains, BoundedContext]
status: done
```

</details>
