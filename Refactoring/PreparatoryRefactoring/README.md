# Přípravný refaktoring

> [← zpět na Refaktoring](../)

> **V jedné větě:** Než přidáš funkci do kódu, kde se přidává těžko, uprav ten kód tak, aby se přidávala snadno — a udělej to jako samostatný krok.

Kent Beck to shrnul v jedné větě, která je dnes nejcitovanějším pravidlem o refaktoringu vůbec:

> „for each desired change, **make the change easy (warning: this may be hard), then make the easy change**"
>
> — Kent Beck, 25. září 2012

Ta závorka je na tom to nejdůležitější. **Připravit místo bývá těžší než ta změna sama** — a právě proto se to tak často přeskočí.

---

## Kdy po tom sáhnout

Tenhle postup se nespouští z rozhodnutí „teď budeme refaktorovat". Spustí ho **konkrétní úkol**, který se ukáže jako těžší, než by měl být.

**Poznáš to podle:**

- odhad zněl „na hodinu" a jsi v tom třetí den
- musíš **zkopírovat kus kódu**, protože jinak to nejde
- přidáváš `if` do metody, která už jich má osm
- říkáš si „tohle už tu jednou je, jen o kus vedle"
- při čtení zadání víš přesně, co se má stát, ale **nevíš kam to napsat**

---

## Jak to vypadá

Zadání: přidat nový způsob dopravy do metody, která má tři `if`y.

### Cesta A: přidat to rovnou

```php
if ($carrier === 'balikovna') {
    if ($orderValueInCents >= 250000) {
        return 0;                       // ← totéž pravidlo, podruhé
    }

    return $weightInGrams > 10000 ? 12900 : 6900;
}
```

```
řádků v Shipping.php          32 → 40  (+8)
```

**Funguje to a diff je malý.** Jenže pravidlo o dopravě zdarma je teď v kódu dvakrát a metoda je zase o kus delší. Příští dopravce bude o něco horší.

### Cesta B, krok 1: připravit místo

Rozdělit dopravce do tříd a vytáhnout sdílené pravidlo — **a nic nepřidat**:

```
shodné chování                9 z 9
nová funkce přidána?          ne — Balíkovna tu není
zkouška Balíkovny             Neznámý dopravce: balikovna
```

**Tenhle krok nepřidal nic.** Změnil jen strukturu, a testy to potvrzují. Tohle je ta „hard" část z Beckovy věty.

### Cesta B, krok 2: přidat funkci

```
nových souborů                1
změn v existujících           0
```

*„…then make the easy change."*

---

## Proč to dělat ve dvou krocích

Nejde jen o pořadí — jde o **dva oddělené commity**. Platí tu [pravidlo dvou klobouků](../TwoHats/): buď přidáváš funkci, nebo refaktoruješ, nikdy obojí zároveň.

```
commit                        mění chování?     co v něm recenzent hledá
1. příprava (refaktoring)     ne                jestli se opravdu nic nezměnilo
2. Balíkovna (funkce)         ano               jestli je nové pravidlo správně
```

Praktický důvod je v pravém sloupci. **Recenzent hledá v každém commitu něco jiného** — a v jednom společném by se ta jedna podstatná řádka ztratila mezi přesuny. Souvisí to s pravidlem z [code review](../../Processes/CodeReview/Author/): refaktoring a změna chování patří do oddělených pull requestů.

Druhý důvod je bezpečnost. **Refaktoring se dá kdykoli zahodit**, protože nic nemění. Když je smíchaný se změnou chování, zahodit se nedá nic.

---

## Kdy se to nevyplatí

Demo si to spočítá samo:

```
                              cesta A                 cesta B
commitů                       1                       2
souborů                       1                       6
řádků celkem po změně         40                      146
další dopravce si vyžádá      zásah do metody         nový soubor
```

**Cesta B má trojnásobek řádků.** Vyplatí se jen tehdy, když ten další dopravce přijde.

