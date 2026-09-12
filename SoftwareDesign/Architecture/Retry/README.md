# Retry (Opakování)

> [← zpět na Architecture](../)

> **V jedné větě:** Zopakuj volání, které selhalo — ale jen když může příště dopadnout jinak, jen na jedné vrstvě, omezeně a s náhodnou prodlevou.

> [!IMPORTANT]
> Opakování je nejjednodušší vzor v téhle sbírce a zároveň ten, kterým se dá nejsnáz ublížit. **Je to nástroj, který zhoršuje přesně tu situaci, kvůli které ho nasazuješ** — službě, která se topí, přidá násobek provozu. Všechno níž je o tom, jak to udržet pod kontrolou.

---

## Problém

Volání do cizí služby občas selže bez zjevného důvodu: vypadne spojení, brána vrátí `503`, databáze zrovna přepíná uzel. Za vteřinu by totéž prošlo.

```php
public function pay(Order $order): PaymentResult
{
    return $this->gateway->charge($order->totalInCents());   // a když to spadne, spadne to
}
```

Napsat kolem toho smyčku je otázka tří řádků a každý to udělá. Potíž je, že **těmi třemi řádky se dá položit vlastní infrastruktura**, a v demu je vidět jak.

**Poznáš to podle:**

- v logu je stejná chyba třikrát po sobě ve stejnou milisekundu
- cizí služba se po výpadku zvedne a hned ji položí nahromaděné opakování
- jedno kliknutí uživatele je v přístupovém logu cizí služby dvacetkrát
- opakuje se i to, co selhalo kvůli chybě ve vstupu
- zákazníkovi přišly dva e-maily, dva dobropisy nebo dvě objednávky

---

## Řešení

Opakování má čtyři parametry a **žádný z nich není volitelný**.

| Parametr | Otázka, na kterou odpovídá |
| -------- | -------------------------- |
| **Na co se opakuje** | Je ta chyba přechodná? |
| **Kolikrát** | Kde se to vzdá? |
| **Po jak dlouhé prodlevě** | Nechá se protistraně čas se zvednout? |
| **S jakou náhodou** | Přijdou všichni klienti naráz? |

```php
public function pay(Order $order): PaymentResult
{
    for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; ++$attempt) {
        try {
            return $this->gateway->charge($order->idempotencyKey(), $order->totalInCents());
        } catch (TransientFailure $e) {
            if ($attempt === self::MAX_ATTEMPTS) {
                throw $e;
            }

            $this->clock->sleep($this->backoff->delayFor($attempt));
        }
    }
}
```

Dvě věci na tom kódu stojí za povšimnutí: chytá se **`TransientFailure`, ne `Throwable`**, a do brány jde **klíč pro idempotenci**. Bez obojího je to špatně a níž je proč.

---

## 1. Opakovat jde jen přechodnou chybu

```
druh chyby                      výsledek      volání na službu
přechodná (timeout, 503)        prošlo        3 volání
trvalá (400, neplatný token)    selhalo       5 volání
```

Druhý řádek je celé opakování naruby: **pět volání, nula užitku** — a službě jsi přidal pětinásobek zátěže.

| Opakovat | Neopakovat |
| -------- | ---------- |
| Timeout, chyba spojení | `400` — špatný požadavek |
| `502`, `503`, `504` | `401`, `403` — chybí oprávnění |
| Deadlock v databázi | `404` — není to tam |
| `429` (ale až po `Retry-After`) | `422` — data neprošla validací |

**Hranice je jednoduchá:** opakuj to, co může příště dopadnout jinak, **aniž by se cokoli změnilo na tvé straně.**

---

## 2. Opakuj na jedné vrstvě

Tohle je nejdražší chyba, protože ji nikdo neudělá sám — udělají ji tři lidé nezávisle, každý rozumně.

```
vrstev, které opakují     volání na spodní službu   z jednoho požadavku
1                         3                         1
2                         9                         1
3                         27                        1
4                         81                        1
5                         243                       1
```

**Tři vrstvy po třech pokusech je 27 volání z jednoho kliknutí.** HTTP klient si to zkouší, kolem něj to zkouší služba, a nad ní to zkouší worker ve frontě. Nikdo z nich nedělá nic nerozumného.

