# Boy scout rule

> [← zpět na Refaktoring](../)

> **V jedné větě:** Ze souboru, do kterého jsi sáhl, odejdi o kousek čistší, než jsi do něj přišel — a „o kousek" znamená aspoň jednu věc.

Pravidlo pochází ze skautingu a do programování ho přenesl **Robert C. Martin** v eseji *The Boy Scout Rule* (2010). Jeho verze mluví o commitu, a to je na ní to podstatné:

> „What if we followed a similar rule in our code: **„Always check a module in cleaner than when you checked it out."** No matter who the original author was, what if we always made some effort, no matter how small, to improve the module."
>
> — Robert C. Martin, *97 Things Every Programmer Should Know*, 2010

Skautská verze zní *„always leave the campground cleaner than you found it"* a je sama parafrází věty zakladatele skautingu **Roberta Baden-Powella**: *„Try and leave this world a little better than you found it."*

---

## Kolik je „o kousek"

Tohle je na Martinově formulaci to nejcennější a zároveň to, co se z ní nejčastěji vynechá. **Pravidlo má stanovené minimum a je záměrně nízké:**

> „You don't have to make every module perfect before you check it in. You simply have to make it **a little bit better** than when you checked it out. Of course, this means that any code you **add** to a module must be clean. It also means that you **clean up at least one other thing** before you check the module back in."

Jsou to tedy dvě povinnosti, ne jedna:

1. **Co přidáváš, přidej čisté.** To je samozřejmost a nikdo se o ní nehádá.
2. **A ještě jednu věc ukliď.** Tahle druhá se vynechává skoro vždycky.

Martin i vyjmenovává, jak malá ta jedna věc smí být:

> „You might simply improve the name of one variable, or split one long function into two smaller functions. You might break a circular dependency, or add an interface to decouple policy from detail."

**Přejmenovat jednu proměnnou stačí.** To je celá laťka.

---

## Vztah k litter-pickupu

Je to táž věc popsaná ze dvou stran a v katalogu ji proto najdeš dvakrát:

| | Boy scout rule | [Litter-pickup](../LitterPickupRefactoring/) |
| --- | --- | --- |
| Co to je | **norma týmu** | **postup jednotlivce** |
| Odpovídá na | kdy je commit hotový | co dělat s nepořádkem, na který narazíš |
| Formulace | per commit — dá se ověřit | per situace — rozhodovací postup |
| Autor | Robert C. Martin, 2010 | Martin Fowler, 2014 |

**Mechanika je v litter-pickupu** — co je odpadek, kde je hranice, proč dva commity. Tenhle dokument přidává jen to, co je vlastní té normě: **kolik stačí a proč je to věc týmu, ne jednotlivce.**

Ta druhá polovina je pointa celé eseje:

> „Caring for our own code is one thing. **Caring for the team's code is quite another.** Teams help each other, and clean up after each other. They follow the Boy Scout rule because it's good for everyone, not just good for themselves."

A odtud pochází i jméno Fowlerova workflow — Martin píše, že *„the act of leaving a mess in the code should be as socially unacceptable as **littering**."*

---

## Jak si to ověřit

Protože pravidlo mluví o commitu, dá se vzít doslova a udělat z něj kontrolu. Demo ji pustí na čtyři různé commity, které opravují **tutéž chybu**:

```
commit                      odpadků před   po    splňuje pravidlo?
C: jen oprava chyby         7              7     NE — odešel jsi stejně špinavý
A: oprava i úklid naráz     7              0     ano
B1: jen úklid               7              0     ano
B2: úklid, pak oprava       7              0     ano
```

Cesta C opravila chybu a nic víc. **Funguje, prošla by review a pravidlo nesplňuje** — příští člověk najde ten soubor přesně takový, jaký jsi ho našel ty.

---

## Co ta kontrola neříká

Číslo je užitečné, ale samo o sobě nestačí:

```
umí říct        jestli odpadků ubylo
neumí říct      jestli je kód po tom lepší
neumí říct      jestli se nezměnilo chování
neumí říct      jestli úklid patřil do tohohle commitu
```

