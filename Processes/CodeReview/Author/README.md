# Code review — pro autora

> [← zpět na Code review](../)

> **V jedné větě:** Kvalitu review určuješ hlavně ty — tím, jak velkou změnu pošleš a jak ji vysvětlíš.

---

## Proč je to hlavně na tobě

Recenzent má omezenou pozornost a ty rozhoduješ, na co ji vynaloží. Pull request o osmi stech řádcích bez popisu ji spotřebuje na orientaci; malá změna s vysvětlením ji nechá na to podstatné — **jestli je návrh dobrý**.

Platí u toho nepříjemná pravda: **čím větší změna, tím míň připomínek dostaneš.** Ne proto, že je lepší, ale protože se v ní nedá nic najít. Schválení velkého pull requestu bývá spíš vzdání se než souhlas.

---

## Velikost změny

Nejúčinnější věc, kterou pro dobré review můžeš udělat.

**Co se naměřilo.** Studie SmartBearu na týmu v Cisco Systems (2006) uvádí, že prohlížet by se nemělo víc než **200 až 400 řádků najednou** a že hustota nalezených chyb výrazně klesá při tempu nad zhruba **400–500 řádků za hodinu**. Zjištění je staré a týkalo se jednoho konkrétního týmu — **ber ho jako řád, ne jako předpis.**

Za pozornost stojí i to, jak velké změny reálně chodí tam, kde review funguje: Sadowski a kolektiv v roce 2018 uvádějí, že v Googlu má **medián změny kolem 24 řádků**.

**Praktické vodítko:**

| Velikost | Co čekat |
| -------- | -------- |
| do ~50 řádků | Přečte se pozorně, připomínky budou k věci |
| ~50–200 | Ještě dobré; popis začíná být důležitý |
| ~200–400 | Horní hranice; recenzent bude potřebovat čas v kuse |
| nad 400 | **Rozděl to**, nebo počítej s povrchním čtením |

Výjimky existují a jsou v pořádku: přejmenování napříč projektem, generovaný kód, přesun souborů. **Napiš to do popisu** a odděl je od změn, které se opravdu mají číst.

### Jak rozdělit, když to nejde

Nejčastější námitka zní, že změna se rozdělit nedá. Skoro vždycky dá — jen ne podle souborů:

- **Refaktoring zvlášť od chování.** Nejsilnější pravidlo. Přesuny a přejmenování v jednom pull requestu, změna chování v druhém; jinak se v šumu ztratí ta jedna řádka, na které záleží.
- **Nejdřív rozhraní, pak implementace.** Rozhraní se dá recenzovat samostatně a je to ta část, kde se návrhová chyba dá ještě levně opravit.
- **Po vrstvách.** Databázová migrace, doména, aplikační vrstva, API — každá zvlášť.
- **Za [feature flagem](../../../GitWorkflows/Glossary.md#feature-flag).** Nedokončená funkce může jít do hlavní větve po částech, když ji uživatel nevidí.
- **[Branch by abstraction](../../../GitWorkflows/TrunkBasedDevelopment/#běžný-den)** u velkých přestaveb — čtyři bezpečná sloučení místo jednoho velkého.

---

## Self-review

**Přečti si vlastní diff dřív, než ho pošleš komukoli jinému.** Zabere to pár minut a ušetří celé kolo připomínek.

Čti ho v rozhraní GitHubu nebo GitLabu, ne v editoru — uvidíš přesně to, co uvidí recenzent, a to je jiný pohled než ten, na který sis zvykl při psaní.

Co se takhle najde skoro pokaždé:

- zapomenutý `dump()`, `console.log`, zakomentovaný kód
- soubor, který tam vůbec neměl být (lokální konfigurace, `.idea`)
- změna formátování v souboru, kterého se úkol netýká
- pojmenování, které dávalo smysl na začátku a teď už ne
- chybějící test na případ, který jsi ručně vyzkoušel a zapomněl

**Když si v diffu sám nejsi něčím jistý, napiš k tomu komentář dřív, než se zeptá recenzent.** Ušetříš tím celé jedno kolo.

---

## Popis pull requestu

Popis není formalita. Je to jediné místo, kde můžeš říct **proč** — z kódu se to nedozví nikdo.

Co v něm má být:

| Část | Proč |
| ---- | ---- |
| **Co se mění a proč** | Dvě věty. Bez nich musí recenzent zpětně odvozovat záměr. |
| **Odkaz na zadání** | Kontext, který se do popisu nevejde |
| **Jak to vyzkoušet** | Když to nejde poznat z testů |
| **Na co se soustředit** | „Zajímá mě hlavně návrh `PriceCalculatoru`“ — nasměruje pozornost |
| **Co jsem vědomě neudělal** | Předejde připomínkám na věci, o kterých už víš |

Špatný popis: *„Oprava fakturace“.*

Dobrý popis:

> DPH se u položek se sníženou sazbou počítalo z ceny včetně daně, takže faktura vycházela o pár korun výš.
>
> Výpočet jsem přesunul do `VatCalculator`, aby šel otestovat samostatně — dřív byl uvnitř `InvoiceRenderer` a nešlo se k němu dostat bez vykreslení celé faktury.
>
> **Zajímá mě hlavně** rozdělení odpovědností mezi kalkulátor a renderer.
> **Vědomě neřeším** zaokrouhlování u záloh — to je na samostatný úkol.

Poslední dva řádky ušetří obvykle nejvíc času.

---

## Než požádáš o review

- [ ] CI je zelená — **recenzent nemá být první, kdo zjistí, že to nejede**
- [ ] Prošel jsem si vlastní diff v rozhraní
- [ ] V diffu není nic, co s úkolem nesouvisí
- [ ] Popis říká **co a proč**, ne jen co
- [ ] Změna je tak malá, jak to šlo udělat
- [ ] Testy pokrývají to, co jsem změnil
- [ ] Vím, koho chci za recenzenta a proč zrovna jeho

Poslední bod se přehlíží. **Recenzenta vybírej podle toho, kdo té části rozumí — nebo kdo by jí rozumět měl.** To druhé je legitimní důvod: review je nejlevnější způsob, jak znalost rozšířit.

---

## Jak reagovat na připomínky

**Odpověz na každou.** I krátce. Nezodpovězený komentář vypadá jako přehlédnutý a recenzent neví, jestli má čekat.

**Rozliš, co je závazné.** Když je komentář [označený jako nezávazný](../Comments/), můžeš ho odmítnout — napiš proč a pokračuj. K tomu ta značka slouží.

**Nesouhlas je v pořádku.** Recenzent nemá vždycky pravdu a ty svůj kód znáš líp. Napiš důvod:

> Zvažoval jsem to, ale `PriceCalculator` by pak potřeboval znát měnu objednávky a tím by se navázal na `Order`. Nechal jsem to takhle kvůli [nízké provázanosti](../../../SoftwareDesign/Principles/CohesionAndCoupling.md).

**Když nerozumíš, zeptej se.** „Nechápu, jak to myslíš“ je lepší než hádat a udělat něco jiného.

**Neber to osobně a nepiš to osobně.** Připomínka je o kódu. Když má někdo tendenci ji formulovat jako soud o tobě, řeš to s ním mimo pull request — ale nezačínej tím.

> [!NOTE]
> **Nesnaž se komentářům předejít tím, že pošleš míň.** Autor, který se bojí připomínek, píše menší a opatrnější změny — ale taky přestane refaktorovat a uklízet, protože „to by přidalo řádky navíc“. Tím proces ztrácí to nejcennější, co dává.

---

## Časté chyby

| Chyba | Proč vadí | Jak správně |
| ----- | --------- | ----------- |
| Pull request na celý týden práce | Nikdo ho nepřečte pozorně; schválení je fikce | Rozdělit — refaktoring zvlášť od chování |
| Refaktoring a změna chování dohromady | V šumu přesunů se ztratí ta jedna řádka, na které záleží | Dva pull requesty |
| Popis „Oprava fakturace“ | Recenzent musí zpětně odvozovat záměr | Napsat **proč** |
| Poslání s červenou CI | Recenzent dělá práci, kterou měl udělat stroj | Zelená CI je vstupenka |
| Žádný self-review | Recenzent najde `dump()` a zapomenutý soubor místo návrhu | Přečíst si diff v rozhraní |
| Přeformátování celého souboru | Skutečná změna se ztratí mezi tisícem řádek | Formátování zvlášť, nebo automaticky v CI |
| Ticho po připomínce | Recenzent neví, jestli čekat | Odpovědět na každou, i krátce |
| „Opraveno“ bez vysvětlení u složité věci | Recenzent musí znovu hledat, co se změnilo | Napsat jak |
| Force push do větve během review | Recenzent přijde o rozečtené místo a musí začít znovu | Přidávat commity; srovnat historii až před sloučením |

Poslední řádek je drobnost s velkým dopadem. **Když během review přepíšeš historii větve, rozbiješ recenzentovi rozečtené místo** — komentáře se odkazují na commity, které přestaly existovat. [Rebase](../../../GitWorkflows/Glossary.md#rebase) si nech až na chvíli před sloučením.

---

## Související

| Dokument | Vztah |
| -------- | ----- |
| [Pro recenzenta](../Reviewer/) | Druhá strana téhož procesu — vyplatí se přečíst obojí |
| [Komentáře](../Comments/) | Jak poznat závazné od nezávazného |
| [Trunk-Based Development](../../../GitWorkflows/TrunkBasedDevelopment/) | Model, který na malých změnách přímo stojí; má i techniky, jak velkou práci rozdělit |
| [First Class Collection](../../../SoftwareDesign/ObjectCalisthenics/FirstClassCollection/) | Příklad změny, která se dá udělat samostatně a nezávisle |

---

## Zdroje

- [Google: The CL Author's Guide](https://google.github.io/eng-practices/review/developer/)
- [SmartBear: Best Practices for Peer Code Review](https://smartbear.com/learn/code-review/best-practices-for-peer-code-review/) — shrnutí studie z Cisco Systems, 2006
- Sadowski et al.: [*Modern Code Review: A Case Study at Google*](https://sback.it/publications/icse2018seip.pdf), ICSE-SEIP 2018
