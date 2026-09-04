# Active Record (Aktivní záznam)

> [← zpět na PoEAA](../)

> **V jedné větě:** Objekt odpovídá řádku tabulky a umí se sám najít i uložit — `$order->save()`.

> [!NOTE]
> Tenhle vzor má mezi vývojáři pověst „toho horšího“. **Není to pravda a tenhle dokument to tvrdit nebude.** Active Record je správná odpověď na velkou část aplikací, které se v PHP píšou, a Laravel na něm stojí. Má ale přesně danou hranici, za kterou začne překážet — a užitečné je poznat ji dřív, než na ni narazíš.

---

## Problém

Máš tabulku a potřebuješ s ní pracovat. Bez pomoci to znamená čtyři metody, ruční SQL a překlad řádku na objekt — na každou tabulku znovu.

**Poznáš to podle:**

- na každou tabulku píšeš `insert`, `update`, `findById` a `delete` prakticky stejně
- polovina kódu je překlad `$row['total_cents']` na `$object->totalInCents`
- administrace o dvaceti obrazovkách má šedesát tříd, které nedělají nic než CRUD
- na jednoduchý číselník je potřeba entita, repository, mapper a konfigurace mapování
- prototyp, který měl vzniknout za odpoledne, vzniká třetí den

```php
// Před: pro každou tabulku znovu totéž
$statement = $connection->prepare('SELECT * FROM orders WHERE number = ?');
$statement->execute([$number]);
$row = $statement->fetch();

$order = new Order($row['number'], $row['customer_id'], (int) $row['total_cents'], $row['status']);
```

**Tohle je legitimní problém**, ne lenost. Když model *je* v podstatě tabulka, je každá vrstva navíc jen práce bez protihodnoty.

---

## Řešení

Ať si objekt persistenci obstará sám. Jedna třída na tabulku, žádná další vrstva.

```php
$order = Order::find('2024/001');
$order->status = 'potvrzená';
$order->save();
```

```
výsledek:              2024/001 — potvrzená za 1 290,00 Kč
tříd, které to řeší:   1   (samotný model)
```

Model je krátký, protože všechno podstatné umí předek:

```php
final class Order extends ActiveRecord
{
    protected static function table(): string
    {
        return 'orders';
    }

    protected static function primaryKey(): string
    {
        return 'number';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['nová', 'potvrzená'], true);
    }
}
```

**Žádné repository, žádný mapper, žádná konfigurace mapování.** Pro CRUD nad tabulkou je to správná odpověď — a je jich v každé aplikaci většina.

