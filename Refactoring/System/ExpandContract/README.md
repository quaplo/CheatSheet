# Expand–Contract


> [← zpět na Refaktoring systému](../)

> **V jedné větě:** Nejdřív přidej nové vedle starého, pak převeď všechny na nové — a teprve nakonec staré odstraň.

> [!NOTE]
> Technika má dvě jména. Danilo Sato ji popsal jako **Parallel Change**, ale hned dodává, že je *„also known as **expand and contract**"*. Používají se obě; v souvislosti s databázemi převládá to druhé.

---

## Kdy po tom sáhnout

Potřebuješ změnit něco, na čem závisí ostatní — a nemůžeš to změnit všude naráz.

Sato pojmenovává, proč je to těžké:

> „making a change to an interface that impacts all its consumers **requires two thinking modes**" naráz — a to je obzvlášť těžké *„if the change is on a PublishedInterface with multiple or external clients."*

**Poznáš to podle:**

- měníš **databázové schéma** a aplikace při nasazení nesmí spadnout
- měníš **API**, které volá někdo, koho neřídíš
- nasazuje se **postupně** (rolling deploy) a chvíli běží stará i nová verze vedle sebe
- „přejmenujeme ten sloupec" znamená **zastavit provoz**
- klienti se aktualizují **vlastním tempem** — mobilní aplikace, integrace partnerů

Demo ukazuje, proč to bez fází nejde:

```
fáze                      stará verze     nová verze
0. výchozí stav           funguje         SPADNE
```

**Skok rovnou na novou verzi rozbije produkci** ve chvíli, kdy ještě běží stará instance.

---

## Předtím

```sql
CREATE TABLE orders (
    number  TEXT PRIMARY KEY,
    shipped INTEGER NOT NULL DEFAULT 0
);
```

Chceš `shipped` (ano/ne) nahradit sloupcem `status`, který unese víc stavů — třeba zrušenou objednávku. Přejmenovat ho jedním příkazem znamená, že mezi nasazením migrace a nasazením kódu je **okno, ve kterém aplikace nefunguje**.

---

## Mechanika

Tři fáze podle Sata, u databáze se prostřední dělí na dvě.

### 1. Expand — přidej nové vedle starého

> „augment the interface to support **both the old and the new versions**"

```sql
ALTER TABLE orders ADD COLUMN status TEXT NOT NULL DEFAULT '';
```

Nic se nemaže, nic se nepřejmenovává. Nový sloupec je prázdný a nikoho nezajímá.

```
sloupce             number, shipped, status
stará aplikace      funguje
nová aplikace       funguje
```

**Po tomhle kroku platí:** obě verze aplikace fungují a změna se dá vydat.

#### Dvojí zápis

Jakmile nová verze začne zapisovat, musí psát **do obou míst** — stará verze totiž pořád běží a musí vidět správná data:

```php
// Fáze expand a migrate
$db->prepare('UPDATE orders SET status = ?, shipped = 1 WHERE number = ?')
```

```
nová zapsala status     expedovaná
stará čte shipped       expedovaná   ← stará verze to taky vidí
```

**Bez dvojího zápisu by objednávky založené novou verzí byly pro starou neviditelné.** To je nejčastější chyba celé techniky.

### 2. Backfill — doplň historická data

Nový sloupec je prázdný u všeho, co vzniklo dřív:

```
vyplněný status     0 z 250
```

Doplňuje se **po dávkách**, ne jedním příkazem:

```php
public function backfill(int $batchSize = 100): int
{
    while (true) {
        // vyber dávku, převeď, opakuj
        // v produkci sem patří krátká pauza mezi dávkami
    }
}
```

```
doplněno řádků          250
velikost dávky          100
vyplněný status         251 z 251
```

Jeden velký `UPDATE` by u milionu řádků **zamkl tabulku na minuty** a provoz by stál. Dávka bývá tisíc až deset tisíc řádků a mezi nimi krátká pauza, aby se databáze nezahltila.

