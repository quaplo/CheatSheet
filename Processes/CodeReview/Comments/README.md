# Code review — komentáře

> [← zpět na Code review](../)

> **V jedné větě:** Z komentáře musí být poznat **jestli blokuje** a **proč** — bez toho autor buď poslechne bez pochopení, nebo se začne bránit.

---

## Dvě věci, které v komentáři nesmí chybět

Většina problémů v review nevzniká z toho, co se řekne, ale z toho, co se neřekne.

**1. Jestli to blokuje.** Autor nemá jak poznat, jestli je „tady by se hodilo jiné jméno“ podmínka pro schválení, nebo poznámka. Když to nevíš jistě, uděláš to nejhorší z obou možností: buď přepíšeš, co přepisovat nemusíš, nebo neuděláš, co udělat máš.

**2. Proč.** Připomínka bez důvodu se dá jen poslechnout, ne pochopit. Autor se z ní nic nenaučí, příště udělá totéž — a review, které mělo předávat znalosti, se změnilo v seznam příkazů.

```
❌  Tohle by mělo být v service.

✅  suggestion (non-blocking): Tohle pravidlo by patřilo spíš do domény —
    kontroler by pak nemusel vědět, kdy se smí objednávka zrušit.
    Viz Tell, Don't Ask.
```

---

## Značkování