Opakovat má **ta vrstva, která ví, jestli je operace bezpečné zopakovat** — obvykle ta nejvyšší, která zná byznysový význam. Pod ní se opakování vypíná, ne nastavuje.

> [!IMPORTANT]
> **Nejdřív zkontroluj, co ti opakuje samo.** HTTP klienty, databázové ovladače, SDK cizích služeb i fronty mívají opakování zapnuté ve výchozím nastavení. Vrstva, o které nevíš, počítá do těch tří stejně.

---

## 3. Exponenciální backoff sám nestačí

Sto klientů selže ve stejný okamžik — protože ta služba spadla všem naráz.

```
strategie                 prodleva        nejhorší koš    košů s provozem
pevná prodleva            1.0 s           100 z 100       1
exponenciální             8.0 s           100 z 100       1
exponenciální + jitter    0.0–7.9 s       12 z 100        16
```

**Exponenciální backoff sám o sobě vlnu nerozpustí.** Prodleva je delší, ale pro všechny stejná — takže se ta vlna jen posune o osm sekund a dopadne se stejnou silou. Teprve **náhoda** ji rozprostře.

```php
// Bez jitteru: všichni se vrátí přesně za 8 s
$delay = min($cap, $base * 2 ** ($attempt - 1));

// Full jitter: každý někde mezi nulou a tím číslem
$delay = random_int(0, (int) ($base * 2 ** ($attempt - 1) * 1000)) / 1000;
```

Popsal to **Marc Brooker** v článku na blogu AWS v roce 2015 a porovnal několik variant:

| Varianta | Jak se počítá | Chování |
| -------- | ------------- | ------- |
| **Bez jitteru** | `base · 2^n` | Všichni pořád zarovnaní |
| **Full jitter** | `random(0, base · 2^n)` | Rozprostře rovnoměrně; **nejnižší zátěž serveru** |
| **Equal jitter** | `půlka + random(0, půlka)` | Drží minimální odstup |
| **Decorrelated** | odvozuje se z minulé prodlevy | O něco rychleji hotovo, o něco víc zátěže |

**Výchozí volba je full jitter.** Je to i to, co mají ve výchozím nastavení SDK od AWS a Google Cloudu.

---

## 4. Kolik to celkem přidá

```
výpadek trvá                  60 s
požadavků za vteřinu          20
bez opakování                 1200 volání
s opakováním 3×               3600 volání
```

Služba, která se topí, dostane během výpadku **trojnásobek provozu**. To není vedlejší efekt, to je přímý důsledek — a je to důvod, proč opakování nikdy nestojí samo.

**Strop na počtu pokusů je minimum. Skutečná odpověď je [jistič](../CircuitBreaker/):** po sérii selhání se přestane volat úplně a opakování se tím zastaví u zdroje.

> [!IMPORTANT]
> **Pořadí je „opakuj uvnitř jističe", ne naopak.** Když je jistič uvnitř smyčky, tři pokusy jen třikrát narazí na otevřený jistič a nic se neušetří. Když je venku, počítají se selhání správně a po prahu se přestane volat.

---

## Účastníci

| Účastník | Role |
| -------- | ---- |
| **Volající** | Rozhoduje, jestli je operace bezpečné zopakovat |
| **Klasifikace chyby** | Odděluje přechodné od trvalých — bez ní je zbytek k ničemu |
| **Strategie prodlevy** | Exponenciála se stropem a s náhodou |
| **Idempotentní protistrana** | Musí snést, že tutéž operaci dostane dvakrát |