**Po tomhle kroku platí:** obě reprezentace obsahují totéž.

### 3. Migrate — převeď všechny na nové

> „update all clients using the old version to the new version. **This can be done incrementally.**"

Čtení se přepne na nový sloupec, zápis pořád jde do obou. U API se v téhle fázi převádějí klienti — každý svým tempem.

```
nová čte status         expedovaná
stará aplikace          funguje
nová aplikace           funguje
```

**Po tomhle kroku platí:** nové se používá, staré už jen existuje.

> [!NOTE]
> **Kde se dá zastavit:** ve fázích 1–3 kdykoli a na libovolně dlouho. Dokud běží dvojí zápis, obě verze si rozumí a návrat zpět nic nestojí. Sato to zdůrazňuje jako hlavní přínos: *„it allows your code to be released in any of these three phases."*

### 4. Contract — odstraň staré

```sql
ALTER TABLE orders DROP COLUMN shipped;
```

```
sloupce             number, status
stará aplikace      SPADNE   ← teprve teď
nová aplikace       funguje
```

**Až tenhle krok starou verzi vyřadí** — a smí přijít teprve tehdy, když už nikde neběží.

**Po tomhle kroku platí:** hotovo, schéma je čisté.

---

## Průběh a návratová cesta

| | |
| --- | --- |
| **Jak dlouho to trvá** | Dny u schématu; **měsíce až roky** u veřejného API s externími klienty |
| **Co vidí uživatel** | Nic — v tom je celý smysl |
| **Jak se vrátit** | Ve fázích 1–3 zdarma; **po fázi 4 už ne** |
| **Jak dlouho žije mezistav** | Od fáze 1 do fáze 4; u API tak dlouho, dokud se nepřevedou klienti |

Návratová cesta je asymetrická a je potřeba to vědět dopředu: **fáze 1 až 3 jsou vratné, fáze 4 není.** Odstranění starého sloupce nebo staré metody je jednosměrné — data jsou pryč a stará verze aplikace už se nemá kam vrátit.

Proto se contract dělá **až s odstupem**, ne hned po přepnutí čtení.

---

## Jak ověřit, že to funguje

Měřítko je jediné a demo ho počítá: **fungují v téhle fázi obě verze aplikace?**

```
fáze                      stará verze     nová verze
0. výchozí stav           funguje         SPADNE
1. expand                 funguje         funguje
2. backfill               funguje         funguje
3. migrate                funguje         funguje
4. contract               SPADNE          funguje
```

Prakticky:

- **Pusť testy staré verze proti novému schématu.** Když projdou, fáze je bezpečná.
- **Ověř dvojí zápis** — po zápisu novou verzí musí být data čitelná i tou starou.
- **Zkontroluj, že backfill nic nevynechal.** Počet řádků s vyplněnou novou hodnotou se musí rovnat celku.
- **Před fází contract si ověř, že staré už nikdo nepoužívá.** U databáze grep v kódu, u API metriky volání.

Poslední bod je ten, který se odbývá. **Metrika na starou metodu je jediný spolehlivý způsob**, jak zjistit, že ji nikdo nevolá — grep najde jen to, co máš v repozitáři.

---

## Co to stojí

| Cena | Kdy se vyplatí |
| ---- | -------------- |
| **Dvojí zápis** — kód, který píše na dvě místa | Když se nasazuje postupně a nesmí být výpadek |
| **Dvě reprezentace téhož** po dobu migrace | Když se klienti nedají aktualizovat naráz |
| **Backfill** u velkých tabulek trvá hodiny | Když je alternativou zastavení provozu |
| **Čtyři nasazení místo jednoho** | Když je vratnost každého kroku cennější než rychlost |
| **Riziko, že se contract neudělá** | Když je kdo dotáhne |

Poslední řádek je hlavní nebezpečí a Sato ho pojmenovává nezvykle ostře:

> „**If the contract phase is not executed you might end up in a worse state than you started**, therefore you need discipline to finish the transition successfully."