Nejjednodušší způsob, jak vyřešit první bod, je **napsat ho na začátek**. Konvence [Conventional Comments](https://conventionalcomments.org/) na to má formát:

```
<label> [dekorace]: <věc>

[odůvodnění]
```

Nejpoužívanější značky:

| Značka | K čemu | Blokuje |
| ------ | ------ | ------- |
| `issue` | Skutečný problém — chyba, riziko | **ano** |
| `suggestion` | Návrh na zlepšení s odůvodněním | podle dekorace |
| `question` | Nerozumím, vysvětli | podle odpovědi |
| `nitpick` | Drobnost, preference | **ne** |
| `thought` | Nápad, který mě napadl při čtení | **ne** |
| `praise` | Něco se povedlo | **ne** |
| `todo` | Malá nutná úprava | ano, ale triviálně |

Dekorace `(blocking)` a `(non-blocking)` to řeknou natvrdo, `(if-minor)` znamená „udělej to, jen když je to na minutu“.

**Nemusíš zavádět celou konvenci.** Google používá jen prefix `Nit:` pro nezávazné připomínky a to samo o sobě vyřeší většinu nedorozumění. **Zvol jednu variantu a drž ji celý tým** — půlka lidí značkujících jinak než druhá je horší než nic.

### `praise` není zdvořilostní fráze

Značka, kterou lidé nejčastěji přeskočí, a přitom je nejlevnější. Když někdo vyřešil něco elegantně, **napiš to** — jednou větou:

```
praise: Rozdělení na VatCalculator a InvoiceRenderer je přesně ono,
tohle šlo konečně otestovat bez vykreslování.
```

Není to o náladě v týmu. Je to **jediná zpětná vazba, ze které se autor dozví, co dělat víc.** Když se komentuje výhradně to, co je špatně, dostane obraz vlastní práce, který neodpovídá skutečnosti — a začne se řídit tím, čemu se vyhnout, místo toho, o co usilovat.

---

## Jak formulovat

**Ptej se, když se ptáš.** Otázka, která je ve skutečnosti příkaz, je horší než příkaz — autor musí hádat, co se po něm chce.

```
❌  Nešlo by to udělat jinak?
✅  question: Proč se tady stav kontroluje podruhé?
✅  suggestion (blocking): Přesuň tu kontrolu do konstruktoru —
    takhle může objekt existovat v neplatném stavu.
```

**Mluv o kódu, ne o autorovi.** Rozdíl je v tom, s čím se dá pracovat:

```
❌  Nepochopil jsi, jak funguje transakce.
✅  issue: Tady se `flush()` volá uvnitř cyklu, takže každá položka
    je vlastní transakce — při chybě v půlce zůstane půlka uložená.
```

**Nabídni cestu, když blokuješ.** „Takhle ne“ nikam nevede. Když nevíš jak, řekni aspoň to:

```
suggestion (blocking): Tohle se bude špatně měnit, až přibude druhý
dodavatel. Nemám hotové řešení — pojďme to probrat, než to přepíšeš.
```

**Piš konkrétně a s odkazem.** Odkaz na [princip](../../../SoftwareDesign/Principles/) nebo [vzor](../../../SoftwareDesign/) je lepší argument než osobní preference a autor si k němu může dojít sám.

**Nezávazné piš taky.** Sebrat si připomínky proto, že „nechci ho zdržovat“, znamená zahodit polovinu užitku. Jen je označ.

---

## Jak nesouhlasit

Neshoda je normální součást procesu, ne selhání. Špatně je jen ta, kterou nikdo neuzavře.

**Rozliš, o co jde.** Spor o fakt („tenhle kód spadne na prázdném poli“) se řeší ověřením. Spor o návrh („patří to do domény, nebo do služby“) se řeší argumentem. Spor o preferenci se neřeší — jeden ustoupí.

**Po druhé výměně přestaň psát.** Když si dva komentáře nestačily, třetí to nevyřeší. Zavolej, dojdi k němu, nasdílej obrazovku. **Písemná neshoda eskaluje sama od sebe**, protože chybí tón a obě strany si domýšlejí to horší.

**Když se nedohodnete, [eskalujte](../#když-se-autor-a-recenzent-neshodnou).** Přiberte třetího. To, co se stát nesmí, je pull request visící týden, protože se dva nedomluvili.

**Připusť, že můžeš nemít pravdu.** Autor kód zná líp, strávil v něm dny a nejspíš už zvažoval to, co tě napadlo. Otázka „nezvažoval jsi X?“ obvykle vede k lepšímu výsledku než tvrzení „má to být X“.

---

## Časté chyby

| Chyba | Proč vadí | Jak správně |
| ----- | --------- | ----------- |
| Není poznat, co blokuje | Autor přepíše, co nemusel, nebo neudělá, co měl | Značkovat |
| Připomínka bez důvodu | Autor poslechne bez pochopení a příště udělá totéž | Napsat **proč** |
| Otázka, která je ve skutečnosti příkaz | Autor hádá, co se po něm chce | Ptej se, nebo řekni |
| Komentář o autorovi, ne o kódu | Vyvolá obranu; s „nepochopil jsi“ se nedá pracovat | Popiš, co kód dělá |
| Jen výtky, nikdy uznání | Autor nemá zpětnou vazbu, co dělat víc | `praise:` když se něco povedlo |
| Dvacet drobností a nic k návrhu | Autor tráví den na kosmetice; podstatné zůstalo | Nejdřív návrh, drobnosti nezávazně |
| Třetí kolo písemné hádky | Eskaluje samo od sebe, chybí tón | Po druhé výměně mluvit |
| Sarkasmus a emoji místo argumentu | Nedá se odpovědět; funguje jako shazování | Napsat věcně, co vadí |
| Nezávazné se nepíše vůbec | Zahodí se půlka užitku review | Napsat a označit |
| „Opraveno“ bez reakce na otázku | Recenzent neví, jestli byl pochopen | Odpovědět, i krátce |

---

## Související

| Dokument | Vztah |
| -------- | ----- |
| [Pro autora](../Author/) | Jak na komentáře reagovat |
| [Pro recenzenta](../Reviewer/) | Co komentovat a co ne |
| [Code review](../) | Kdy schválit a jak řešit neshodu |
| [Principy návrhu](../../../SoftwareDesign/Principles/) | Odkaz místo osobní preference |

---

## Zdroje

- [Conventional Comments](https://conventionalcomments.org/) — formát a značky
- [Google: How to write code review comments](https://google.github.io/eng-practices/review/reviewer/comments.html)
- [Google: The Standard of Code Review](https://google.github.io/eng-practices/review/reviewer/standard.html) — prefix `Nit:`