Podrobné srovnání s protipólem je u [Data Mapperu](../DataMapper/#data-mapper-vs-active-record); tenhle dokument ho neopakuje a soustředí se na to, jak Active Record vypadá zevnitř a kde je jeho hranice.

### Určující vlastnost: spojení je statické

```php
$order = Order::find('2024/001');
```

Odkud si tahle metoda vzala databázi? Nikdo jí ji nepředal. Sáhla si pro ni sama — spojení je **statické, sdílené celou aplikací**:

```
Active Record potřebuje spojení do databáze. Bez něj neexistuje ani prázdný objekt.
```

Odtud plyne obojí. **Pohodlí:** `Order::find()` funguje odkudkoli, netahá se konstruktorem a nemusí se registrovat v kontejneru. **Cena:** závislost na databázi není v žádném podpisu vidět a v testu ji nejde vyměnit jinak než globálně — to je [Singleton](../../GoF/Creational/Singleton/) se všemi svými důsledky.

Není to vada implementace, kterou by šlo opravit. **Je to podstata vzoru:** kdyby si objekt spojení nebral sám, nemohl by se sám uložit.

### Hranice první: vazby se načítají potichu

```php
foreach (Order::all() as $order) {
    echo $order->customer()->name;
}
```

Vypadá to jako práce s objekty. Je to šest dotazů:

```
dotazů:                6   (1 na objednávky + 5 na zákazníky)
s eager loadingem:     2   ← Eloquent to řeší přes with()
```

To je [N+1](../../Glossary.md#n1) v učebnicové podobě. **Za problém nemůže pattern, ale to, jak snadno se dá vyrobit** — `$order->customer()` se čte jako přístup k vlastnosti, ne jako dotaz. [Data Mapper](../DataMapper/) má tutéž vlastnost, jen ji hůř schová za lazy loading.

Řešení je v každém frameworku a jmenuje se eager loading (`with()` v Eloquentu). Vědět o něm je povinnost, ne bonus.

### Hranice druhá: pravidlo neotestuješ bez schématu

```
                              Active Record     doménová entita
lze vytvořit bez DB           ne                ano
test pravidla chce schéma     ano               ne
stejné pravidlo vrací         false             false   ← shodně, o pravidlo tu nejde
```

Pravidlo `canBeCancelled()` je v obou případech totéž. Rozdíl je v tom, **co musíš postavit, abys ho spustil**: u Active Recordu schéma, spojení a data; u doménového objektu konstruktor.

Dokud je pravidel pár, je to jedno — testovací databáze se stejně hodí. Ve chvíli, kdy má doména desítky pravidel a kombinací, se z toho stane hlavní brzda: každý test je integrační, i když testuje `if`.

### Hranice třetí: jména sloupců se rozlezou po aplikaci

```
                                  výskytů názvů sloupců
Active Record model               2
volající kód (tohle demo)         4   ← a tady je ten problém
doménová entita                   0
```

Vlastnosti objektu **jsou** sloupce, takže `$order->customer_id` se objeví všude, kde se s objednávkou pracuje. Přejmenování sloupce v databázi pak není migrace, ale refaktoring napříč aplikací — a IDE ho nenajde, protože `customer_id` je z jeho pohledu řetězec.

Tohle je nejtišší z hranic. Neprojeví se, dokud schéma nemusíš měnit.

---

## Účastníci

| Role | V příkladu | Odpovědnost |
| ---- | ---------- | ----------- |
| **Active Record** | `ActiveRecord` | Společné chování: `find()`, `save()`, `delete()`, spojení |
| **Model** | `Order`, `Customer` | Název tabulky, klíč, vazby **a doménová pravidla** |
| **Spojení** | statická `PDO` | Sdílené celou aplikací; nikdo ho nepředává |
| **Řádek** | tabulka `orders` | Zdroj i cíl; **tvar objektu se řídí jím** |

Poslední řádek je celý vzor v jedné větě. U Data Mapperu určuje tvar objektu doména, tady tabulka.

---

## Implementace v PHP

### Vlastní Active Record se nepíše

Ten v demu má sto padesát řádků a umí `find`, `all`, `where`, `save` a `delete`. Chybí mu vazby, hromadné operace, události, soft delete, transakce a validace — a to je právě ta část, kde je práce. **Když chceš Active Record, vezmi Eloquent.** Demo tu je proto, aby bylo vidět, co uvnitř dělá, ne jako předloha.

### Zásadní varianta: Active Record jen jako persistence

Nejužitečnější věc, kterou lze o tomhle vzoru vědět: **není to volba na celý život.** Když model přeroste, nemusíš přepisovat aplikaci na Doctrine — stačí přestat používat model jako doménu:

```php
$record = Order::find('2024/004');

$domain = new Domain\Order(
    $record->number,
    $record->customer_id,
    (int) $record->total_cents,
    $record->status,
);

$domain->cancel();                  // pravidlo běží bez databáze

$record->status = $domain->status();
$record->save();                    // zápis obstará Active Record
```

```
pravidlo běželo v:     doméně (bez databáze)
zápis obstaral:        Active Record
v databázi:            2024/004 — zrušená za 890,00 Kč
```

| | **Čistý Active Record** | **Hybrid** | **Data Mapper** |
| --- | --- | --- | --- |
| Kde jsou pravidla | V modelu | **V doméně** | V doméně |
| Test pravidla bez DB | Ne | **Ano** | Ano |
| Kolik tříd na entitu | 1 | **2** | 2–3 |
| Převod tam a zpět | — | **Ručně** | Mapper |
| Kdy | Model je tabulka | Přerostlo to, přepisovat se nechce | Doména má vlastní tvar |

**Výchozí volba je čistý Active Record**, dokud model odpovídá tabulce. K hybridu sáhni, až když pravidla přerostou model — je to levnější než migrace na jiné ORM a dá se dělat postupně, model po modelu. Tohle dělají větší Laravel projekty a není to zrada vzoru; je to uznání, že tabulka a doména už nemají stejný tvar.

### Co dělá Active Record snesitelným

| Praxe | Proč |
| ----- | ---- |
| Eager loading všude, kde je cyklus | Bez něj [N+1](../../Glossary.md#n1) |
| Pravidla v metodách modelu, ne v kontroleru | Jinak zůstane model prázdný a logika se rozlije |
| Query scopes místo dotazů v kontroleru | Dotaz má jméno a jde znovu použít |
| Nikdy `save()` v cyklu bez transakce | Každý zápis je round-trip |
| Formulářová validace mimo model | Model má hlídat data, ne UI |

### Čemu se vyhnout

```php
// Model, který vedle sebe drží pravidlo, dotaz, formátování i mail
final class Order extends ActiveRecord
{
    public function canBeCancelled(): bool { /* … */ }
    public function scopeOverdue($query) { /* … */ }
    public function formattedTotal(): string { /* … */ }
    public function sendConfirmationEmail(): void { /* … */ }   // ← tohle ne
}
```

Modelu se snadno stane, že do sebe nasaje celou aplikaci — je po ruce a nic mu v tom nebrání. Odesílání mailů, generování PDF a volání API do modelu nepatří ani v Active Recordu. **Hranice je: model smí umět to, co se týká jeho dat.**

---

## Kdy použít

- ✅ **Model odpovídá tabulce.** CRUD, administrace, číselníky, obsahové weby.
- ✅ **Prototyp nebo malý projekt**, kde je rychlost vývoje důležitější než čistota vrstev.
- ✅ **Tým zná Laravel** a nemá důvod psát věci dvakrát.
- ✅ **Schéma si řídíš sám** a můžeš ho tvarovat podle aplikace.
- ✅ **Pravidel je málo** a týkají se jedné tabulky.

## Kdy nepoužít

- ❌ **Doména má vlastní tvar** — [agregáty](../../DDD/Aggregate/), [value objecty](../../DDD/ValueObject/), invarianty přes víc tabulek.
- ❌ **Pravidel jsou desítky** a potřebuješ je testovat rychle a bez databáze.
- ❌ **Schéma je staré nebo sdílené** s jinou aplikací a nejde ohnout podle domény.
- ❌ **Objekt nemá odpovídat řádku** — jedna doménová věc uložená ve třech tabulkách.
- ❌ **Potřebuješ [Unit of Work](../UnitOfWork/) nebo [Identity Map](../IdentityMap/).** Active Record ukládá hned a tytéž záznamy nesdílí.

---

## Časté chyby

| Chyba | Proč vadí | Jak správně |
| ----- | --------- | ----------- |
| Vazba v cyklu bez eager loadingu | [N+1](../../Glossary.md#n1); vypadá to jako čtení vlastnosti | `with()` |
| Model dělá všechno — mail, PDF, volání API | Nasaje celou aplikaci a nejde otestovat | Model umí to, co se týká jeho dat |
| `save()` v cyklu bez transakce | Každý zápis samostatný round-trip | Transakce, nebo hromadný zápis |
| Pravidla v kontroleru, model prázdný | Anemický model a logika na pěti místech | Pravidla do modelu |
| Doménový test staví schéma a data | Test `if` trvá vteřinu | Pravidlo vytkni, nebo přejdi na hybrid |
| Psaní vlastního Active Recordu | Chybí vazby, události, transakce, hromadné operace | Eloquent |
| Očekávání, že `find()` dvakrát vrátí tentýž objekt | Active Record [Identity Map](../IdentityMap/) nemá | Načíst jednou a předávat |
| `$model->update()` bez kontroly, kdo co smí měnit | Hromadné přiřazení vstupu z formuláře | Whitelist atributů |
| Vlastní nastavení spojení uvnitř modelu | Statické spojení je jedno pro celou aplikaci | Nastavit na jednom místě při startu |

---

## V praxi

- **Laravel Eloquent** — nejrozšířenější Active Record v PHP a důvod, proč vzor v ekosystému žije.
- **Ruby on Rails** — knihovna se jmenuje `ActiveRecord` doslova; odtud vzor vstoupil do širokého povědomí.
- **Yii, CakePHP, Nette Database** — Active Record nebo jeho blízké varianty.
- **Doctrine je Data Mapper** — proto v ní `$entity->save()` neexistuje. Kdo přechází z Laravelu, tohle je první překvapení.
- **Query scopes** — pojmenovaný dotaz na modelu (`Order::overdue()`); nejbližší, co má Active Record k [Specification](../../DDD/Specification/).

---

## Související patterny

| Pattern | Vztah |
| ------- | ----- |
| [Data Mapper](../DataMapper/) (PoEAA) | **Protipól ze stejné knihy.** Tam určuje tvar objektu doména, tady tabulka. [Srovnávací tabulka](../DataMapper/#data-mapper-vs-active-record) je u něj. |
| [Repository](../Repository/) (PoEAA) | Active Record ho nepotřebuje — statické `find()` a `where()` dělají totéž. Za to platíš tím, že persistenci nejde v testu vyměnit. |
| [Unit of Work](../UnitOfWork/) (PoEAA) | Active Record ho **nemá**: `save()` zapisuje hned. Proto v Eloquentu neexistuje `flush()`. |
| [Identity Map](../IdentityMap/) (PoEAA) | Taky **nemá**: dvě volání `find(1)` vrátí dvě instance. Není to nedopatření, jen tu není vrstva, kam by mapa patřila. |
| [Entity](../../DDD/Entity/) (DDD) | Obojí má identitu, ale entita ji odvozuje od domény, Active Record od primárního klíče. |
| [Value Object](../../DDD/ValueObject/) (DDD) | Špatně se snáší: hodnota bez identity nemá vlastní řádek. Odtud „doména se tvaruje podle tabulky“. |
| [Aggregate](../../DDD/Aggregate/) (DDD) | Nejostřejší hranice. Agregát je celek uložený přes víc tabulek — a to je přesně to, co Active Record neumí. |
| [Singleton](../../GoF/Creational/Singleton/) (GoF) | Statické spojení je Singleton se vším všudy: neviditelná závislost, kterou v testu nevyměníš lokálně. |
| [Service Layer](../ServiceLayer/) (PoEAA) | Kam patří logika, která přerostla jeden model — orchestrace přes víc modelů do modelu nepatří. |
| [Specification](../../DDD/Specification/) (DDD) | Query scope je jeho zjednodušená obdoba: pojmenovaná podmínka, ale vyhodnocená v SQL. |
| [CQRS](../../Architecture/CQRS/) | Čtecí strana Active Recordu sedí dobře; zápisová narazí první. |
| [Segregated Core](../../DDD/SegregatedCore/) (DDD) | Protipól: tam se jádro odděluje do vlastního balíčku, tady se doména záměrně tvaruje podle tabulky. |

---

## Vztah k principům

| Princip | Jak souvisí |
| ------- | ----------- |
| [SRP](../../Principles/SOLID.md#single-responsibility-principle-srp) | **Vědomé porušení.** Model se mění kvůli byznysu i kvůli schématu. Za tu cenu kupuješ míň tříd — a u CRUDu se to vyplatí. |
| [KISS](../../Principles/Simplicity.md#kiss--keep-it-simple) | Hlavní argument pro. Když model je tabulka, je Active Record jednodušší řešení a jednodušší vyhrává. |
| [YAGNI](../../Principles/Simplicity.md#yagni--you-arent-gonna-need-it) | Vrstvy „pro případ, že doména poroste“ se často nevyplatí. Hybridní varianta ukazuje, že přechod jde udělat později. |
| [DIP](../../Principles/SOLID.md#dependency-inversion-principle-dip) | Porušuje ho: doména závisí na databázi napřímo, a to staticky. Tohle je ta hlavní cena. |
| [Vysoká soudržnost](../../Principles/CohesionAndCoupling.md#stupnice-soudržnosti) | Data a chování jednoho záznamu pohromadě — z tohohle pohledu je Active Record soudržnější než anemická entita vedle mapperu. |

Že vzor porušuje dva principy, není verdikt. **Principy jsou měřítko ceny, ne zákaz** — a u aplikace, kde model odpovídá tabulce, je ta cena nízká a protihodnota vysoká.

---

## Demo

```bash
php PoEAA/ActiveRecord/demo/run.php
```

Šest částí nad SQLite v paměti. Nejdřív co vzor **šetří** — CRUD jednou třídou, bez repository a mapperu. Pak tři hranice, každá změřená: N+1 (šest dotazů proti dvěma s eager loadingem), pravidlo, které se bez schématu nespustí, a názvy sloupců rozlezlé z modelu do volajícího kódu. Čtvrtá část ukáže, že bez statického spojení neexistuje ani prázdný objekt. Poslední staví hybrid: pravidlo běží v doméně bez databáze, zápis obstará Active Record.

---

## Původ

|               |                                                   |
| ------------- | ------------------------------------------------- |
| **Zdroj**     | *Patterns of Enterprise Application Architecture*  |
| **Autor**     | Martin Fowler                                     |
| **Rok**       | 2002                                              |
| **Kategorie** | Object-Relational Architectural Patterns          |
| **Obtížnost** | ●○○○○                                             |

Fowler popsal Active Record vedle [Data Mapperu](../DataMapper/) jako **dvě protikladné odpovědi na tutéž otázku** a u obou uvedl, kdy se hodí. Jeho vlastní shrnutí: Active Record je výborný, dokud model odpovídá tabulce, a přestane fungovat ve chvíli, kdy se doména začne vyvíjet vlastním směrem.

Skutečnou slávu vzoru přinesl **Ruby on Rails** (2004), jehož ORM se jmenuje `ActiveRecord` doslova. Rails ukázaly, že se s ním dá postavit reálná aplikace za zlomek času — a nastavily očekávání, které pak zopakoval Laravel. Že je vzor v PHP tak rozšířený, není náhoda ani nevzdělanost: **odpovídá tomu, jak většina webových aplikací skutečně vypadá.**

Obtížnost je jednička, protože pochopit ho je otázka minuty a začít s ním je nejrychlejší cesta k funkční aplikaci. To ale neznamená, že je vždycky levný. **Cena se neplatí na začátku, ale později** — ve chvíli, kdy pravidla přerostou tabulku a testy se protáhnou. Právě proto se vyplatí znát hranici předem a vědět, že [hybrid](#zásadní-varianta-active-record-jen-jako-persistence) existuje.

Spor „Doctrine, nebo Eloquent“ je většinou spor o to, jestli má model tvar domény, nebo tabulky. To je **vlastnost projektu, ne názor** — a proto na něj neexistuje obecná odpověď.

---

## Zdroje

- Martin Fowler: *Patterns of Enterprise Application Architecture*, Addison-Wesley, 2002 — str. 160
- [martinfowler.com: Active Record](https://martinfowler.com/eaaCatalog/activeRecord.html)
- [Laravel: Eloquent ORM](https://laravel.com/docs/eloquent)
- [Laravel: Eager Loading](https://laravel.com/docs/eloquent-relationships#eager-loading)

---

<details>
<summary>Metadata patternu</summary>

```yaml
name: Active Record
name_cs: Aktivní záznam
category: Object-Relational Architectural
source: PoEAA – Patterns of Enterprise Application Architecture
authors: Martin Fowler
year: 2002
difficulty: 1
tags: [persistence, ORM, CRUD, Eloquent, N+1]
principles: [SRP, KISS, YAGNI, DIP, CohesionAndCoupling]
related: [DataMapper, Repository, UnitOfWork, IdentityMap, Entity, ValueObject, Aggregate, Singleton, ServiceLayer, Specification, CQRS]
status: done
```

</details>
