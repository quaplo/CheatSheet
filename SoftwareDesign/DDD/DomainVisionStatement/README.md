# Domain Vision Statement (Prohlášení o vizi domény)

> [← zpět na DDD](../)

> **V jedné větě:** Jedna stránka textu o tom, co je [jádro](../CoreDomain/) a jakou hodnotu přináší — napsaná brzy a revidovaná, jak se pochopení mění.

> [!NOTE]
> Nejlevnější vzor z celého katalogu. **Nepíše se v něm kód**, píše se stránka textu — a přesto řeší problém, kvůli kterému se míjejí celé projekty: že každý má v hlavě jiné jádro.

---

## Problém

Na začátku projektu model ještě neexistuje, ale rozhodnutí se dělají už teď. Později model existuje, ale je tak velký, že se z něj nedá vyčíst, o co vlastně jde.

Evans:

> „At the beginning of a project, the model usually doesn't even exist, yet **the need to focus its development is already there.** In later stages of development, there is a need for an explanation of the value of the system **that does not require an in-depth study of the model.** Also, the critical aspects of the domain model may span multiple bounded contexts, but by definition these distinct models can't be structured to show their common focus."

Poslední věta míří na situaci, kterou struktura kódu vyřešit nemůže: **to podstatné se často táhne přes několik [kontextů](../BoundedContext/)** a žádný z nich to sám neukáže.

**Poznáš to podle:**

- nový člověk se ptá „a co ta aplikace vlastně dělá" a dostane **popis obrazovek**
- na otázku po hodnotě produktu odpoví každý jinak — podle toho, na čem zrovna dělá
- vysvětlit projekt někomu zvenčí trvá **půl hodiny a diagram**
- při sporu o priority se argumentuje **dojmy**, protože není čím jiným
- [jádro](../CoreDomain/) jste si na workshopu vyjasnili, ale **nikde to není**

---

## Řešení

> „Therefore: **Write a short description (about one page) of the core domain and the value it will bring, the „value proposition."** Ignore those aspects that do not distinguish this domain model from others. Show how the domain model serves and balances diverse interests. **Keep it narrow. Write this statement early and revise it** as you gain new insight."

Pět pokynů a každý něco zakazuje:

| Pokyn | Co vylučuje |
| ----- | ----------- |
| **About one page** | Není to specifikace ani architektura |
| **The value it will bring** | Není to popis funkcí |
| **Ignore what doesn't distinguish** | Není to seznam všeho, co systém umí |
| **Keep it narrow** | Není to firemní vize ani marketing |
| **Write early and revise** | Není to jednorázový dokument do šuplíku |

### Jak to vypadá

