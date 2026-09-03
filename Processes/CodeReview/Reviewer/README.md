# Code review — pro recenzenta

> [← zpět na Code review](../)

> **V jedné větě:** Čti odshora dolů — nejdřív jestli je to dobrý nápad, pak jestli je to dobře napsané, a schval, jakmile je kód lepší než dosud.

---

## V jakém pořadí číst

Nejčastější chyba recenzenta není to, že něco přehlédne. Je to **špatné pořadí** — začne u prvního řádku diffu, vyčerpá pozornost na detailech a k otázce, jestli má ta změna vůbec takhle vypadat, se nedostane.

Čti odshora dolů a **na každé úrovni se ptej, jestli má smysl pokračovat**:

| Pořadí | Otázka | Když je odpověď špatná |
| ------ | ------ | ---------------------- |
| **1. Záměr** | Řeší to ten problém, který má? | Zastav se a zeptej se; zbytek je jedno |
| **2. Návrh** | Patří to sem? Nebude se to špatně měnit? | **Nejdražší chyba** — tady se ještě opraví levně |
| **3. Funkčnost** | Dělá to, co popis slibuje? Co okrajové případy? | Konkrétní připomínka s příkladem vstupu |
| **4. Testy** | Testují chování, nebo implementaci? Chybí případ? | Napiš, který případ chybí |
| **5. Čitelnost** | Pochopím to za půl roku? Sedí názvy? | Návrh lepšího jména, nezávazně |
| **6. Detaily** | Překlepy, drobnosti | Označit jako nezávazné |

**Když najdeš problém na úrovni 2, nepokračuj na 3.** Nemá smysl komentovat pojmenování proměnných v kódu, který se celý přesune jinam — a autora to demotivuje dvakrát: jednou za návrh, podruhé za práci, kterou zahodí.

---

## Kdy schválit

