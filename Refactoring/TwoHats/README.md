# Dva klobouky

> [← zpět na Refaktoring](../)

> **V jedné větě:** Buď přidáváš funkci, nebo refaktoruješ — nikdy obojí zároveň, a v historii se to pozná podle toho, že jsou to dva commity.

Metafora je **Kenta Becka** a Fowler ji šíří od prvního vydání *Refactoringu*. V infodecku o workflow ji staví před všechny ostatní způsoby refaktoringu, protože platí pro každý z nich:

> **Refactoring** — „When refactoring every change you make is a **small behavior-preserving change**. You only refactor with green tests, and **any test failing indicates a mistake**. By stringing together a series of small changes like this you can move more quickly and with less risk because you shouldn't get trapped in debugging."
>
> **Adding Function** — „Any other change to the code is adding function. **You will add new tests and break existing tests.** You aren't confined to behavior-preserving changes (but it's wise to keep changes small and return to green tests swiftly)."
>
> — Martin Fowler, *Workflows of Refactoring*, 2014

A pak ta věta, kvůli které to celé je:

> **„You can only wear one hat at a time."**

Střídat je můžeš klidně každé dvě minuty — Fowler to tak píše. Mít je na hlavě obě naráz ale nejde.

---

## Jak se pozná, který máš na hlavě

Nejrychlejší test není v tom, co děláš, ale **co pro tebe znamená červený test**:

| | 🧢 Refaktoring | 🎩 Přidávání funkce |
| --- | --- | --- |
| Smí se změnit chování | **ne** | ano, o to jde |
| Sada na začátku | zelená | zelená |
| Sada během práce | **zelená celou dobu** | červená, a to je v pořádku |
| Červený test znamená | **udělal jsi chybu** | ještě nejsi hotový |
| Přibývají testy | ne | ano |
| Dá se to zahodit | **kdykoli, bez ztráty** | přijdeš o funkci |

Poslední řádek je ten, o který jde v praxi nejčastěji. **Refaktoring je vratný, protože nic nemění.** Ve chvíli, kdy je slepený se změnou chování, vratné není ani jedno.

---

## Jak to vypadá

Úkol: přidat slevu pro velkoodběratele. Kód kolem toho potřebuje úklid. Demo to udělá dvakrát — jednou v jednom commitu, jednou ve dvou — a obě historie skončí u **bajt po bajtu totožného** souboru.

```
smíchané klobouky       5 commitů
oddělené klobouky       6 commitů
výsledný Cart.php       bajt po bajtu totožný
```

Stejná práce, stejný výsledek. Jediný rozdíl je v tom, kudy vedou hranice commitů. Za týden se ozve podpora, že malý košík počítá špatně, a `git bisect` hledá viníka:

```
historie                bisect ukázal na                            k přečtení
smíchané klobouky       Cart: sleva pro velkoodběratele + úklid     31 řádků
oddělené klobouky       Cart: sleva pro velkoodběratele             13 řádků
```

Chyba je v obou případech **tatáž jedna podmínka** — `<=` místo `>=`. V prvním případě ji hledáš mezi jedenatřiceti řádky, z nichž třicet jsou přejmenování. Ve druhém mezi třinácti, které všechny patří k té slevě.

Demo si u oddělené historie ověří i to, proč bisect úklidový commit přeskočil:

```
kontrola nad úklidovým commitem     prošla
```

**To je celý smysl toho oddělení.** Refaktoring nemění chování — a když se drží ve vlastním commitu, dá se to ověřit jedním spuštěním testů.

### A když se to má vrátit

```
po vrácení viníka       sleva pryč?     úklid zůstal?
smíchané klobouky       ano             NE — přišel jsi i o něj
oddělené klobouky       ano             ano
```

Tohle je ta škoda, která se nedá spočítat dopředu. **Vrácení jedné vady s sebou vezme i všechnu úklidovou práci** — a protože ji nikdo nechce dělat podruhé, obvykle se ten commit nevrátí a chyba se místo toho záplatuje.

---

## Proč se to pravidlo tak snadno poruší

Protože **v okamžiku psaní nic nestojí a nic nevynáší**. Rozdíl se projeví až později, a pokaždé u někoho jiného:

