# Extreme Programming (XP)

> [← zpět na Procesy](../)

> **V jedné větě:** Vezmi postupy, o kterých se ví, že fungují, a dělej je **naplno a pořád** — když je dobré kód revidovat, revidujme ho průběžně; když je dobré testovat, pišme testy první.

> [!NOTE]
> **XP je nejmíň známý a nejvíc používaný přístup z celé téhle sekce.** Když píšeš testy před kódem, máš CI na každý push, průběžně refaktoruješ, děláš code review a plánuješ v uživatelských příbězích — **děláš praktiky, které pojmenovalo XP.** Rozšířily se natolik, že se přestaly spojovat se jménem, ze kterého vzešly.

---

## Proč „extrémní"

Název není o tvrdosti ani o rychlosti. Je o tom, že se osvědčené postupy dotáhnou **na maximum**, místo aby se dělaly občas:

| Ví se, že pomáhá | XP z toho udělá |
| ---------------- | --------------- |
| Kód někdo zkontroluje | **Kontroluje ho druhý člověk průběžně** — párové programování |
| Psát testy | **Psát je dřív než kód** — test-first |
| Integrovat práci | **Integrovat několikrát denně** — průběžná integrace |
| Zjednodušovat návrh | **Navrhovat jen na to, co je potřeba teď** — přírůstkový návrh |
| Mluvit se zákazníkem | **Mít ho v týmu** |
| Vydávat často | **Vydávat v týdenním cyklu** |

Myšlenka za tím je, že tyhle praktiky se **navzájem drží**. Průběžný refaktoring je bezpečný jen díky testům; testy se dají psát první jen díky jednoduchému návrhu; společné vlastnictví kódu funguje jen díky testům a párování. **Vytáhnout jednu praktiku samostatně obvykle nefunguje** — a to je nejčastější důvod, proč se lidem XP „nechytlo".

---

## Odkud pochází

Vzniklo na konkrétním projektu. **Chrysler Comprehensive Compensation System (C3)** — přepis mzdových systémů automobilky — běžel od roku 1993 a nefungoval; po letech nevytiskl jedinou výplatnici. V **březnu 1996** se jeho vedení ujal **Kent Beck**.

Postup, který na projektu s týmem vypiloval, dostal v roce **1997** jméno Extreme Programming. Systém se rozběhl v roce **1997** a vyplácel zhruba deset tisíc lidí. Projekt Chrysler nakonec v únoru **2000** ukončil — a stojí za to to zmínit, protože se to k příběhu XP obvykle nedodává.

Beck metodu popsal v knize **Extreme Programming Explained: Embrace Change** (říjen 1999). **Druhé vydání z roku 2004** (s Cynthií Andres) je podstatně přepracované a je to to, co se dnes myslí XP.

Kent Beck je zároveň jedním ze signatářů [Agile Manifesta](../AgileManifesto/) a XP bylo jedním z přístupů zastoupených v Snowbirdu roku 2001.

> [!NOTE]
> Obě vydání se liší natolik, že se o XP mluví dvěma způsoby. **První vydání (1999):** čtyři hodnoty a **dvanáct praktik** — Planning Game, Small Releases, Metaphor, Simple Design, Testing, Refactoring, Pair Programming, Collective Ownership, Continuous Integration, 40-hour Week, On-site Customer, Coding Standards. To je verze, kterou najdeš na většině webů. **Druhé vydání (2004)** má pět hodnot, čtrnáct principů a praktiky rozdělené na třináct primárních a jedenáct doplňkových. Dál v dokumentu popisuju druhé vydání.

---

## Pět hodnot

| Hodnota | O čem je |
| ------- | -------- |
| **Communication** | Většina problémů v projektu je nakonec problém komunikace. |
| **Simplicity** | Ptát se: co je nejjednodušší věc, která by mohla fungovat? |
| **Feedback** | Zpětná vazba co nejdřív — z testů, z buildu, od zákazníka. |
| **Courage** | Odvaha zahodit kód, říct pravdu o odhadu, pustit se do velkého refaktoringu. |
| **Respect** | Přidán ve druhém vydání. Bez něj ostatní čtyři nefungují. |

Kniha k nim dodává, že je můžou doplnit i další hodnoty — seznam není uzavřený.

---

## Čtrnáct principů

Principy jsou most mezi hodnotami (obecné) a praktikami (konkrétní):