> [!IMPORTANT]
> **Kdyby žádný další nepřišel, byla cesta A správná.** Přípravný refaktoring se dělá kvůli změně, kterou děláš **teď** — ne kvůli té, kterou tušíš. Připravovat místo pro funkci, která možná nikdy nebude, je [YAGNI](../../SoftwareDesign/Principles/Simplicity.md#yagni--you-arent-gonna-need-it) v čisté podobě.

Rozdíl je jemný, ale zásadní:

| | Přípravný refaktoring | Spekulativní zobecnění |
| --- | --- | --- |
| Spouštěč | **úkol, který právě děláš** | tušení, že se to bude hodit |
| Ověření | ta změna je po něm snazší — **hned** | žádné, uvidí se za rok |
| Když se mýlíš | přišel jsi o jedno odpoledne | nesourodá abstrakce navždy |

---

## Ostatní situace, kdy se refaktoruje

Fowler v článku *Workflows of Refactoring* (2014) popisuje **sedm** způsobů, jak se refaktoring do práce dostává. Přípravný je jeden z nich a stojí za to znát i ostatní — mají různou cenu a různou míru schválení:

| Workflow | Kdy | Kdo o tom rozhoduje |
| -------- | --- | ------------------- |
| [**Two Hats**](../TwoHats/) | Základní pravidlo pro všechny ostatní: buď funkce, nebo refaktoring | vývojář, průběžně |
| [**TDD Refactoring**](../TddRefactoring/) | Třetí krok cyklu red–green–refactor | vývojář, minuty |
| [**Litter-Pickup**](../LitterPickupRefactoring/) | „Jsem tu stejně, tak to cestou uklidím" | vývojář, minuty |
| [**Comprehension**](../ComprehensionRefactoring/) | Refaktoruješ, abys kódu porozuměl — a znalost zůstane v kódu | vývojář, hodiny |
| **Preparatory** | Chystáš změnu a připravuješ na ni místo | vývojář, hodiny až den |
| [**Planned**](../PlannedRefactoring/) | Vyhrazený čas na úklid, který se nestihl průběžně | tým |
| **Long Term** | Velká změna po částech, měsíce | tým, někdy i byznys |

Šest z nich má v tomhle katalogu vlastní pokračování:

- [**TDD refaktoring**](../TddRefactoring/) je ten nejlevnější — síť už máš z prvního kroku cyklu.
- [**Comprehension refactoring**](../ComprehensionRefactoring/) je myšlenkově blízké [charakterizačním testům](../CharacterizationTests/) — obojí je způsob, jak zjistit, co kód dělá, a zapsat to.
- [**Litter-pickup refactoring**](../LitterPickupRefactoring/) je s comprehension dvojice, které Fowler říká **oportunistické refaktoringy** — u obou narazíš na problém, když děláš něco jiného.
- [**Planned Refactoring**](../PlannedRefactoring/) je první z nich, o kterém se rozhoduje tým — a podle Fowlera zároveň signál, že ta čtyři předchozí vázla.
- **Long Term Refactoring** je to, čemu se v [`System/`](../System/) věnují všechny čtyři techniky.
- [**Two Hats**](../TwoHats/) je pravidlo, které stojí za oddělenými commity ve všech [kódových refaktoringech](../Code/).

Fowler u toho zdůrazňuje jednu věc, která zní politicky, ale je praktická: **na první čtyři se nikoho neptáš.** Jsou to součást práce, ne samostatná položka v plánu. Teprve *Planned* a *Long Term* jsou rozhodnutí, o kterém tým ví.

---

## Časté chyby

| Chyba | Proč vadí | Jak správně |
| ----- | --------- | ----------- |
| Příprava a funkce v jednom commitu | Podstatná změna se ztratí mezi přesuny | Dva commity, dva klobouky |
| Příprava se rozroste na přepis modulu | Úkol na hodinu je z toho úkol na týden | Připravit **jen** to, co ta změna potřebuje |
| Připravuje se na změnu, která možná přijde | To je spekulativní zobecnění, ne příprava | Spouštěčem je úkol, který děláš teď |
| Refaktoruje se bez testů | Není jak poznat, že se chování nezměnilo | [Charakterizační testy](../CharacterizationTests/) |
| „Nejdřív to zprovozním, pak uklidím" | Druhá půlka nepřijde; funkce je hotová a úkol zavřený | Příprava jde **první** |
| Refaktoring se schovává do odhadu | Nikdo neví, kolik to stálo, a příště se to škrtne | Přiznat, že příprava byla součást úkolu |

Předposlední řádek je nejčastější a nejzrádnější. **Po dokončení funkce už motivace uklízet zmizí** — úkol je splněný a další čeká. Proto Beckova věta zní *„make the change easy, **then** make the easy change"*, a ne naopak.

---

## Demo

```bash
php Refactoring/PreparatoryRefactoring/demo/run.php
```

Zadání „přidat Balíkovnu" udělané dvěma způsoby. Cesta A přidá `if` do stávající metody — funguje to a diff má osm řádků, ale pravidlo o dopravě zdarma je teď v kódu dvakrát. Cesta B nejdřív **připraví místo a ověří, že se chování nezměnilo** (9 z 9 případů, Balíkovna zatím neexistuje), a teprve pak přidá funkci jako jediný nový soubor.

Závěr obě cesty spočítá vedle sebe — a přiznává, že **kdyby žádný další dopravce nepřišel, byla správná cesta A.**

---

## Související

| Dokument | Vztah |
| -------- | ----- |
| [Dva klobouky](../TwoHats/) | Pravidlo, na kterém tenhle postup stojí — a proč jsou to dva commity. |
| [TDD refaktoring](../TddRefactoring/) | Třetí krok cyklu — a druhé místo, kde platí dva klobouky. |
| [Charakterizační testy](../CharacterizationTests/) | Bez sítě se příprava dělat nedá. |
| [Comprehension refactoring](../ComprehensionRefactoring/) | Druhé z Fowlerových workflow, o kterých se nikoho neptáš — a často to, co přípravě předchází. |
| [Litter-pickup refactoring](../LitterPickupRefactoring/) | Nejlevnější z nich; přejde v přípravný ve chvíli, kdy úklid usnadní tvou vlastní změnu. |
| [Plánovaný refaktoring](../PlannedRefactoring/) | Co přijde, když se příprava ani úklid dělat nestihly. |
| [Refaktoring kódu](../Code/) | Konkrétní techniky, kterými se to místo připravuje. |
| [Refaktoring systému](../System/) | Fowlerův *Long Term Refactoring* — totéž na měsíce místo hodin. |
| [Code review](../../Processes/CodeReview/Author/) | Odkud pochází pravidlo o oddělených pull requestech. |
| [YAGNI](../../SoftwareDesign/Principles/Simplicity.md#yagni--you-arent-gonna-need-it) | Hranice, za kterou je z přípravy spekulace. |
| [Pravidlo tří](../../SoftwareDesign/Principles/Simplicity.md#pravidlo-tří) | Kdy je opakování ještě náhoda a kdy už důvod k přípravě. |
| [Extreme Programming](../../Processes/ExtremeProgramming/) | Odkud pochází představa refaktoringu jako průběžné činnosti, ne fáze. |

---

## Původ

|             |                    |
| ----------- | ------------------ |
| **Autor**   | Kent Beck (myšlenka), Martin Fowler (pojmenování) |
| **Rok**     | 2012 / 2014        |
| **Zdroj**   | Beckův tweet; Fowler: *Workflows of Refactoring* |
| **Náročnost** | ●●○○○            |

Beckova věta vznikla jako **tweet z 25. září 2012** a je pravděpodobně nejcitovanějším pravidlem o refaktoringu vůbec — mimo jiné proto, že se vejde do jednoho řádku a nedá se špatně pochopit. Beck ji později rozvinul v knize *Tidy First?*

Fowler ji zařadil mezi sedm workflow refaktoringu v článku z **8. ledna 2014** a dal jí jméno. Zajímavé je, kam ji v tom seznamu umístil: mezi věci, o kterých **se nerozhoduje** — je to součást práce na úkolu, ne samostatná položka v plánu.

Náročnost je dvojka a je celá v disciplíně. Mechanika je triviální — udělej to ve dvou krocích. Těžké jsou dvě věci:

- **Odolat pokušení udělat to naráz.** Když už je kód rozebraný, je lákavé tam tu funkci rovnou přidat.
- **Nepřipravovat víc, než ta změna potřebuje.** Příprava má hranici a tou je úkol, který děláš teď. Za ní začíná [spekulativní zobecnění](#kdy-se-to-nevyplatí).

---

## Zdroje

- Kent Beck: [tweet z 25. 9. 2012](https://x.com/KentBeck/status/250733358307500032)
- Martin Fowler: [*Workflows of Refactoring*](https://martinfowler.com/articles/workflowsOfRefactoring/), 2014
- Martin Fowler: [*An example of preparatory refactoring*](https://martinfowler.com/articles/preparatory-refactoring-example.html)
- Kent Beck: *Tidy First?*, O'Reilly, 2023

---

<details>
<summary>Metadata techniky</summary>

```yaml
name: Přípravný refaktoring
level: příprava
author: Kent Beck (myšlenka), Martin Fowler (pojmenování)
year: 2012
duration: hodiny až den
reversible: ano — refaktoring nic nemění
requires_tests: ano
difficulty: 2
tags: [workflow, dva klobouky, kdy refaktorovat, Beck]
leads_to: []
related: [CharacterizationTests, CodeReview, YAGNI]
status: done
```

</details>