| Kdy | Kdo to pocítí | Co se stane |
| --- | ------------- | ----------- |
| Při psaní | ty | **nic** — jeden commit je pohodlnější |
| Při review | recenzent | hledá jednu řádku mezi třiceti |
| Za týden | ten, kdo hledá chybu | bisect ukáže na commit, který nic neříká |
| Za měsíc | ten, kdo to vrací | nedá se vrátit chyba bez úklidu |

Proto se to nedá řešit dobrou vůlí. **Musí to být návyk v okamžiku commitu**, ne rozhodnutí při plánování.

---

## Když už máš v pracovním adresáři obojí

Nejčastější situace: uklízel jsi a přitom napsal i tu funkci. Rozdělit to jde a nemusí se nic přepisovat.

**Rozdělit rozpracovanou změnu do dvou commitů:**

```bash
git add -p          # vybrat jen kusy, které patří k úklidu
git commit -m "úklid: …"
git add .
git commit -m "…"   # zbytek, tedy změna chování
```

**Odložit rozdělanou funkci, když chceš uklidit v zelené:**

```bash
git stash           # rozdělaná funkce jde stranou
# …refaktoring nad zelenou sadou…
git stash pop
```

Ten druhý postup doporučuje Fowler přímo — u [litter-pickupu](../LitterPickupRefactoring/) je jako krok „Get to Green". Refaktoring nad rozdělanou prací totiž porušuje první podmínku: **nemáš zelenou sadu, tak nemáš jak poznat, že jsi udělal chybu.**

> [!TIP]
> Pořadí commitů si vyber: úklid první je obvykle čitelnější, protože pak je změna chování malá a stojí sama. Přesně o to jde v [přípravném refaktoringu](../PreparatoryRefactoring/).

---

## Kde všude to platí

Dva klobouky nejsou jedno ze sedmi workflow — jsou **pravidlo, které platí ve všech**:

| Workflow | Jak se v něm klobouky střídají |
| -------- | ------------------------------ |
| [TDD refaktoring](../TddRefactoring/) | Krok ② je funkce, krok ③ refaktoring. Střídání po minutách. |
| [Litter-pickup](../LitterPickupRefactoring/) | Úklid a úkol, kvůli kterému jsi přišel — dva commity. |
| [Comprehension](../ComprehensionRefactoring/) | Když při pojmenovávání najdeš chybu, **neopravuj ji tady**. |
| [Přípravný](../PreparatoryRefactoring/) | Nejzřetelnější případ: příprava a pak ta snadná změna. |
| [Plánovaný](../PlannedRefactoring/) | Refaktoringová story nemění chování. Jinak není vratná. |
| [Refaktoring systému](../System/) | Totéž na měsíce — a tam je návratová cesta podmínkou, ne pohodlím. |

---

## Časté chyby

| Chyba | Proč vadí | Jak správně |
| ----- | --------- | ----------- |
| „Když už tam jsem, opravím i tohle" | Diff nabobtná a chyba se v něm ztratí | Poznamenat, opravit ve vlastním commitu |
| Refaktoring nad červenou sadou | Spadlý test může znamenat obojí a nevíš co | Nejdřív zelená — `git stash`, pak úklid |
| Úklid schovaný v commitu s funkcí | Recenzent hledá podstatnou řádku mezi třiceti | `git add -p` a dva commity |
| Přejmenování „při té příležitosti" v cizím souboru | Rozbiješ diffy a `git blame` někomu jinému | To je [litter-pickup](../LitterPickupRefactoring/) a má vlastní hranici |
| Dva commity, ale v jednom pull requestu bez vysvětlení | Recenzent netuší, že první nemění chování | Napsat to do popisu |
| „Rozdělím to až před pushnutím" | Rozdělit hotovou změnu zpětně je práce navíc | Commitovat průběžně, jak se klobouky střídají |

Druhý řádek je ten, který demo měří nepřímo. **V červené sadě se refaktorovat nedá** — ne proto, že by to bylo zakázané, ale protože ti chybí jediný signál, který refaktoring jistí.

---

## Demo

```bash
php Refactoring/TwoHats/demo/run.php
```