| | | |
| --- | --- | --- |
| Humanity | Economics | Mutual Benefit |
| Self-Similarity | Improvement | Diversity |
| Reflection | Flow | Opportunity |
| Redundancy | Failure | Quality |
| Baby Steps | Accepted Responsibility | |

Tři, které se vyplatí znát jménem:

- **Baby Steps** — malé kroky. Velký skok se nedá vrátit; deset malých ano.
- **Failure** — když nevíš, kterou cestou jít, zkus jednu a nech se poučit. **Neúspěšný pokus není plýtvání, pokud přinesl znalost.**
- **Humanity** — software píšou lidé, kteří potřebují spát a mít pocit smyslu. Praktika, která to ignoruje, dlouho nevydrží.

---

## Třináct primárních praktik

Kniha je vyjmenovává za sebou; **rozdělení do skupin níž je pro přehlednost, ne z knihy.**

### Tým a prostředí

| Praktika | Co znamená |
| -------- | ---------- |
| **Sit Together** | Tým sedí spolu, ne rozesetý po budově |
| **Whole Team** | V týmu jsou všichni, kdo jsou k dodání potřeba — včetně byznysu |
| **Informative Workspace** | Z prostoru je vidět stav práce (nástěnka, monitor s buildem) |
| **Energized Work** | Pracuje se tolik, kolik jde udržet — přesčasy si berou zpátky víc, než dají |

### Práce s kódem

| Praktika | Co znamená |
| -------- | ---------- |
| **Pair Programming** | Dva lidé u jednoho kódu; kontrola probíhá průběžně |
| **Test-First Programming** | **Nejdřív test, pak kód** — dnes známé jako TDD |
| **Continuous Integration** | Integrovat průběžně, ne jednou za čas |
| **Ten-Minute Build** | Sestavení a testy **do deseti minut** |
| **Incremental Design** | Návrh se vyvíjí s kódem, nedělá se celý dopředu |

