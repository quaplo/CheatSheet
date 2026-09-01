# Entity (Entita)

> [← zpět na DDD](../)

> **V jedné větě:** Objekt, u kterého tě zajímá **který to je**, ne jaký je — má identitu, která přežije změnu všech ostatních atributů.

---

## Problém

Doménový objekt se scvrkne na gettery a settery a všechna pravidla, která k němu patří, bydlí někde vedle. Tomu se říká **anemický model** a je to nejrozšířenější způsob, jak přijít o výhody objektového návrhu a nechat si jen jeho režii.

**Poznáš to podle:**

- třída obsahuje **jen** `getX()` a `setX()` a žádné jiné metody
- vedle ní existuje `XService`, který dělá to, co měla dělat ona
- **odvozený údaj je uložený** (`tier` vedle `points`) a může se s ním rozejít
- rovnost se řeší přes `==`, nebo vůbec — a dvě načtené kopie téhož záznamu si nejsou rovny
- `id` je `null`, dokud se neudělá `flush()`
- kdokoli může zavolat `setStatus('odeslaná')` na zrušené objednávce
- táž podmínka o objektu je v košíku, v ceníku i v exportu

```php
// Před: anemická entita
final class Customer
{
    public function getLoyaltyPoints(): int { return $this->loyaltyPoints; }
    public function setLoyaltyPoints(int $p): void { $this->loyaltyPoints = $p; }
    public function getTier(): string { return $this->tier; }
    public function setTier(string $t): void { $this->tier = $t; }
    // …a tak dál
}

// …a logika, kterou musí zopakovat každý, kdo se zákazníkem pracuje
if ($customer->isActive() && $customer->getTier() === 'zlato') {
    $discount = 10;
}
```

Demo tenhle rozpad ukazuje na jednom řádku: stačí nastavit body jinudy, než přes službu, a uložená úroveň přestane odpovídat:

```
anemický po service->addPoints():  6200 b → zlato   sleva 10 %
po přímém setLoyaltyPoints(200):    200 b → zlato   sleva 10 %   ← ROZPOR
```

---

## Řešení

Entitu definují tři věci, a všechny tři spolu souvisejí:

| | Co to znamená |
| --- | --- |
| **Má identitu** | Zajímá tě, **který** to je. Identita se nemění nikdy. |
| **Rovná se podle identity** | Ne podle obsahu. Dvě instance téhož zákazníka v různém stavu jsou týž zákazník. |
| **Mění se v čase** | A právě proto ji identita drží pohromadě. |

```php
final class Customer
{
    private function __construct(
        public readonly CustomerId $id,      // identita — jediné readonly
        private EmailAddress $email,         // ostatní se mění
        private string $companyName,
        private int $loyaltyPoints,
        private bool $isActive,
    ) {
    }

    /** Rovnost POUZE podle identity. */
    public function equals(self $other): bool
    {
        return $this->id->equals($other->id);
    }
}
```

### Entita, nebo value object?

**Nejdůležitější rozhodnutí při návrhu doménového modelu** — a naštěstí se dá udělat jednou otázkou:

> Zajímá mě, **který** to je, nebo **jaký** je?

| | **Entita** | **[Value object](../ValueObject/)** |
| --- | --- | --- |
| Otázka | Který to je? | Jaký je? |
| Má identitu | Ano, a je neměnná | Ne |
| Rovnost | Podle identity | Podle obsahu |
| Mění se | **Ano**, to je smysl | **Ne**, nikdy |
| V PHP | `final class` s `readonly` identitou | `final readonly class` |
| Příklad | Zákazník, objednávka, faktura | Peníze, e-mail, adresa, PSČ |

A pozor, ta odpověď **není vlastnost věci, ale tvého kontextu**. Bankovka je pro tebe hodnota (je ti jedno která stokoruna), pro Českou národní banku entita (sériové číslo, opotřebení, kdy byla stažena z oběhu). Táž adresa je hodnota v objednávce a entita ve správě poboček.

Demo to ukazuje na rovnosti — přesně naopak, než by to dopadlo u value objectu:

```
equals() se starší verzí téhož   true   ← na obsahu nezáleží
equals() s jiným, ale identickým  false  ← stejné atributy nestačí
```