**Horší stav než na začátku** — dva sloupce místo jednoho, dvojí zápis navždy a nikdo neví, který je ten pravý. Nedokončená migrace je tu horší než žádná.

---

## Kdy to nedělat

- ❌ **Nasazuje se s výpadkem a ten je přijatelný.** Pak je jedno nasazení levnější než čtyři.
- ❌ **Změnu vlastníš celou** — jeden klient, jedno nasazení, žádné rolling deploy.
- ❌ **Tabulka je malá a provoz nulový.** U tisíce řádků v noci se nic nestane.
- ❌ **Nemá kdo dotáhnout contract.** Bez toho skončíš v horším stavu, než v jakém jsi začínal.
- ❌ **Mění se význam, ne tvar.** Když nová hodnota znamená něco jiného, nejde o migraci, ale o změnu chování — a ta se řeší jinak.

---

## Časté chyby

| Chyba | Proč vadí | Jak správně |
| ----- | --------- | ----------- |
| **Contract se neudělá** | Skončíš v horším stavu, než jsi začínal | Naplánovat ho jako úkol s datem, ne jako přání |
| Chybí dvojí zápis | Data od nové verze jsou pro starou neviditelná | Zapisovat do obou, dokud stará běží |
| Backfill jedním `UPDATE` | Zámek na tabulce, provoz stojí | Po dávkách s pauzou |
| Contract hned po migrate | Ještě běží stará instance a spadne | S odstupem, po ověření |
| Nový sloupec je `NOT NULL` bez výchozí hodnoty | Stará verze neumí zapsat a spadne při vkládání | Výchozí hodnota nebo nullable |
| Přejmenování místo přidání | To je jednorázová změna, ne expand | Přidat nové vedle starého |
| Neověří se, že staré nikdo nepoužívá | Contract shodí klienta, o kterém nikdo nevěděl | Metrika volání, ne jen grep |
| Fáze se spojí do jednoho nasazení | Vzniklo přesně to okno, kterému ses vyhýbal | Každá fáze vlastní nasazení |

---

## Dvě podoby téhož

Technika se používá ve dvou světech a v obou má stejnou strukturu:

| | **Databázové schéma** | **Rozhraní / API** |
| --- | --- | --- |
| **Expand** | přidat sloupec | přidat novou metodu / pole |
| **Migrate** | dvojí zápis, backfill, přepnout čtení | převést klienty na novou |
| **Contract** | odstranit starý sloupec | odstranit starou metodu |
| Co určuje délku | velikost tabulky | **jak rychle se aktualizují klienti** |
| Kdo drží tempo | tým | **klienti, které neřídíš** |

Rozdíl je v posledních dvou řádcích. **U schématu si tempo určuješ sám; u veřejného API ne** — a proto tam mezistav trvá měsíce až roky a fáze contract se plánuje s dlouhým předstihem a oznámením.

---

## Demo

```bash
php Refactoring/System/ExpandContract/demo/run.php
```

Výměna sloupce `shipped` (ano/ne) za `status` (nová/expedovaná/zrušená) na 250 objednávkách. Demo prochází všechny čtyři fáze a **v každé zkusí, jestli funguje stará i nová verze aplikace** — protože při postupném nasazování běží obě. Ukáže dvojí zápis (nová verze zapíše status, stará to vidí přes `shipped`), backfill po dávkách a nakonec fázi contract, která **starou verzi teprve teď vyřadí**. Závěrečná tabulka shrnuje, ve kterých fázích se dá bezpečně zůstat.

---

## Související