**Ten-Minute Build** je z nich nejkonkrétnější a nejsnáz ověřitelná. Není to arbitrární číslo — je to hranice, za kterou lidé přestanou build pouštět a začnou ho obcházet. Stejný argument stojí za [požadavkem na rychlou CI](../../GitWorkflows/TrunkBasedDevelopment/#co-si-to-vyžaduje) u Trunk-Based Development.

### Plánování

| Praktika | Co znamená |
| -------- | ---------- |
| **Stories** | Práce se popisuje jako **uživatelské příběhy** — co uživatel potřebuje |
| **Weekly Cycle** | Týdenní rytmus plánování |
| **Quarterly Cycle** | Čtvrtletní pohled na směr |
| **Slack** | **Rezerva v plánu** — něco, co jde vypustit, když se to nestihne |

**Slack** je nejpřehlíženější praktika z celého seznamu. Plán bez rezervy vede k tomu, že se při prvním zdržení buď nestihne termín, nebo se ubere na kvalitě — a druhé bývá tišší. Rezerva není lenost, je to **to, co dělá závazek splnitelným**.

---

## Jedenáct doplňkových praktik

Druhé vydání je odděluje s tím, že se k nim tým dostane, až mu sedí primární:

Real Customer Involvement · Incremental Deployment · Team Continuity · Shrinking Teams · Root-Cause Analysis · Shared Code · Code and Tests · Single Code Base · Daily Deployment · Negotiated Scope Contract · Pay-Per-Use

Za pozornost stojí **Single Code Base** (jedna hlavní větev, žádné dlouhodobě paralelní verze) a **Daily Deployment** — obojí popisuje totéž, co dnes známe jako [Trunk-Based Development](../../GitWorkflows/TrunkBasedDevelopment/), o dobrých patnáct let dřív.

---

## Co z XP používáš, aniž bys to tak nazýval

| Dnešní název | Praktika XP |
| ------------ | ----------- |
| **TDD** | Test-First Programming |
| **CI / pipeline na každý push** | Continuous Integration |
| **Refaktoring jako běžná činnost** | Incremental Design |
| **User stories v ticketech** | Stories |
| **[Code review](../CodeReview/)** | příbuzné Pair Programming — táž myšlenka, jiná forma |
| **[Trunk-Based Development](../../GitWorkflows/TrunkBasedDevelopment/)** | Single Code Base + Daily Deployment |
| **[YAGNI](../../SoftwareDesign/Principles/Simplicity.md#yagni--you-arent-gonna-need-it)** | vzešlo z XP komunity (Ron Jeffries, Kent Beck) |
| **Udržitelné tempo** | Energized Work |

**Tohle je hlavní odkaz XP.** Jako celek se v původní podobě prosadilo málokde, ale jeho praktiky se rozšířily samostatně a dnes je považujeme za samozřejmost.

---

## XP a Scrum

Nejsou to soupeři a v praxi se kombinují nejčastěji ze všech dvojic v této sekci.

| | [**Scrum**](../Scrum/) | **XP** |
| --- | --- | --- |
| Co řeší | **jak tým organizovat** — role, události, artefakty | **jak psát software** — konkrétní inženýrské praktiky |
| Mluví o kódu | ne | **ano, hlavně** |
| Předepisuje role | tři | žádné |
| Rytmus | Sprint (měsíc nebo méně) | Weekly Cycle + Quarterly Cycle |
| Co říká o testech | nic (jen „Definice Hotovo") | **test-first, konkrétně** |

**Scrum je záměrně neúplný** a sám říká, že „rámec je záměrně neúplný, pouze definuje části potřebné k implementaci teorie Scrum" — o technických praktikách mlčí. XP je přesně to, co se do té mezery hodí: **Scrum jako rámec, XP jako obsah.**

Vysvětluje to i častý jev: tým dělá Scrum, drží všechny události, a stejně mu to nefunguje — protože k tomu chybí inženýrské praktiky, které udrží kvalitu. **Sprint sám o sobě nevyrobí kód, který jde měnit.**

---

## Co je na tom náročné

Poctivě: XP jako celek se prosadilo míň než Scrum, a má to důvody.

- **Párové programování** je nejtěžší prodat — vypadá to jako dvojnásobná cena a část lidí ho vyloženě nechce. V praxi se často nahrazuje [code review](../CodeReview/).
- **Sit Together a Whole Team** předpokládají tým na jednom místě; při práci na dálku se plní hůř.
- **Zákazník v týmu** (On-site Customer, později Real Customer Involvement) je v mnoha organizacích nedosažitelný.
- **Praktiky se navzájem drží.** Kdo vezme jen některé, obvykle zjistí, že bez ostatních nefungují — a přičte to XP.
- **Test-first je dovednost**, ne nastavení. Trvá měsíce, než se tým dostane přes fázi, kdy testy spíš překážejí.

Právě proto se z XP v praxi nejčastěji **bere po částech** — a jak je vidět [výš](#co-z-xp-používáš-aniž-bys-to-tak-nazýval), s velkým úspěchem.

---

## Souvislost s naší prací

| Dokument | Souvislost |
| -------- | ---------- |
| [Agile Manifesto](../AgileManifesto/) | **Kent Beck je signatář**; XP bylo v Snowbirdu zastoupené. |
| [Scrum](../Scrum/) | Nejčastější kombinace — [srovnání výš](#xp-a-scrum). |
| [Kanban](../Kanban/) | Také se kombinuje; Kanban řeší tok, XP inženýrskou práci. |
| [Code review](../CodeReview/) | Dnešní podoba téhož, co XP řeší párovým programováním. |
| [Trunk-Based Development](../../GitWorkflows/TrunkBasedDevelopment/) | Continuous Integration, Single Code Base a Daily Deployment jsou jeho přímí předchůdci. |
| [Simplicity — YAGNI](../../SoftwareDesign/Principles/Simplicity.md#yagni--you-arent-gonna-need-it) | Princip, který vzešel z XP komunity. |
| [Software Design](../../SoftwareDesign/) | „Incremental Design" a průběžný refaktoring předpokládají, že víš, k čemu návrh vést. |
| [Waterfall](../Waterfall/) | Protiklad — návrh dopředu a celý, místo průběžně a po částech. |

---

## Zdroje

- Kent Beck, Cynthia Andres: *Extreme Programming Explained: Embrace Change*, 2. vydání, Addison-Wesley, 2004 — [ukázka včetně obsahu](https://ptgmedia.pearsoncmg.com/images/9780321278654/samplepages/9780321278654.pdf) (PDF)
- Kent Beck: *Extreme Programming Explained: Embrace Change*, 1. vydání, Addison-Wesley, 1999
- [Agile Alliance: What is Extreme Programming?](https://agilealliance.org/glossary/xp/)
- [Chrysler Comprehensive Compensation System](https://en.wikipedia.org/wiki/Chrysler_Comprehensive_Compensation_System) — projekt, na kterém XP vzniklo