> [!IMPORTANT]
> **Pravidlo je návyk, ne metrika.** Čistě podle čísel je nejlepší commit ten, který uklidil ze sedmi odpadků na nulu a nic jiného neudělal — jenže ten neopravil nic, kvůli čemu jsi přišel. Kdyby se z toho udělal ukazatel v CI, začne se optimalizovat na číslo.

---

## Časté chyby

| Chyba | Proč vadí | Jak správně |
| ----- | --------- | ----------- |
| „Ukliď to celé, když už tam jsi" | Z pravidla se stane nesplnitelný nárok a přestane se dodržovat | Jedna věc stačí — to je Martinova laťka |
| Uklidí se jen to, co jsem přidal | To je první polovina pravidla; druhá je „a ještě jednu věc" | Nový kód čistě **a** jeden úklid navíc |
| Úklid v commitu se změnou chování | Podstatná řádka se ztratí | [Dva klobouky](../TwoHats/) — oddělené commity |
| Pravidlo se změří a dá do CI | Optimalizuje se na číslo, ne na čitelnost | Ověřit občas, nevynucovat strojem |
| Platí jen pro můj kód | Pravidlo je o kódu týmu, ne o vlastnictví | „No matter who the original author was" |

---

## Demo

```bash
php Refactoring/BoyScoutRule/demo/run.php
```

Krátká ukázka, která z Martinovy věty udělá spustitelnou kontrolu. Detektor odpadků i vzorky si půjčuje od [litter-pickupu](../LitterPickupRefactoring/), protože je to táž věc měřená z druhé strany — a rovnou tím ukazuje, že pouhá oprava chyby pravidlo nesplňuje.

---

## Související

| Dokument | Vztah |
| -------- | ----- |
| [Litter-pickup](../LitterPickupRefactoring/) | **Mechanika**: co je odpadek, kde je hranice, proč dva commity. |
| [Dva klobouky](../TwoHats/) | Proč úklid patří do vlastního commitu. |
| [Code review: recenzent](../../Processes/CodeReview/Reviewer/) | Kde se dodržování normy pozná — a kde se dá připomenout. |
| [Plánovaný refaktoring](../PlannedRefactoring/) | Co přijde, když se pravidlo soustavně nedodržuje. |

---

## Původ

|             |                    |
| ----------- | ------------------ |
| **Autor**   | Robert C. Martin   |
| **Rok**     | 2010               |
| **Zdroj**   | *97 Things Every Programmer Should Know* |
| **Náročnost** | ●○○○○            |

Esej vyšla ve sbírce **97 Things Every Programmer Should Know** (O'Reilly, 2010, editor Kevlin Henney) a má necelou stranu. Martin v ní netvrdí nic složitého — tvrdí, že kdyby se to pravidlo dodržovalo, *„we'd see the end of the relentless deterioration of our software systems."*

Jednička na náročnosti je za mechaniku a je poctivá: přejmenovat proměnnou umí každý. Těžké je jen to, že se to musí udělat **pokaždé**, a nikdo si nevšimne, když se to neudělá.

---

## Zdroje

- Robert C. Martin: [*The Boy Scout Rule*](https://github.com/97-things/97-things-every-programmer-should-know/blob/master/en/thing_08/README.md), in: *97 Things Every Programmer Should Know*, O'Reilly, 2010
- Martin Fowler: [*Workflows of Refactoring*](https://martinfowler.com/articles/workflowsOfRefactoring/), 2014 — kde totéž vystupuje jako *camp site rule*

---

<details>
<summary>Metadata techniky</summary>

```yaml
name: Boy scout rule
level: pravidlo
author: Robert C. Martin
year: 2010
duration: trvale — je to návyk, ne úkon
reversible: netýká se — norma, ne zásah
requires_tests: ano — úklid nesmí změnit chování
difficulty: 1
tags: [pravidlo, norma týmu, camp site rule, úklid, per commit]
leads_to: []
related: [LitterPickupRefactoring, TwoHats, CodeReview]
status: done
```

</details>