| Dokument | Vztah |
| -------- | ----- |
| [Branch by Abstraction](../BranchByAbstraction/) | Sourozenec o úroveň jinde: ten mění **implementaci** za rozhraním, tenhle mění **samo rozhraní** nebo schéma. |
| [Strangler Fig](../StranglerFig/) | Při přesouvání schopnosti se často mění i schéma — a tehdy se použije tahle technika uvnitř. |
| [Parallel Run](../ParallelRun/) | Ověří, že nová reprezentace dává tytéž výsledky, dřív než se přepne čtení. |
| [Trunk-Based Development](../../../GitWorkflows/TrunkBasedDevelopment/) | Sato techniku doporučuje právě pro continuous delivery — každá fáze je samostatně vydatelná. |
| [Feature flag](../../../GitWorkflows/Glossary.md#feature-flag) | Čím se přepíná čtení mezi starou a novou reprezentací. |
| [Idempotence](../../../SoftwareDesign/Glossary.md#idempotence) | Backfill musí jít pustit znovu — po přerušení se pokračuje, ne začíná od začátku. |
| [Data Mapper](../../../SoftwareDesign/PoEAA/DataMapper/) (PoEAA) | Kde se dvojí zápis v aplikaci nejčastěji implementuje. |
| [Introduce Parameter Object](../../Code/IntroduceParameterObject/) | Totéž v malém: když se mění podpis metody, kterou volá i kód mimo repozitář. |
| [Charakterizační testy](../../CharacterizationTests/) | **Krok nula.** Co dělat, když testy neexistují a zadání, podle kterého by se napsaly, taky ne. |

---

## Původ

|             |                    |
| ----------- | ------------------ |
| **Autor**   | Danilo Sato (Parallel Change) |
| **Rok**     | 2014               |
| **Zdroj**   | bliki *Parallel Change* na martinfowler.com |
| **Náročnost** | ●●●○○            |

Vzor popsal **Danilo Sato** 13. května 2014 na Fowlerově bliki pod názvem *Parallel Change* — a hned v úvodu uvádí i druhé jméno, *expand and contract*. Sato ho staví do souvislosti s **continuous delivery**: hodnota není v samotném rozdělení na fáze, ale v tom, že **každou fázi lze samostatně vydat**.

Databázová podoba je starší než název. **Scott Ambler a Pramod Sadalage** ji popsali v knize *Refactoring Databases* (2006) jako katalog malých, chování zachovávajících úprav schématu, z nichž každá má **výslovné přechodové období, kdy staré i nové schéma existuje vedle sebe.** Jméno „expand–contract" tehdy ještě neexistovalo, disciplína ano.

Náročnost je trojka a je celá v dokončení. Mechanika je průhledná — přidej, převeď, odeber — a každý krok je jednoduchý. Cena je jinde:

- **Dvojí zápis je zdroj chyb**, protože se na něj snadno zapomene v nové cestě kódu.
- **Backfill u velkých tabulek trvá hodiny** a musí jít přerušit a znovu spustit.
- **Fáze contract se odkládá**, protože „to už přece funguje" — a to je způsob, jak skončit v horším stavu, než v jakém jsi začínal.

Poslední bod stojí za zopakování, protože se týká i ostatních technik v téhle sekci. [Branch by Abstraction](../BranchByAbstraction/) i [Strangler Fig](../StranglerFig/) mají tentýž závěrečný krok a tutéž slabinu: **nedokončená migrace je horší než žádná.**

---

## Zdroje

- Danilo Sato: [*Parallel Change*](https://martinfowler.com/bliki/ParallelChange.html), martinfowler.com, 2014
- Scott Ambler, Pramod Sadalage: *Refactoring Databases: Evolutionary Database Design*, Addison-Wesley, 2006

---

<details>
<summary>Metadata techniky</summary>

```yaml
name: Expand–Contract
level: system
author: Danilo Sato (Parallel Change); databázová podoba Ambler & Sadalage
year: 2014
duration: dny (schéma) až roky (veřejné API)
reversible: ve fázích 1–3 ano; po fázi contract ne
requires_tests: ano
difficulty: 3
tags: [migrace, schéma, API, dvojí zápis, backfill, zpětná kompatibilita]
leads_to: []
related: [BranchByAbstraction, StranglerFig, ParallelRun, TrunkBasedDevelopment, DataMapper]
status: done
```

</details>
