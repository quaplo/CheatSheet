# Singleton (Jedináček)

> [← zpět na Creational](../)

> **V jedné větě:** Třída, která zaručí, že z ní existuje jen jedna instance, a zpřístupní ji odkudkoli — což je slib, jehož druhá polovina je problém.

> [!WARNING]
> Tenhle pattern je v katalogu proto, že na něj narazíš — ne proto, aby se používal. **V PHP s DI kontejnerem ho skoro nikdy nepotřebuješ**, a [demo](#demo) ukazuje spustitelně, co za něj platíš. Pokud hledáš, co použít místo něj, přeskoč na [Co použít místo](#co-použít-místo).

---

## Problém

Něčeho má být v aplikaci **jen jedno** — konfigurace, připojení k databázi, registr. A má se k tomu dostat kód, který je hluboko a nechce si to protahovat pěti konstruktory.

**Poznáš to podle:**

- konfigurace se předává přes čtyři vrstvy, aby se dostala tam, kde je potřeba
- vzniká `Config::get('vat')` nebo `Registry::get('db')` volané odkudkoli
- objekt je drahý na vytvoření a nechceš ho vytvářet znovu
- „stejně bude jen jeden, tak proč to komplikovat“

```php
// Před: konfigurace putuje aplikací jen proto, aby doputovala
$controller = new OrderController(
    new PlaceOrderHandler(
        new ShippingCalculator($config),   // ← jen tady je opravdu potřeba
        $config,                            // ← a tady už jen prochází
    ),
    $config,                                // ← a tady taky
);
```

---

## Řešení podle GoF

Konstruktor privátní, instance statická, přístup přes statickou metodu:

```php
final class PriceConfig
{
    private static ?self $instance = null;

    private function __construct()
    {
    }

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __clone()
    {
    }

    public function __wakeup(): void
    {
        throw new \LogicException('Singleton nelze deserializovat.');
    }
}
```

Funguje to a slib splní:

```
$a === $b:  true
```

Jenže ten slib má dvě části a **problematická je ta druhá**:

| Část slibu | Hodnocení |
| ---------- | --------- |
| „Existuje jen jedna instance“ | Legitimní požadavek. Občas opravdu platí. |
| „Je dostupná odkudkoli“ | **Tady je problém.** To není zajištění jedinečnosti, to je globální proměnná. |

---

## Co za to platíš

Všechno níž je v demu spustitelné.

### 1. Závislost, kterou z třídy nepoznáš

```php
final class ShippingCalculator
{
    public function calculate(int $orderTotalInCents): int
    {
        $config = PriceConfig::getInstance();     // ← závislost schovaná v těle metody

        return $orderTotalInCents >= $config->freeShippingFromCents ? 0 : 9900;
    }
}
```

```
parametrů konstruktoru: 0
skutečných závislostí:  1  (PriceConfig, schovaná uvnitř metody)
```

**Tohle je hlavní problém, ne ta jedinečnost.** Z podpisu třídy nepoznáš, co potřebuje; musíš přečíst těla metod. A protože to nikdo nevidí, závislostí postupně přibývá.

### 2. Test nejde napsat

```
Se singletonem:
    doprava pro 600 Kč: 99 Kč   ← pořád platí globální hranice 1 500 Kč
    jinou konfiguraci nepodstrčím — instance je jedna a globální

Bez singletonu:
    doprava pro 600 Kč: 0 Kč   ← test si nastavil vlastní hranici
```

Instanci nejde nahradit, protože je globální. Zbývají obcházení — reflexe do statické vlastnosti, `runInSeparateProcess`, nebo přidání `setInstance()` pro testy, čímž singleton přestane být singletonem.

### 3. Testy přestanou být nezávislé

```
test A nastaví DPH na 0 %
test B pak vidí:  0 %   ← a neví proč
```

Statický stav přežije mezi testy. Testy začnou selhávat **podle pořadí, ve kterém běží** — a to je jedna z nejhůř dohledatelných věcí vůbec.

### 4. „Jen jedna“ je požadavek, který se mění

```
objednávka za 1 700 Kč:
    CZ (zdarma od 1 500 Kč):  0 Kč
    SK (zdarma od 2 000 Kč):  99 Kč
```

Expanze do druhé země, více tenantů, jiná konfigurace pro administraci — a najednou instance musí být dvě. Se singletonem to znamená **přepsat každé volání `getInstance()` v aplikaci**.

---

## Co použít místo

Prakticky vždycky: **obyčejný objekt a DI kontejner.**

```php
final readonly class PriceConfig
{
    public function __construct(
        public int $vatPercent = 21,
        public int $freeShippingFromCents = 150000,
    ) {
    }
}

final readonly class ShippingCalculator
{
    public function __construct(
        private PriceConfig $config,     // ← závislost je vidět
    ) {
    }
}
```

Kontejner vytvoří instanci jednou a předá ji všem, kdo si o ni řeknou. **Jedinečnost zůstane, globální přístup zmizí** — a to je celý rozdíl.

| | **Singleton** | **DI kontejner** |
| --- | --- | --- |
| Jedna instance v aplikaci | ano | **ano** |
| Závislost je vidět | **ne** | ano |
| Jde podstrčit v testu | **ne** | ano |
| Dvě konfigurace vedle sebe | **ne** | ano |
| Testy jsou nezávislé | **ne** | ano |
| Práce navíc | žádná | řádek v konfiguraci |

Ten poslední řádek je celý důvod, proč singleton vznikl — **a jediný, který dnes neplatí.**

### Když opravdu potřebuješ jedinou instanci

Občas jedinečnost není pohodlí, ale požadavek — obvykle kvůli sdílenému prostředku. I tehdy ale platí, že **zajistit ji má kontejner, ne třída**:

| Situace | Řešení |
| ------- | ------ |
| Drahé připojení, které se má sdílet | Služba v kontejneru, injektovaná |
| Registr, do kterého se přihlašují pluginy | Služba s tagovanými službami |
| Sdílený stav napříč requestem | Služba se stavem, vytvořená kontejnerem |
| Opravdu globální technický zdroj | Singleton **uvnitř knihovny**, ne v aplikačním kódu |

Poslední řádek je jediný, kde má smysl. Uvnitř knihovny, která nemůže spoléhat na kontejner, je to legitimní volba — a i tam se to obvykle dá vyřešit statickou továrnou s možností instanci podstrčit.

### Enum jako jedináček, který nevadí

PHP 8.1 dalo k dispozici variantu bez nevýhod:

```php
enum Currency: string
{
    case CZK = 'CZK';
    case EUR = 'EUR';
}

Currency::CZK === Currency::CZK;   // true — vždycky tatáž instance
```

Enum case **je** jediná instance a jazyk to garantuje. Nemá to žádnou z nevýhod výše, protože **nedrží měnitelný stav**. Přesně proto se v tomhle katalogu enumy používají tam, kde by GoF sáhli po singletonu — třeba u [stavů](../../Behavioral/State/) nebo u bezstavových [strategií](../../Behavioral/Strategy/).

Rozhodující je právě ten stav: singleton bez stavu je neškodný, singleton se stavem je globální proměnná.

---

## Účastníci

| Role | V příkladu | Odpovědnost |
| ---- | ---------- | ----------- |
| **Singleton** | `Classic\PriceConfig` | Drží jedinou instanci a zpřístupňuje ji |
| **Klient** | `Classic\ShippingCalculator` | Sáhne si pro ni **kdykoli a odkudkoli** |
| *(lepší varianta)* **Služba** | `Better\PriceConfig` | Obyčejný objekt, o jedinečnosti nic neví |
| *(lepší varianta)* **Kontejner** | DI | Vytvoří jednou, předá všem |

---

## Implementace v PHP

Když už, tak správně. Singleton má tři místa, kudy uniká:

```php
final class PriceConfig
{
    private static ?self $instance = null;

    private function __construct() { }        // 1. nelze vytvořit zvenčí

    private function __clone() { }            // 2. nelze naklonovat

    public function __wakeup(): void          // 3. nelze deserializovat
    {
        throw new \LogicException('Singleton nelze deserializovat.');
    }

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }
}
```

Bez druhého a třetího bodu **singleton nedrží** — `clone` i `unserialize()` vytvoří druhou instanci a celá záruka padá.

### Souběžnost v PHP nehrozí

V jazycích s vlákny je singleton klasickým zdrojem problémů se souběhem a řeší se zamykáním. **V PHP tenhle problém neexistuje** — každý request má vlastní paměť a statická vlastnost s ním zaniká.

Zároveň z toho plyne, co singleton v PHP **není**: není to sdílení mezi requesty. Když potřebuješ, aby něco přežilo request, potřebuješ cache nebo databázi, ne singleton.

V dlouho běžících procesech (Swoole, RoadRunner, workery) se to obrací — statický stav **přežije mezi requesty**, což je zdroj velmi nepříjemných chyb. Pattern, který byl neškodný, se stane nebezpečným, aniž by se změnil řádek kódu.

---

## Kdy použít

- ✅ **Uvnitř knihovny**, která nemůže spoléhat na DI kontejner — a i tam s možností instanci podstrčit.
- ✅ Jako **enum** pro bezstavovou hodnotu, kde jedinečnost garantuje jazyk.
- ✅ Když potřebuješ **rozpoznat** ten pattern v cizím kódu a vědět, co s ním.

## Kdy nepoužít

- ❌ **V aplikačním kódu, kde máš DI kontejner.** Což je prakticky vždycky.
- ❌ **Na konfiguraci, připojení, logger, cache.** Všechno to jsou služby a kontejner je udělá líp.
- ❌ **Když drží měnitelný stav.** To už není singleton, to je globální proměnná s lepším jménem.
- ❌ **Jako náhradu za promyšlené předávání závislostí.** Že se něco „špatně předává“, je signál o návrhu, ne důvod pro globální přístup.
- ❌ **V dlouho běžících procesech.** Statický stav přežije request a začne se chovat nepředvídatelně.

---

## Časté chyby

| Chyba | Proč vadí | Jak správně |
| ----- | --------- | ----------- |
| **Singleton s měnitelným stavem** | Globální proměnná; testy se ovlivňují podle pořadí | Bezstavový, nebo raději služba v kontejneru |
| Chybí `__clone()` a `__wakeup()` | `clone` a `unserialize()` vyrobí druhou instanci — singleton nedrží | Zakázat obojí |
| `setInstance()` „jen pro testy“ | Singleton přestal být singletonem a testy si přesto lezou do zelí | Injektuj závislost normálně |
| `getInstance()` volané uprostřed metod | Skryté závislosti; z podpisu třídy nic nepoznáš | Konstruktor |
| Singleton jako obal nad databází | Nejde testovat, nejde mít druhé připojení | Služba v kontejneru |
| Statický stav v dlouho běžícím procesu | Přežije request a míchá data mezi uživateli | Bez statického stavu |
| „Bude jen jeden, tak ať je singleton“ | Zaměňuje se **kolik jich je** za **jak se k nim dostat** | Jedinečnost řeší kontejner, ne třída |

---

## V praxi

- **Symfony DI** — služby jsou ve výchozím nastavení *shared*, tedy jedna instance na kontejner. Přesně to, co singleton slibuje, bez jeho nevýhod.
- **PHP enumy** — jediná podoba singletonu, která se v moderním PHP doporučuje.
- **Legacy kód** — `Registry::get()`, `Config::getInstance()`, `Db::getInstance()`. Když na to narazíš, prvním krokem k rozpletení bývá **injektovat singleton jako závislost** a teprve pak ho zrušit.
- **Doctrine, Monolog, Guzzle** — žádná z těch knihoven singleton nevynucuje. To samo o sobě něco říká.
- **RoadRunner / Swoole** — místa, kde se ze statického stavu stane chyba, i když roky fungoval.

---

## Související patterny

| Pattern | Vztah |
| ------- | ----- |
| [Factory Method](../FactoryMethod/) | Sdílí mechaniku (privátní konstruktor, statická metoda), ale ne záměr: továrna vytváří **nové** instance, singleton vrací **pořád tutéž**. |
| **Abstract Factory** (GoF) | Často se implementovala jako singleton — dnes je to služba v kontejneru. |
| [State](../../Behavioral/State/) | Bezstavové stavy jde sdílet jako jedináčky; v PHP to řeší enum. |
| [Strategy](../../Behavioral/Strategy/) | Totéž — bezstavová strategie může být sdílená instance a kontejner to udělá sám. |
| [Value Object](../../../DDD/ValueObject/) | Enum jako value object je ta „správná“ podoba jedinečnosti: garantovaná jazykem a bez stavu. |
| [Repository](../../../PoEAA/Repository/) | Častá oběť: `Repository::getInstance()` znemožní in-memory implementaci v testech. |
| [Identity Map](../../../PoEAA/IdentityMap/) (PoEAA) | Časté lákadlo udělat mapu statickou. Znamená to únik paměti a data jednoho uživatele u druhého. |
| [Active Record](../../../PoEAA/ActiveRecord/) (PoEAA) | Statické spojení do databáze je Singleton se vším všudy — neviditelná závislost, kterou v testu nevyměníš lokálně. |

---

## Vztah k principům

| Princip | Jak souvisí |
| ------- | ----------- |
| [DIP](../../../Principles/SOLID.md#dependency-inversion-principle-dip) | **Porušuje ho.** Klient závisí na konkrétní třídě, kterou si sám najde — místo aby dostal abstrakci. |
| [SRP](../../../Principles/SOLID.md#single-responsibility-principle-srp) | Třída dělá svou práci **a navíc** řídí svůj životní cyklus. Dva důvody ke změně. |
| [Zviditelni implicitní](../../../Principles/ObjectDesign.md#zviditelni-implicitní) | Přesný opak: závislost, která by měla být v podpisu, je schovaná v těle metody. |
| [Soudržnost a provázanost](../../../Principles/CohesionAndCoupling.md#stupnice-provázanosti) | Globální stav je **společná provázanost** — druhý nejhorší stupeň na Constantinově stupnici. |

---

## Demo

```bash
php GoF/Creational/Singleton/demo/run.php
```

Nejdřív ukáže, že singleton **svůj slib splní**. Pak spočítá, že třída, která ho používá, má nula parametrů konstruktoru a jednu skutečnou závislost. Zkusí napsat test s jinou konfigurací (nejde), postaví dvě konfigurace vedle sebe (jde jen bez singletonu) a nechá jeden „test“ nastavit DPH na nulu — druhý ji uvidí taky. Končí tabulkou proti DI kontejneru.

---

## Původ

|               |                                                   |
| ------------- | ------------------------------------------------- |
| **Zdroj**     | *Design Patterns: Elements of Reusable Object-Oriented Software* |
| **Autoři**    | Gamma, Helm, Johnson, Vlissides („Gang of Four“)   |
| **Rok**       | 1994                                              |
| **Kategorie** | Creational                                        |
| **Obtížnost** | ●○○○○ na napsání, ●●●●● na odstranění             |

Singleton je **nejznámější a nejkritizovanější vzor z celé knihy**. Zapamatuje si ho každý, kdo o návrhových vzorech kdy slyšel — mimo jiné proto, že je nejkratší a dá se opsat za minutu.

GoF ho mysleli jako řešení situace, kdy má něčeho být jen jedno a jazyk na to nemá prostředek. Rok 1994: žádné DI kontejnery, žádné frameworky, které by životní cyklus objektů řídily za tebe. **V tom kontextu to byla rozumná odpověď.**

Kritika přišla s testováním. Když se v 2000. letech rozšířily jednotkové testy, ukázalo se, že singleton je nejde psát — instanci nejde nahradit, stav přežívá mezi testy a závislosti nejsou vidět. Erich Gamma sám v roce 2009 v rozhovoru řekl, že **kdyby knihu psali znovu, Singleton by v ní nejspíš nebyl**.

Pro dnešního PHP vývojáře je hodnota tohohle vzoru výhradně v tom, že ho **pozná v cizím kódu a ví, co s ním**. Napsat ho je snadné; odstranit z aplikace, která na něm stojí, je práce na týdny — proto ta dvojí obtížnost v tabulce výše.

---

## Zdroje

- Gamma, Helm, Johnson, Vlissides: *Design Patterns*, Addison-Wesley, 1994 — kapitola 3, str. 127
- [Symfony: Service Container](https://symfony.com/doc/current/service_container.html)
- [PHP: Enumerations](https://www.php.net/manual/en/language.enumerations.php)

---

<details>
<summary>Metadata patternu</summary>

```yaml
name: Singleton
name_cs: Jedináček
category: Creational
source: GoF – Design Patterns
authors: Gamma, Helm, Johnson, Vlissides
year: 1994
difficulty: 1
tags: [globální stav, jedinečnost, testovatelnost, anti-pattern, di kontejner]
principles: [DIP, SRP, MakeImplicitExplicit, CohesionAndCoupling]
related: [FactoryMethod, AbstractFactory, State, Strategy, ValueObject, Repository]
status: done
```

</details>