Platí [standard z hlavního dokumentu](../#kdy-schválit): schvaluj, jakmile změna **prokazatelně zlepšuje kód**, i když není dokonalá.

V praxi to znamená rozhodnout u každé připomínky, do které patří kategorie:

| Kategorie | Příklad | Blokuje? |
| --------- | ------- | -------- |
| **Chyba** | Špatný výpočet, chybějící ošetření, bezpečnostní díra | **Ano** |
| **Návrh, který bude drahý změnit** | Doména závisí na databázi, veřejné API bez rozmyslu | **Ano** |
| **Chybějící test na to podstatné** | Nové pravidlo bez testu | **Ano** |
| **Zhoršení oproti současnému stavu** | Duplikace, kterou bylo možné snadno neudělat | Podle míry |
| **Šlo by to lépe** | Jiný návrh, který je jen jinak dobrý | **Ne** |
| **Preference** | Pojmenování, styl, pořadí metod | **Ne** |

**Tři čtvrtiny toho, co tě napadne, patří do posledních dvou řádků.** Napiš to — jen [označ, že to neblokuje](../Comments/).

Užitečná kontrolní otázka: *„Kdyby se to sloučilo takhle, budu to za měsíc považovat za problém?"* Když ne, neblokuj.

---

## Co hledat na úrovni návrhu

Nejcennější část review a jediná, kterou nezvládne stroj. Pomůcka: většina otázek, které tu stojí za položení, má v tomhle repozitáři [vlastní dokument](../../../SoftwareDesign/Principles/) — a **odkaz na princip je lepší argument než „mně se to nelíbí“**.

- **Patří ten kód sem?** Doménové pravidlo v kontroleru, výpočet v šabloně, SQL v entitě.
- **Kolik toho ta třída ví o okolí?** [Provázanost](../../../SoftwareDesign/Principles/CohesionAndCoupling.md#stupnice-provázanosti) je to, co se za rok platí nejdráž.
- **Dá se to otestovat bez celého světa?** Když test potřebuje databázi, síť a čas, obvykle je problém v návrhu, ne v testu.
- **Neopakuje se to?** Ale pozor na [falešné porušení DRY](../../../SoftwareDesign/Principles/Simplicity.md#falešné-porušení-dry--a-tam-vzniká-škoda) — dvě podobné věci nejsou totéž.
- **Není to řešení problému, který nemáme?** [YAGNI](../../../SoftwareDesign/Principles/Simplicity.md#yagni--you-arent-gonna-need-it) je nejčastěji porušený princip v kódu, který vypadá promyšleně.
- **Jde to jednodušeji?** Nejsilnější připomínka, jakou můžeš napsat — a nejtěžší.

---

## Co naopak nehledat

**Nedělej práci, kterou zvládne stroj.** Formátování, pořadí importů, chybějící středníky, jednoduché překlepy v kódu — na to patří formátovač a lint v CI. Každá minuta strávená mezerami je minuta, která nešla na návrh.

Když se v review opakovaně objevuje tentýž typ připomínky, **není to úkol pro recenzenty, ale pro nastavení projektu.** Napiš pravidlo do lintu a víc už to nikdo řešit nemusí.

---

## Když jsi rychlejší než pečlivější

Recenze má být rychlá — [do jednoho pracovního dne](../#do-kdy-odpovědět) — ale rychlost se nesmí platit tím, že se schvaluje bez čtení.

Když nemáš čas, máš tři poctivé možnosti:

1. **Řekni to.** „Kouknu na to zítra ráno“ je plnohodnotná odpověď a autor podle ní ví, na čem je.
2. **Předej to.** Někomu, kdo čas má — nejlíp s poznámkou proč.
3. **Zrecenzuj část a řekni kterou.** „Prošel jsem doménovou vrstvu, na API se nedívám“ je užitečné. Tiché schválení celku není.

Co je vždycky špatně: **schválit bez čtení, protože je to od kolegy, který to umí.** Tím se z procesu stává razítko a přestává platit i to, co si od něj slibuješ.

---

## Když recenzuješ jako junior

Zvyk, že junior je jen recenzovaný, mu bere nejrychlejší způsob, jak poznat cizí kód. **Recenzuj, i když tvoje schválení nakonec nestačí.**

Nejužitečnější připomínka, kterou můžeš napsat, je zároveň ta nejsnadnější:

> **question:** Nechápu, proč se tady kontroluje stav podruhé — nestačilo by to jednou v konstruktoru?

Otázka je vždycky legitimní. Buď se něco dozvíš, nebo jsi našel místo, které je nesrozumitelné — **a to je nález sám o sobě.** Kód, kterému nerozumí nový člověk v týmu, je kód s problémem, i když je správný.

Neboj se, že se zeptáš na hloupost. Obavy z toho, že otázka vypadá hloupě, jsou hlavní důvod, proč se nesrozumitelný kód dostane do produkce.

---

## Časté chyby

| Chyba | Proč vadí | Jak správně |
| ----- | --------- | ----------- |
| Čtení od prvního řádku diffu | Pozornost se vyčerpá na detailech dřív než na návrhu | Odshora dolů: záměr → návrh → detaily |
| Blokování kvůli preferenci | Proces se mění v překážku a autoři přestanou uklízet | Blokuj chyby a návrh; zbytek [nezávazně](../Comments/) |
| Připomínky k formátování | Práce pro stroj zabírá pozornost pro člověka | Formátovač a lint |
| Komentáře k detailům v kódu, který má odejít | Autor zahodí práci, na kterou dostal připomínky | Nejdřív vyřeš návrh |
| Schválení bez čtení | Razítko; navíc přebíráš odpovědnost, kterou nemůžeš unést | Řekni, že nemáš čas, nebo předej dál |
| Dávkování připomínek po jedné | Autor opraví, pošle znovu, přijde další — a takhle pětkrát | Přečti celé a napiš najednou |
| „Přepiš to celé“ | Nedá se s tím pracovat a je to demotivující | Konkrétně co a proč; navrhni první krok |
| Návrh bez odůvodnění | Autor buď poslechne bez pochopení, nebo se hádá | Napiš **proč** — ideálně s odkazem |
| Recenzent vidí kód poprvé v pull requestu | Návrhová připomínka přichází, když je pozdě | Domluvit se nad návrhem předem, u větších věcí |

Poslední řádek je ta nejlevnější prevence, jakou proces má: **u větší změny se vyplatí probrat návrh dřív, než se napíše.** Deset minut u tabule ušetří dvě kola review a situaci, kdy autor zahazuje třídenní práci.

---

## Související

| Dokument | Vztah |
| -------- | ----- |
| [Pro autora](../Author/) | Druhá strana téhož procesu |
| [Komentáře](../Comments/) | Jak formulovat, aby připomínka vedla ke změně, ne k obraně |
| [Principy návrhu](../../../SoftwareDesign/Principles/) | Společný jazyk pro připomínky k návrhu |
| [Cohesion & Coupling](../../../SoftwareDesign/Principles/CohesionAndCoupling.md) | Nejčastější téma návrhových připomínek |
| [Index podle problému](../../../SoftwareDesign/#index-podle-problému) | Když v kódu vidíš symptom a hledáš, jak se jmenuje jeho řešení |

---

## Zdroje

- [Google: How to do a code review](https://google.github.io/eng-practices/review/reviewer/)
- [The Standard of Code Review](https://google.github.io/eng-practices/review/reviewer/standard.html)
- [What to look for in a code review](https://google.github.io/eng-practices/review/reviewer/looking-for.html)