Demo postaví **dvě skutečné git historie** v dočasném adresáři — jednu se smíchanými klobouky, jednu s oddělenými — a ověří, že končí u totožného souboru. Pak v obou spustí `git bisect run` s testem, který kontroluje chování platné od prvního commitu:

```
historie                bisect ukázal na                            k přečtení
smíchané klobouky       Cart: sleva pro velkoodběratele + úklid     31 řádků
oddělené klobouky       Cart: sleva pro velkoodběratele             13 řádků
```

Pak spustí tentýž test nad úklidovým commitem (prošel — proto ho bisect přeskočil) a nakonec **oba viníky vrátí přes `git revert`** a přečte z výsledku, co v kódu zůstalo. U smíchané historie zmizí i úklid.

Dočasné repozitáře po sobě uklidí.

---

## Související

| Dokument | Vztah |
| -------- | ----- |
| [TDD refaktoring](../TddRefactoring/) | Kde se klobouky střídají nejrychleji — a odkud Fowler metaforu uvádí. |
| [Přípravný refaktoring](../PreparatoryRefactoring/) | Nejzřetelnější použití: připrav místo, pak přidej funkci. |
| [Litter-pickup](../LitterPickupRefactoring/) | Odkud pochází krok „Get to Green" a proč se uklízí až v zelené. |
| [Comprehension refactoring](../ComprehensionRefactoring/) | Kde se pravidlo poruší nejnenápadněji — nálezem chyby při čtení. |
| [Plánovaný refaktoring](../PlannedRefactoring/) | Totéž ve velkém: story, která mění chování, není vratná. |
| [Charakterizační testy](../CharacterizationTests/) | Zelená sada je podmínka celého pravidla. Tohle je způsob, jak ji získat. |
| [Code review: autor](../../Processes/CodeReview/Author/) | Proč se oddělené commity vyplatí i tomu, kdo je jenom čte. |
| [Refaktoring systému](../System/) | Kde už oddělení nestačí a je potřeba návratová cesta. |

---

## Původ

|             |                    |
| ----------- | ------------------ |
| **Autor**   | Kent Beck (metafora), Martin Fowler (šíření) |
| **Rok**     | 1999               |
| **Zdroj**   | Fowler: *Refactoring*; *Workflows of Refactoring* |
| **Náročnost** | ●●○○○            |

Metafora pochází od **Kenta Becka** a Fowler ji předal dál v knize *Refactoring* (1999). V infodecku z roku 2014 o ní píše, že *„goes back to the earliest days of refactoring"*, a používá ji jako vysvětlení, proč má cyklus [TDD](../TddRefactoring/) tři kroky, a ne dva.

Náročnost je dvojka a je celá v návyku. Mechanika je „udělej dva commity místo jednoho". Těžké jsou dvě věci:

- **Všimnout si, že jsi klobouk vyměnil.** Přechod z úklidu do psaní funkce nemá žádný signál — stane se to uprostřed metody.
- **Rozdělit to, když už je pozdě.** `git add -p` to umí, ale je to práce navíc a v tu chvíli už je změna hotová a láká to nechat být.

---

## Zdroje

- Martin Fowler: [*Workflows of Refactoring*](https://martinfowler.com/articles/workflowsOfRefactoring/), 8. ledna 2014 — [celý text na jedné stránce](https://martinfowler.com/articles/workflowsOfRefactoring/fallback.html)
- Martin Fowler: [*Refactoring: Improving the Design of Existing Code*](https://martinfowler.com/books/refactoring.html), 1999 a 2018
- Dokumentace: [`git bisect`](https://git-scm.com/docs/git-bisect), [`git add -p`](https://git-scm.com/docs/git-add)

---

<details>
<summary>Metadata techniky</summary>

```yaml
name: Dva klobouky
level: pravidlo
author: Kent Beck (metafora), Martin Fowler (šíření)
year: 1999
duration: trvale — je to návyk, ne úkon
reversible: netýká se — pravidlo, ne zásah
requires_tests: ano — zelená sada je podmínka
difficulty: 2
tags: [pravidlo, dva klobouky, commity, git bisect, Beck]
leads_to: []
related: [TddRefactoring, PreparatoryRefactoring, LitterPickupRefactoring, ComprehensionRefactoring, PlannedRefactoring]
status: done
```

</details>