Pro e-shop z [demo klasifikace](../CoreDomain/#demo) by prohlášení mohlo vypadat takhle:

> **Vize domény — doporučování a cenotvorba**
>
> Zákazník k nám chodí proto, že mu ukážeme zboží, které by sám nenašel, za cenu, která dává smysl jemu i nám. Obojí děláme lépe než katalogy, se kterými soutěžíme.
>
> **Doporučování** staví na tom, co zákazník prohlížel, co koupil a co koupili lidé s podobným chováním. Model musí umět pracovat i s velmi krátkou historií — u nového zákazníka máme pár minut, ne měsíce dat.
>
> **Cenotvorba** vyvažuje tři zájmy, které jdou proti sobě: marži, konkurenceschopnost a důvěru zákazníka. Cena se smí měnit v čase, ale nesmí se měnit tak, aby si toho zákazník všiml jako nespravedlnosti. To poslední je omezení, ne technický detail — je to důvod, proč nepoužíváme čistě aukční model.
>
> Katalog, objednávky, platby a fakturace jsou podpora. Musí fungovat spolehlivě a nezaslouží si víc pozornosti, než kolik jí potřebují, aby doporučování a cenotvorbě nestály v cestě.

Všimni si tří věcí, které dokument dělá a seznam funkcí neumí:

1. **Říká, co je jádro a co podpora** — poslední odstavec je půl věty a přesto určuje priority.
2. **Pojmenovává protichůdné zájmy** — „marže vs. konkurenceschopnost vs. důvěra". Evansovo *„serves and balances diverse interests"*.
3. **Obsahuje omezení, které vypadá jako obchodní rozmar** — „nesmí to působit nespravedlivě" — a tím ho povyšuje na návrhový požadavek.

### Co tam nepatří

| Nepatří tam | Proč | Kam to patří |
| ----------- | ---- | ------------ |
| Seznam funkcí | Neříká, co je důležité | Backlog |
| Technologie a architektura | Vize domény přežije přepis systému | Architektonická dokumentace |
| Popis obrazovek | Popisuje řešení, ne hodnotu | Návrh UI |
| Vše, co systém umí | Evans: *ignore what doesn't distinguish* | Nikam — o podpoře stačí věta |
| Firemní hodnoty a marketing | Není to leták | Web |

---

## Účastníci

| Role | Odpovědnost |
| ---- | ----------- |
| **Prohlášení** | Jedna stránka; žije v repozitáři vedle kódu |
| **Doménový expert / produkt** | Dodá obsah — čím firma vydělává a co jde proti sobě |
| **Tým** | Zná ho, používá při sporu o priority a připomíná, když přestane platit |

---

## Implementace v PHP

Kód se nepíše, ale **umístění rozhoduje o tom, jestli to bude živý dokument nebo mrtvý**:

```
docs/
    domain-vision.md     ← v repozitáři, ne na firemním disku
src/
    Recommendation/
    Pricing/
```

Tři praktické věci, které rozhodují víc než obsah:

- **V repozitáři, ne v cizím nástroji.** Dokument, který se needituje ve stejném commitu jako kód, zastará.
- **Prochází [code review](../../../Processes/CodeReview/).** Změna vize je změna jako každá jiná a má se o ní vědět.
- **Odkazuje se z hlavního README.** Co není na cestě, nikdo nečte.

---

## Kdy použít

- ✅ **Začíná projekt** a je potřeba nasměrovat rozhodování dřív, než vznikne model.
- ✅ **Přišel nový člověk** a nemá kde začít.
- ✅ **Jádro se táhne přes víc [kontextů](../BoundedContext/)** a struktura ho neukáže.
- ✅ **Vede se spor o priority** a chybí společný základ.
- ✅ Vysvětlování projektu zvenčí zabírá **pokaždé půl hodiny**.

## Kdy nepoužít

- ❌ **Produkt teprve hledá, čím bude.** Pak se vize mění týdně a psát ji je práce nazmar — počkej, až se ustálí.
- ❌ **Aplikace je CRUD bez jádra.** Není co destilovat a upřímné přiznání je lepší než vymyšlená vize.
- ❌ **Nikdo z byznysu není k dispozici.** Vize napsaná vývojáři popisuje, co je zajímavé, ne co vydělává.
- ❌ **Psalo by se to „protože se to má".** Dokument, který nikdo nepoužije při rozhodování, je jen další soubor.

---

## Časté chyby

| Chyba | Proč vadí | Jak správně |
| ----- | --------- | ----------- |
| Je z toho seznam funkcí | Neurčuje, co je důležité — a o to jde | Psát o hodnotě, ne o schopnostech |
| Deset stran místo jedné | Nikdo to nepřečte a nikdo to neaktualizuje | Evans: **about one page** |
| Popisuje i podporu | Rozmělní se to podstatné | *Ignore what doesn't distinguish* |
| Napsáno jednou a nikdy nerevidováno | Za rok popisuje projekt, který už neexistuje | Revidovat, když se mění pochopení |
| Leží mimo repozitář | Zastará dřív, než si toho někdo všimne | Do repozitáře, do review |
| Píšou to vývojáři sami | Popíše, co je technicky zajímavé | Obsah dodá byznys |
| Chybí protichůdné zájmy | Nejcennější část textu chybí | Pojmenovat, co jde proti sobě |
| Je to marketingový text | Nedá se podle něj rozhodovat o návrhu | Psát pro tým, ne pro zákazníky |

---

## V praxi

- **README projektu** — nejčastější místo, kde tenhle dokument v praxi žije, i když se mu tak neříká.
- **ADR (Architecture Decision Records)** — příbuzný žánr; vize domény je jejich doménová obdoba a měla by jim předcházet.
- **Product vision / value proposition canvas** — produktové nástroje, které řeší totéž z druhé strany; vize domény z nich může přímo vycházet.
- **Onboardingový dokument** — když existuje vize, onboarding se zkrátí o vysvětlování, „o co tu jde".

---

## Související patterny

| Pattern | Vztah |
| ------- | ----- |
| [Core Domain](../CoreDomain/) | **Předpoklad.** Vize popisuje jádro; bez rozhodnutí, co jádro je, není o čem psát. |
| [Highlighted Core](../HighlightedCore/) | **Přímé pokračování.** Evans: vize identifikuje jádro „in broad terms", ale konkrétní prvky nechává na výkladu — a to řeší až zvýrazněné jádro. |
| [Generic Subdomains](../GenericSubdomains/) | Vize říká, co jádrem není — a tím zdůvodňuje, co vytěsnit. |
| [Bounded Context](../BoundedContext/) | Vize může jádro popsat i tam, kde se táhne přes víc kontextů a struktura to neukáže. |
| [Context Map](../ContextMap/) | Mapa ukazuje vztahy mezi kontexty, vize říká, který z nich nese hodnotu. |
| [Ubiquitous Language](../) | Vize je první text, ve kterém se jednotný jazyk projeví — nebo se ukáže, že ho tým nemá. |

---

## Vztah k principům

| Princip | Jak souvisí |
| ------- | ----------- |
| [Zviditelni implicitní](../../Principles/ObjectDesign.md#zviditelni-implicitní) | Celý vzor je o tom — „všichni přece víme, o co jde" se stane textem, který jde přečíst a rozporovat. |
| [KISS](../../Principles/Simplicity.md#kiss--keep-it-simple) | Jedna stránka. Delší dokument problém neřeší, jen ho zakryje. |
| [YAGNI](../../Principles/Simplicity.md#yagni--you-arent-gonna-need-it) | *Keep it narrow* — vše, co neodlišuje, se vynechá. |

---

## Původ

|               |                                                   |
| ------------- | ------------------------------------------------- |
| **Zdroj**     | *Domain-Driven Design: Tackling Complexity in the Heart of Software* |
| **Autor**     | Eric Evans                                        |
| **Rok**       | 2003                                              |
| **Kategorie** | Strategický návrh — destilace (kapitola 15)       |
| **Obtížnost** | ●○○○○                                             |

V kapitole **Distillation** stojí hned za [Core Domain](../CoreDomain/) a [Generic Subdomains](../GenericSubdomains/) a je z celé kapitoly **nejlevnější**. Nevyžaduje refaktoring, nedotýká se kódu a napsat se dá za odpoledne.

Právě proto stojí za to začít jím. [Segregated Core](../SegregatedCore/) i [Abstract Core](../AbstractCore/) jsou zásahy do modelu, které se dělají týdny; tenhle vzor je stránka textu, která ale rozhodne, jestli ty zásahy vůbec budou mířit správně.

Obtížnost je jednička a přesto se vzor míjí často — nikoli složitostí, ale tím, že **napsat jednu stránku o hodnotě je těžší než deset stran o funkcích.** Vynechat vše, co neodlišuje, znamená rozhodnout, co je podružné, a to je nepříjemné.

Evans sám na hranice tohohle vzoru upozorňuje hned v definici následujícího: vize *„leaves the identification of the specific core model elements up to the vagaries of individual interpretation"* — samotný text nestačí, protože každý si pod ním představí jiné třídy. Na to je [Highlighted Core](../HighlightedCore/).

---

## Zdroje

- Eric Evans: *Domain-Driven Design*, Addison-Wesley, 2003 — kapitola 15, *Distillation*
- Eric Evans: [*Domain-Driven Design Reference*](https://www.domainlanguage.com/wp-content/uploads/2016/05/DDD_Reference_2015-03.pdf) (PDF, 2015) — souhrn definic, pod licencí CC BY 4.0

---

<details>
<summary>Metadata patternu</summary>

```yaml
name: Domain Vision Statement
name_cs: Prohlášení o vizi domény
category: Strategický návrh — destilace
source: DDD – Domain-Driven Design
authors: Eric Evans
year: 2003
difficulty: 1
tags: [destilace, jádro, dokumentace, hodnota, priority]
principles: [MakeImplicitExplicit, KISS, YAGNI]
related: [CoreDomain, HighlightedCore, GenericSubdomains, BoundedContext, ContextMap]
status: done
```

</details>
