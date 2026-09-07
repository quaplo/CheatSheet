# Plánovaný refaktoring

> [← zpět na Refaktoring](../)

> **V jedné větě:** Úklid, na který je vyhrazený čas a o kterém ví celý tým — nutná součást práce a zároveň signál, že ostatní způsoby refaktoringu vázly.

Fowler o něm píše dvě věty, které jdou proti sobě, a obě jsou důležité:

> „Planned refactoring is **a necessary element of most teams' approach** — however it's also **a sign that the team hasn't done enough refactoring using the other workflows**. If most of your refactoring is occurring only in planned work, then that's **a bad smell**."
>
> — Martin Fowler, *Workflows of Refactoring*, 2014

Je to jediné ze sedmi workflow, u kterého Fowler zároveň říká „dělejte to" a „to, že to musíte dělat, je špatná zpráva". Tenhle dokument je hlavně o tom druhém.

---

## Kdy po tom sáhnout

Ostatní workflow mají spouštěč v tom, co zrovna děláš: [nerozumím tomu](../ComprehensionRefactoring/), [je to ošklivé](../LitterPickupRefactoring/), [nemám kam to napsat](../PreparatoryRefactoring/). Plánovaný refaktoring má spouštěč jinde — **nikdo tam právě nic nedělá a stejně to potřebuje zásah**.

**Poznáš to podle:**

- oprava jednoho pravidla se musí udělat na třech místech a pokaždé se na jedno zapomene
- odhad každého úkolu v tom modulu je „dva dny, ale kdo ví"
- nový člověk se do té části nedostane ani po měsíci
- opakovaně se objevují chyby ze stejného místa, ale pokaždé jinak
- při plánování někdo řekne „tam radši nesahat"

Fowler pro tenhle případ používá mechanismus, který má většina týmů po ruce: **refaktoringovou story**. Je to položka v plánu jako každá jiná — má rozsah, má odhad a někdo ji dělá.

---

## Jak vypadá refaktoringová story

Story, která se dá dodělat, musí mít tři věci. Všechny tři vycházejí z toho, co demo měří.

**1. Cílový stav napsaný jednou větou.** Ne „uklidit slevy", ale *„pravidlo pro slevu je v jedné třídě a všechny tři konzumenty ho volají"*. Bez toho se nepozná, kdy je hotovo.

**2. Rozdělení na kroky, po kterých se dá odejít.** Každý krok samostatně nasaditelný, každý beze změny chování. Kolik kroků, tolik míst, kde se dá story přerušit, aniž by po ní zbyl nepořádek.

**3. Odpověď na otázku „co když to nedoděláme".** Tahle otázka zní pesimisticky a přesně proto se neptá. Přitom je to ta jediná, která rozhoduje o tom, jak se story rozdělí.

> [!IMPORTANT]
> Refaktoringová story **nemění chování**. Když je v ní schovaná i oprava chyby nebo nová funkce, přestává být vratná a recenzent v ní nemá co hledat. Platí [dva klobouky](../TwoHats/), jen ve větším měřítku.

---

## Jak to vypadá

Zadání: **pravidlo pro slevu je ve třech třídách.** Každá si počítá procenta sama, včetně stropu 12 %.

```
InvoicePdf.php
OrderExport.php
OrderSummary.php
```

Na [litter-pickup](../LitterPickupRefactoring/) je to moc velké a [přípravný refaktoring](../PreparatoryRefactoring/) to není — nikdo do těch tříd zrovna nic nepřidává. Je to práce, o které musí vědět tým.

### Story rozdělená na tři kroky

Vytvořit `DiscountPolicy` a přepojit konzumenty **jednoho po druhém**:

```
stav                      míst s pravidlem    běží?    chování shodné?
výchozí stav              3                   ano      ano (36/36)
krok 1: OrderExport       3                   ano      ano (36/36)
krok 2: InvoicePdf        2                   ano      ano (36/36)
krok 3: OrderSummary      1                   ano      ano (36/36)
```

Zajímavý je první krok: **míst je pořád stejně.** `DiscountPolicy` přibyla, ale dvě třídy si pravidlo drží dál — takže se zaplatilo a nic se nezlepšilo. To je ta část story, která se nejhůř obhajuje, a zároveň ta, bez které zbylé dva kroky nejdou udělat.

### Co zbude, když se to nedodělá

Tady je celý rozdíl mezi „po krocích" a „velkým třeskem". Demo obě varianty přeruší:

```
přerušeno po kroku 1 (po krocích)     3 místa
přerušeno po kroku 2 (po krocích)     2 místa
přerušeno uprostřed velkého třesku    4 místa
výchozí stav pro srovnání             3 místa
```