### Proč entita není readonly

Skoro všechno ostatní v tomhle katalogu je `readonly`, tahle třída ne — a je to záměr. Entita se **má** měnit; to je celý důvod, proč potřebuje identitu. Value object se nemění, protože pětka se nikdy nestane šestkou.

Neměnná entita jde v PHP napsat (`withX()` vracející novou instanci) a v některých stylech se to dělá. Ale platíš za to: přestává být zřejmé, která instance je „ta aktuální“, a sledování změn (Unit of Work v Doctrine) přestane fungovat. **Výchozí volba pro entitu je měnitelná, pro hodnotu neměnná.**

### Settery jsou nepřítel, ne metody

Zbavit se anemického modelu neznamená přidat metody. Znamená to **nahradit settery doménovými operacemi**, které si hlídají svá pravidla:

```php
// Setter: dovolí cokoli, komukoli, kdykoli
$customer->setLoyaltyPoints(-500);
$customer->setTier('platina');

// Doménová operace: má jméno z byznysu a hlídá si pravidlo
$customer->earnPoints(6200);
$customer->redeemPoints(6000);
$customer->deactivate();
```

To druhé je [Tell, Don't Ask](../../Principles/ObjectDesign.md#tell-dont-ask) v praxi. Neptáš se entity na stav, abys pak rozhodl za ni — řekneš jí, co má udělat.

### Odvozené se nepočítá dopředu, ale při dotazu

Drobnost, která likviduje celou kategorii chyb. Úroveň věrnostního programu **není uložený atribut**, je to funkce bodů:

```php
public function tier(): LoyaltyTier
{
    return LoyaltyTier::forPoints($this->loyaltyPoints);
}
```

Nemá se jak rozejít, protože se nikde nedrží. V anemické verzi je `tier` sloupec, který musí někdo aktualizovat — a někdo na to zapomene.

### Identitu vyrábí aplikace

Entita má být platná od okamžiku vzniku. Když identitu přiděluje databáze, je objekt mezi `new` a `flush()` v polovičatém stavu a půlka kódu s tím musí počítat:

```php
$customer = Customer::register(CustomerId::generate(), $email, $name);
// od téhle chvíle je entita platná — žádné id === null
```

Kde se identita bere v praxi, řeší `nextIdentity()` u [Repository](../../PoEAA/Repository/).

---

## Účastníci

| Role | V příkladu | Odpovědnost |
| ---- | ---------- | ----------- |
| **Entita** | `Customer` | Drží identitu, mění stav, hlídá svá pravidla |
| **Identita** | `CustomerId` | Value object; neměnná, generovaná aplikací |
| **Vnitřní hodnoty** | `EmailAddress`, `LoyaltyTier` | Value objecty uvnitř entity |
| **Doménová operace** | `earnPoints()`, `deactivate()` | Pojmenovaná změna se svým pravidlem |
| **Rekonstrukce** | `reconstitute()` | Vznik z úložiště, mimo registrační pravidla |

---

## Implementace v PHP

```php
final class Customer
{
    private function __construct(
        public readonly CustomerId $id,
        private EmailAddress $email,
        private string $companyName,
        private int $loyaltyPoints,
        private bool $isActive,
    ) {
    }

    public static function register(CustomerId $id, EmailAddress $email, string $companyName): self
    {
        if (trim($companyName) === '') {
            throw new \InvalidArgumentException('Název firmy nesmí být prázdný.');
        }

        return new self($id, $email, trim($companyName), loyaltyPoints: 0, isActive: true);
    }

    /** Rekonstrukce z úložiště — neprochází registračními pravidly. */
    public static function reconstitute(/* … */): self { /* … */ }

    public function equals(self $other): bool
    {
        return $this->id->equals($other->id);
    }

    public function earnPoints(int $points): void
    {
        if ($points <= 0) {
            throw new \InvalidArgumentException('Připsat lze jen kladný počet bodů.');
        }

        if ($this->isActive === false) {
            throw new \LogicException('Neaktivní zákazník body nesbírá.');
        }

        $this->loyaltyPoints += $points;
    }

    /** Odvozeno, ne uloženo — nemá se jak rozejít. */
    public function tier(): LoyaltyTier
    {
        return LoyaltyTier::forPoints($this->loyaltyPoints);
    }

    public function discountPercent(): int
    {
        return $this->isActive ? $this->tier()->discountPercent() : 0;
    }
}
```

Dvě továrny vedle sebe — `register()` a `reconstitute()` — jsou schválně. Zakládání má doménová pravidla, **rekonstrukce z databáze je procházet nemá**: ta data už jednou platná byla a dnešní pravidlo tehdy nemuselo existovat. Totéž najdeš u [Repository](../../PoEAA/Repository/).

---

## Kdy použít

- ✅ Zajímá tě, **který** objekt to je, a chceš ho sledovat v čase.
- ✅ Objekt má **životní cyklus** — vzniká, mění se, končí.
- ✅ Platí pro něj pravidla, která má někdo hlídat.
- ✅ Dvě instance se stejnými atributy mají být **různé věci**.

## Kdy nepoužít

- ❌ **Zajímá tě jen hodnota.** Peníze, e-mail, rozmezí dat — to je [value object](../ValueObject/), a je levnější.
- ❌ **Objekt se nemění.** Bez změny nepotřebuješ identitu, která by ho držela pohromadě.
- ❌ **Jde o čtecí model.** Řádek tabulky v administraci není entita a nemá mít chování — viz [CQRS](../../Architecture/CQRS/).
- ❌ **Chceš z každé tabulky entitu.** Databázové schéma není doménový model.

---

## Časté chyby

| Chyba | Proč vadí | Jak správně |
| ----- | --------- | ----------- |
| Samé gettery a settery, logika ve `Service` | Pravidla musí znát a zopakovat každý — a jednou je někdo zopakuje jinak | Doménové operace na entitě ([Tell, Don't Ask](../../Principles/ObjectDesign.md#tell-dont-ask)) |
| Rovnost podle atributů nebo `==` | Dvě načtené kopie téhož záznamu si nejsou rovny; změna vyrobí „jinou“ entitu | `equals()` porovnává **jen** identitu |
| Identita jde měnit | Přestane být identitou; sledování v čase se rozpadne | `public readonly` a žádný setter |
| Odvozený údaj je uložený vedle zdrojového | `tier` se rozejde s `points` a nikdo neví kdy | Odvozuj při dotazu |
| `id` je `null` do `flush()` | Půlka kódu musí počítat s polovičatým objektem | Identitu generuje aplikace |
| Registrační pravidla se pouští i při načtení z DB | Historický záznam nejde načíst, protože dnešní pravidlo tehdy neplatilo | Oddělené `register()` a `reconstitute()` |
| Entita sahá do repository nebo do databáze | Nejde ji otestovat izolovaně a schová N+1 dotazů | Vše dostane v parametru |
| Entita nese formátovací metody pro šablonu | Doména se ohýbá kvůli zobrazení | Formátování do čtecího modelu |

---

## V praxi

- **Doctrine** — „entity“ v Doctrine je mapovaná třída, což **není totéž** co doménová entita. Doménová entita může být Doctrine entitou, ale nemusí; a Doctrine entita s dvaceti settery doménovou entitou rozhodně není.
- **`readonly` identita** — od PHP 8.1 jde `public readonly CustomerId $id`, což je nejjednodušší způsob, jak identitu ochránit.
- **Enumy** — ideální pro stavy a úrovně uvnitř entity; `match` navíc PHPStan kontroluje na vyčerpanost.
- **Sledování změn** — Doctrine Unit of Work pozná změněné atributy sám, takže u měnitelné entity nepotřebuješ explicitní `save()` po každé operaci.

---

## Související patterny

| Pattern | Vztah |
| ------- | ----- |
| [Value Object](../ValueObject/) | **Protipól a nejdůležitější rozhodnutí v modelu.** Entita má identitu, hodnota jen obsah. Uvnitř entity jsou hodnoty (`EmailAddress`, `LoyaltyTier`); naopak to nikdy. |
| [Aggregate](../Aggregate/) | Entity se skládají do agregátů. Jedna z nich se stane kořenem a začne hlídat pravidla celku. |
| [Repository](../../PoEAA/Repository/) | Načítá a ukládá entity — přesněji kořeny agregátů. Odtud `nextIdentity()` i `reconstitute()`. |
| [State](../../GoF/Behavioral/State/) | Když má entita netriviální životní cyklus, stavy se vyplatí vytáhnout z podmínek do objektů. |
| [Specification](../Specification/) | Pravidlo o entitě, které nepatří dovnitř ní — protože kombinuje víc věcí. |
| [Domain Service](../DomainService/) | Kam s operací, která se nevejde ani sem. **Ale až jako poslední možnost** — jinak z entit zbudou datové struktury. |

---

## Vztah k principům

| Princip | Jak souvisí |
| ------- | ----------- |
| [Tell, Don't Ask](../../Principles/ObjectDesign.md#tell-dont-ask) | **Rozdíl mezi anemickou a doménovou entitou je přesně tenhle princip.** `$c->discountPercent()` místo `$c->getTier()` a rozhodování venku. |
| [SRP](../../Principles/SOLID.md#single-responsibility-principle-srp) | Entita se mění, když se změní pravidla o té věci. Ne kvůli zobrazení, ne kvůli databázovému schématu. |
| [Fail Fast](../../Principles/ObjectDesign.md#fail-fast) | Neplatná operace vyhodí výjimku hned. Setter by ji tiše provedl. |
| [DRY](../../Principles/Simplicity.md#dry--dont-repeat-yourself) | Pravidlo o entitě má jedno místo — v ní. V anemickém modelu se opakuje všude, kde s ní někdo pracuje. |

---

## Demo

```bash
php DDD/Entity/demo/run.php
```

Ukáže, jak identita přežije změnu jména, e-mailu i bodů, jak rovnost ignoruje obsah, **jak se v anemické verzi rozejde uložená úroveň s body** (200 bodů, ale pořád „zlato“), rozdíl Tell Don't Ask na jednom řádku a nakonec pravidla, která entita nepustí.

---

## Původ

|               |                                                    |
| ------------- | -------------------------------------------------- |
| **Zdroj**     | *Domain-Driven Design*, kapitola 5                  |
| **Autor**     | Eric Evans                                          |
| **Rok**       | 2003                                                |
| **Kategorie** | Taktické stavební bloky                             |
| **Obtížnost** | ●●○○○                                               |

Evans používá termín **Entity** pro to, čemu se dřív říkalo *reference object*: objekt definovaný spíš svou kontinuitou a identitou než svými atributy. Klíčová věta z knihy zní, že *„identita je definována něčím jiným než atributy“* — a celá kapitola je vlastně o jednom rozhodnutí: **entita, nebo hodnota?**

Anemický model, proti kterému tenhle pattern stojí, pojmenoval **Martin Fowler** v roce 2003 v článku *Anemic Domain Model*. Jeho pozorování bylo trpké: tenhle anti-pattern se šíří právě proto, že **vypadá jako vrstvená architektura**. Objekty jsou, vrstvy jsou, jen v těch objektech není nic — logika žije v „službách“ a objekty jsou datové struktury s obalem.

V PHP tomu pomohl svět frameworků a ORM, kde se „entita“ ustálila jako mapovaná třída se settery. Stojí za to ten pojem oddělit: **Doctrine entita je technický termín, doménová entita je návrhový.** Můžou být totéž, ale samo od sebe se to nestane.

---

## Zdroje

- Eric Evans: *Domain-Driven Design*, Addison-Wesley, 2003 — kapitola 5
- Martin Fowler: *AnemicDomainModel*, 2003 — [martinfowler.com/bliki/AnemicDomainModel.html](https://martinfowler.com/bliki/AnemicDomainModel.html)
- Vaughn Vernon: *Implementing Domain-Driven Design*, Addison-Wesley, 2013 — kapitola 5

---

<details>
<summary>Metadata patternu</summary>

```yaml
name: Entity
name_cs: Entita
category: Taktické stavební bloky
source: DDD – Domain-Driven Design
authors: Eric Evans
year: 2003
difficulty: 2
tags: [identita, doménový model, životní cyklus, anemický model, chování]
principles: [TellDontAsk, SRP, FailFast, DRY]
related: [ValueObject, Aggregate, Repository, State, Specification]
status: done
```

</details>