Poslední řádek je podmínka, ne doporučení. Rozebraná je [níž](#idempotence-je-podmínka-ne-doporučení).

---

## Idempotence je podmínka, ne doporučení

Opakování z principu znamená, že **operace může proběhnout víckrát** — a to i tehdy, když první pokus ve skutečnosti prošel a jen se ztratila odpověď. Timeout neříká „nestalo se to". Říká „nevím".

```php
// Volající neví, jestli první pokus prošel. Zákazník dostane dva dobropisy.
$gateway->refund($paymentId, $amountInCents);

// Brána pozná, že je to totéž volání, a podruhé neudělá nic
$gateway->refund($paymentId, $amountInCents, idempotencyKey: 'refund-' . $paymentId);
```

Je to přesně ten [stupeň 1b z poka-yoke](../../Principles/ObjectDesign.md#stupeň-1b-když-se-chybě-nedá-zabránit): chybě nejde zabránit, protože opakování **musí** být povolené — tak se jí odebere následek. Bez idempotence je opakování stroj na duplicity.

---

## Kdy použít

- ✅ Voláš něco přes síť a chyby jsou občasné a přechodné.
- ✅ Protistrana je idempotentní, nebo umíš klíč pro idempotenci poslat.
- ✅ Víš, kolik pokusů je ještě rozumných, a uživatel tak dlouho počká.
- ✅ Máš nad tím [jistič](../CircuitBreaker/).

## Kdy nepoužít

- ❌ **Chyba je trvalá.** Opakování `400` je jen zátěž.
- ❌ **Operace není idempotentní a nedá se udělat.** Nejdřív idempotence, pak opakování.
- ❌ **Opakuje už vrstva pod tebou.** Sečti si, kolik volání z toho vyjde.
- ❌ **Uživatel čeká.** Tři pokusy s backoffem jsou snadno patnáct sekund — do fronty s tím.
- ❌ **Selhání je masivní.** Když neodpovídá nic, opakování situaci jen prodlouží. Na to je jistič.

---

## Časté chyby

| Chyba | Proč vadí | Jak správně |
| ----- | --------- | ----------- |
| `catch (Throwable)` a opakovat | Opakuje se i chyba ve vlastním kódu | Chytat jen přechodné chyby |
| Bez stropu na počtu pokusů | Z opakování je útok na vlastní službu | Tři až pět a konec |
| Bez stropu na prodlevě | Exponenciála doroste do hodin | `min($cap, …)` |
| Pevná prodleva | Sto klientů se vrátí ve stejnou vteřinu | Exponenciála **a** jitter |
| Exponenciála bez jitteru | Vlna se jen posune | Full jitter |
| Opakování na každé vrstvě | Tři vrstvy = 27 volání | Opakuje jedna, ostatní vypnout |
| Bez idempotence | Dva dobropisy | Klíč pro idempotenci |
| `429` se opakuje hned | Protistrana řekla „zpomal" a ty zrychlíš | Počkat podle `Retry-After` |
| Jistič uvnitř smyčky | Třikrát narazíš na otevřený jistič a nic neušetříš | Opakovat **uvnitř** jističe |

Poslední řádek je jediná věc, kterou si u těch dvou vzorů stačí zapamatovat, a plete se skoro vždycky.

---

## V praxi

- **Symfony HttpClient** má [`RetryableHttpClient`](https://symfony.com/doc/current/http_client.html#retry-failed-requests) s exponenciálním backoffem a jitterem; výchozí nastavení opakuje jen bezpečné metody a vybrané stavové kódy.
- **AWS SDK a Google Cloud knihovny** mají ve výchozím režimu full jitter — tedy přesně to, co Brookerův článek doporučuje.
- **Doctrine** opakování neřeší; deadlock při souběžném zápisu je přesně ten případ, kdy se opakovat má, a musí se to napsat ručně.
- **Fronty** (RabbitMQ, SQS, Symfony Messenger) mají opakování zabudované a je to obvykle **to správné místo**, kde má být — mimo požadavek uživatele, s viditelným počtem pokusů a s frontou pro neúspěšné zprávy.

---

## Související patterny

| Pattern | Vztah |
| ------- | ----- |
| [Circuit Breaker](../CircuitBreaker/) | **Nutná dvojice.** Opakování zvyšuje zátěž, jistič ji zastaví. Opakuj **uvnitř** jističe. |
| [Idempotence](../../Glossary.md#idempotence) | Podmínka, bez které je opakování stroj na duplicity. |
| [Poka-yoke, stupeň 1b](../../Principles/ObjectDesign.md#stupeň-1b-když-se-chybě-nedá-zabránit) | Proč se opakovanému doručení nebrání, ale odebírá se mu následek. |
| [Saga](../Saga/) | Kompenzace řeší, co s procesem, který ani po opakování neprošel. |
| [Batching](../Batching/) | Opakování dávky je ošemetné: jedna vadná zpráva nesmí nutit k opakování zbylých 499. |
| [Anticorruption Layer](../../DDD/AnticorruptionLayer/) (DDD) | Kde se klasifikace cizích chyb na přechodné a trvalé má odehrát. |

---

## Vztah k principům

| Princip | Jak souvisí |
| ------- | ----------- |
| [Poka-yoke](../../Principles/ObjectDesign.md#poka-yoke--znemožni-chybu-nebo-ji-nakloň) | Idempotence odebírá následek chybě, které nejde zabránit. |
| [Fail Fast](../../Principles/ObjectDesign.md#fail-fast) | Trvalá chyba má selhat hned, ne po pěti pokusech. |
| [Zviditelni implicitní](../../Principles/ObjectDesign.md#zviditelni-implicitní) | „Přechodná chyba" je pojem, který má mít v kódu typ, ne komentář. |

---

## Demo

```bash
php SoftwareDesign/Architecture/Retry/demo/run.php
```

Čtyři měření, všechna deterministická.

Nejdřív rozdíl mezi přechodnou a trvalou chybou (3 volání s užitkem proti 5 bez něj). Pak **násobení napříč vrstvami** — tabulka, ze které je vidět, že tři vrstvy po třech pokusech dělají 27 volání z jednoho kliknutí.

Třetí část pouští sto klientů, kteří selhali ve stejný okamžik, a sleduje jejich čtvrtý pokus:

```
strategie                 prodleva        nejhorší koš    košů s provozem
pevná prodleva            1.0 s           100 z 100       1
exponenciální             8.0 s           100 z 100       1
exponenciální + jitter    0.0–7.9 s       12 z 100        16
```

Na tom je vidět, **že exponenciální backoff sám o sobě nepomůže** — vlna se jen posune. Nakonec demo spočítá, o kolik víc provozu dostane služba během výpadku (trojnásobek).

---

## Původ

|              |                            |
| ------------ | -------------------------- |
| **Zdroj**    | nemá jediný — praxe distribuovaných systémů |
| **Backoff a jitter** | Marc Brooker, AWS, **2015** |
| **Kontext** | Michael Nygard: *Release It!*, 2007 — opakování jako součást stability |
| **Obtížnost**| ●●○○○                      |

Opakování je starší než jeho jméno a nikdo si ho nenárokuje — smyčku kolem volání napsal každý, kdo kdy sáhl na síť. Zajímavé je, že **to podstatné na něm bylo pojmenované až dlouho potom**.

Exponenciální backoff pochází z ethernetu a jeho řešení kolizí. **Marc Brooker** v roce 2015 na blogu AWS ukázal simulacemi, že samotný exponenciální backoff nestačí a že rozhoduje **náhoda** — a dal jednotlivým variantám jména, která se dnes používají.

Dvojka na obtížnosti je za mechaniku; smyčka je triviální. Těžké je, že **každý z těch čtyř parametrů se dá nastavit tak, že uškodí**, a že škoda není vidět při vývoji — projeví se až ve chvíli, kdy něco spadne a opakování z malého problému udělá velký.

---

## Zdroje

- Marc Brooker: [*Exponential Backoff And Jitter*](https://aws.amazon.com/blogs/architecture/exponential-backoff-and-jitter/), AWS Architecture Blog, 2015
- Michael Nygard: *Release It!*, Pragmatic Bookshelf, 2007
- [Amazon Builders' Library: Timeouts, retries, and backoff with jitter](https://aws.amazon.com/builders-library/timeouts-retries-and-backoff-with-jitter/)

---

<details>
<summary>Metadata patternu</summary>

```yaml
name: Retry
name_cs: Opakování
category: Architektura
source: praxe distribuovaných systémů; backoff a jitter — Marc Brooker, AWS
authors: [Marc Brooker]
year: 2015
difficulty: 2
tags: [odolnost, backoff, jitter, idempotence, přechodné chyby]
principles: [Poka-yoke, Fail Fast, Zviditelni implicitní]
related: [CircuitBreaker, Saga, Batching, AnticorruptionLayer]
status: done
```

</details>