**Přerušený velký třesk je horší než výchozí stav** — pravidlo je teď na čtyřech místech místo tří, protože vedle tří původních kopií stojí i nová abstrakce, kterou nikdo nedopoužil.

A teď to podstatné: **chování je i v tom stavu shodné, 36 z 36.** Nic se nerozbilo, žádný test nespadl, nikomu se nic nenahlásí. Přerušená refaktoringová story po sobě neuklidí a nikdo si toho nevšimne.

```
postup                  bezpečných zastávek    když se zastaví jinde
po krocích              3                      jiná místa nejsou
velký třesk             1 — až na konci        4 místa místo 3
```

Práce je v obou případech stejně velká. Liší se jen tím, **kolikrát se dá odejít a nechat to v pořádku**.

---

## Kdy je to špatná zpráva

Fowlerova druhá věta se dá ověřit. Není to jeho měřítko — je to způsob, jak si tu otázku položit konkrétně:

**Projdi posledních dvacet sloučených pull requestů a spočítej, ve kterých je refaktoringový commit bez čísla úkolu.**

| Kolik jich je | Co to znamená |
| ------------- | ------------- |
| Většina | Ostatní workflow fungují. Plánovaný refaktoring je doplněk, ne záchranná brzda. |
| Několik | Normální stav. |
| Žádný | Refaktoruje se **jen** tehdy, když je na to story. To je ten bad smell. |

Poslední řádek má následek, který se pozná až po roce: refaktoring, který se dělá jen na povel, **soutěží o čas s funkcemi** — a v té soutěži vždycky prohraje. Průběžný úklid s ničím nesoutěží, protože je součástí úkolu.

Fowler to v závěru shrnuje jako doporučení, ne jako zákaz plánovaného refaktoringu:

> „For most teams this needs **more effort into the day-to-day refactoring workflows** in order to introduce steady improvement."

---

## Plánovaný nebo dlouhodobý?

Dvě věci, o kterých ví tým, se snadno pletou. Rozdíl není ve velikosti kódu, ale v tom, **odkud se na to bere čas**.

| | Plánovaný refaktoring | [Dlouhodobý refaktoring](../System/) |
| --- | --- | --- |
| Trvá | jednu story | *„multiple iterations over several months"* |
| Odkud čas | **vyhrazený** — story v plánu | **z běžné práce** — po kouskách při jiných úkolech |
| Co tým odsouhlasí | rozsah story | hrubý cílový stav **a hrubý plán, jak se tam dostat** |
| Typický nástroj | rozdělení na nasaditelné kroky | [Branch by Abstraction](../System/BranchByAbstraction/) |
| Když se to přeruší | zbude půl migrace | zbude abstrakce, která funguje dál |

Prostřední řádek je ten praktický. **Vyhrazený čas se dá odebrat** — a odebere se, jakmile přijde něco naléhavějšího. Právě proto Fowler u dlouhodobého refaktoringu zdůrazňuje, že se dělá *během běžné práce*: to, co není v plánu, se nedá z plánu vyškrtnout.

---

## Časté chyby

| Chyba | Proč vadí | Jak správně |
| ----- | --------- | ----------- |
| Story bez kroků, které jde nasadit | Přerušení nechá kód horší, než byl | Rozdělit tak, aby se dalo odejít po každém kroku |
| Ve story je i oprava chyby nebo nová funkce | Nejde vrátit a recenzent netuší, co hledat | Dva klobouky — oddělené commity, oddělené story |
| „Refaktoringový sprint" | Měsíc bez dodané funkce; příště to nikdo neschválí | Menší story průběžně |
| Cíl zní „uklidit modul X" | Nikdy se nepozná, že je hotovo | Cílový stav jednou větou |
| Dělá se to bez testů | Není jak poznat, že se chování nezměnilo | [Charakterizační testy](../CharacterizationTests/) |
| Story se odloží „na příští iteraci" | Rozdělaná migrace je horší než žádná | Buď dodělat, nebo vrátit — ne odložit |
| Všechen refaktoring jde přes story | Ten bad smell z Fowlerovy věty | [Litter-pickup](../LitterPickupRefactoring/) a [comprehension](../ComprehensionRefactoring/) do běžné práce |

Předposlední řádek je ten, který demo měří. **Odložená story není neutrální stav** — je to stav, který někdo zaplatil a nikdo z něj nic nemá.

---

## Demo

```bash
php Refactoring/PlannedRefactoring/demo/run.php
```

Refaktoringová story „sjednotit výpočet slevy" na třech třídách, které si pravidlo počítají každá po svém. Demo najde v kódu všechna místa, která to pravidlo znají (podle otisku — stropu 12 %), a projde story po krocích:

```
stav                      míst s pravidlem    běží?    chování shodné?
výchozí stav              3                   ano      ano (36/36)
krok 1: OrderExport       3                   ano      ano (36/36)
krok 2: InvoicePdf        2                   ano      ano (36/36)
krok 3: OrderSummary      1                   ano      ano (36/36)
```

Pak tutéž story přeruší uprostřed — jednou rozdělenou na kroky, jednou jako velký třesk — a spočítá, co po ní zbylo. **Přerušený velký třesk vyjde na čtyři místa místo tří, a chování je přitom pořád shodné (36/36).** Nic nespadne; jen je to horší než předtím.

Nakonec ověří, kolik má každý postup bezpečných zastávek: po krocích tři, velký třesk jednu.

---

## Související

| Dokument | Vztah |
| -------- | ----- |
| [Dlouhodobý refaktoring](../LongTermRefactoring/) | Fowlerův *Long Term Refactoring* — totéž na měsíce a bez vyhrazeného času. |
| [Refaktoring systému](../System/) | Techniky, ze kterých se obojí skládá. |
| [Branch by Abstraction](../System/BranchByAbstraction/) | Nástroj, který Fowler u dlouhodobého refaktoringu jmenuje. |
| [Dva klobouky](../TwoHats/) | Proč story nesmí měnit chování — jinak ji nejde vrátit. |
| [TDD refaktoring](../TddRefactoring/) | První z průběžných workflow; jeho soustavné vynechávání je vidět právě tady. |
| [Litter-pickup](../LitterPickupRefactoring/) | Průběžný úklid, jehož absence dělá z plánovaného refaktoringu nutnost. |
| [Comprehension refactoring](../ComprehensionRefactoring/) | Druhý z průběžných způsobů, které mají story předcházet. |
| [Přípravný refaktoring](../PreparatoryRefactoring/) | Kde je hranice: příprava má spouštěč v úkolu, story ne. |
| [Charakterizační testy](../CharacterizationTests/) | Bez sítě se story dělat nedá — a u cizího modulu obvykle chybí. |
| [Refaktoring kódu](../Code/) | Konkrétní techniky, ze kterých se story skládá. |
| [DRY](../../SoftwareDesign/Principles/Simplicity.md#dry--dont-repeat-yourself) | Pravidlo na třech místech je učebnicové porušení — a demo ho měří. |
| [Scrum](../../Processes/Scrum/) | Kde v procesu refaktoringová story bydlí a s čím soutěží. |
| [Extreme Programming](../../Processes/ExtremeProgramming/) | Odkud pochází představa, že refaktoring je průběžný, ne plánovaný. |

---

## Původ

|             |                    |
| ----------- | ------------------ |
| **Autor**   | Martin Fowler      |
| **Rok**     | 2014               |
| **Zdroj**   | *Workflows of Refactoring* |
| **Náročnost** | ●●●○○            |

Plánovaný refaktoring je šesté ze **sedmi workflow**, které Fowler popsal v infodecku z 8. ledna 2014. V tom seznamu je spolu s dlouhodobým jediné, u kterého se **rozhoduje tým** — první čtyři jsou součástí běžné práce a nikdo se na ně neptá.

Mechanismus, který Fowler jmenuje, jsou **„refactoring stories"** — položky v plánu určené na *„larger areas on problematic code that need dedicated attention"*.

Na náročnosti trojka není mechanika. Ta je stejná jako u [refaktoringů kódu](../Code/) — jen se jich udělá víc za sebou. Těžké jsou dvě věci a obě jsou mimo editor:

- **Rozdělit story tak, aby přerušení nebolelo.** To se dělá při jejím psaní, ne až když se přeruší.
- **Obhájit první krok.** Ten, po kterém je práce zaplacená a měřitelně se nic nezlepšilo.

---

## Zdroje

- Martin Fowler: [*Workflows of Refactoring*](https://martinfowler.com/articles/workflowsOfRefactoring/), 8. ledna 2014
- Martin Fowler: [*Opportunistic Refactoring*](https://martinfowler.com/bliki/OpportunisticRefactoring.html)

---

<details>
<summary>Metadata techniky</summary>

```yaml
name: Plánovaný refaktoring
level: příprava
author: Martin Fowler
year: 2014
duration: jedna story — dny až týdny
reversible: ano — refaktoring nic nemění
requires_tests: ano
difficulty: 3
tags: [workflow, refactoring story, plánování, kdy refaktorovat, bad smell]
leads_to: [BranchByAbstraction]
related: [LitterPickupRefactoring, ComprehensionRefactoring, PreparatoryRefactoring, CharacterizationTests]
status: done
```

</details>
