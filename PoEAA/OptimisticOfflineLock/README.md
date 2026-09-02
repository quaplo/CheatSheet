# Optimistic Offline Lock (Optimistické zamykání)

> [← zpět na PoEAA](../)

> **V jedné větě:** Konfliktům souběžných změn se nepředchází, jen se **poznají** — záznam nese verzi a zápis projde, jen když se verze od načtení nezměnila.

---

## Problém

Dva lidé si otevřou tutéž objednávku. Jeden změní prioritu, druhý poznámku. Oba uloží. **Jedna změna zmizí — a nikdo se to nedozví.**

Tomu se říká **ztracená aktualizace** (lost update) a je nebezpečná právě tím, že je tichá:

```
Anna uloží:    priorita = 'urgentní'
Bedřich uloží: poznámka = 'volat před doručením', priorita = 'běžná'
```

Bedřich Anninu změnu nepřepsal schválně. Uložil prostě to, co měl **načtené** — a jeho `UPDATE` obsahoval i starou prioritu. Žádná chyba, žádné varování, nic v logu.

**Poznáš to podle:**

- „někomu zmizely změny“ v hlášeních, které nejde reprodukovat
- data v databázi neodpovídají tomu, co si lidé pamatují, že uložili
- formulář, který uživatel vyplňuje pět minut, přepíše mezitím provedenou změnu
- dva workery zpracují tutéž entitu a jeden výsledek se ztratí
- souběžné importy nebo hromadné akce si přepisují výsledky

### Proč to databázová transakce nespraví

Nejčastější omyl. Transakce chrání **jeden krátký zápis**, ale tenhle problém vzniká jinde:

```
databázová transakce:   BEGIN … UPDATE … COMMIT        (milisekundy)
byznysová transakce:    GET /order/4711/edit
                        … uživatel pět minut přemýšlí …
                        POST /order/4711                (minuty)
```

**Přes ty dva requesty žádná databázová transakce nedrží** — a držet nemůže, protože bys blokoval spojení na celou dobu, co si někdo dává kávu. Přesně proto Fowler ten pattern pojmenoval **Offline Lock**: řeší souběh mimo databázovou transakci.

---

## Řešení

Dej záznamu **verzi**. Při zápisu ji ověř a zvyš:

```sql
UPDATE orders
   SET note = :note, priority = :priority, version = version + 1
 WHERE id = :id AND version = :version
```

A pak se podívej, **kolik řádků se změnilo**:

| Změněno řádků | Znamená |
| ------------- | ------- |
| **1** | Nikdo mezitím nezasáhl, hotovo |
| **0** | Verze nesedí — někdo byl rychlejší → **konflikt** |

```php
if ($statement->rowCount() === 0) {
    throw ConcurrentModification::of($order->number, $order->version, $this->currentVersion($order->number));
}
```

To je celý pattern. **Žádné zámky se nedrží, nic se neblokuje.** Konfliktům se nepředchází — jen se poznají. Proto „optimistický“: vychází z toho, že jsou vzácné, a platí se za ně až když nastanou.

```
Anna    načetla verzi 1
Bedřich načetl  verzi 1

Anna uloží    → v databázi je verze 2
Bedřich uloží → Objednávku OBJ-002 mezitím někdo změnil (očekávána verze 1, v databázi je 2).
```

### Detekce je jen půlka práce

Tohle je část, která se odbývá nejčastěji: pattern ti řekne, **že** je konflikt. Co s ním, musíš rozhodnout ty — a odpověď se liší operaci od operace.

| Reakce | Kdy | Poznámka |
| ------ | --- | -------- |
| **Zopakovat automaticky** | Operace nezávisí na tom, co uživatel viděl | Načti znovu, aplikuj změnu na čerstvý stav, ulož |
| **Ukázat konflikt uživateli** | Uživatel rozhodoval podle dat, která už neplatí | Nejčastější a nejpoctivější varianta |
| **Sloučit** | Změny se týkají různých polí a doména to dovolí | Drahé na napsání, milé pro uživatele |
| **Zahodit tu novější** | Skoro nikdy | Jen když je operace bezvýznamná |

Rozhodovací pravidlo:

> **Opakuj automaticky, když operace nezávisí na tom, co uživatel viděl. Když rozhodoval podle dat, která už neplatí, musí konflikt dostat na oči.**

| Operace | Opakovat? |
| ------- | --------- |
| „přičti 100 bodů“ | **Ano** — výsledek nezávisí na tom, co bylo |
| „změň stav na odeslaná“ | **Ano** — cíl je stejný |
| „nastav cenu na 500“ (viděl jsem 400) | **Ne** — rozhodoval podle starých dat |
| „schval slevu, kterou jsem viděl“ | **Ne** — musí se podívat znovu |

Automatické opakování v demu funguje takhle — a je podstatné, že **se změna aplikuje na čerstvě načtený stav**, ne na tu zastaralou kopii:

```php
while (true) {
    $fresh = $store->get($number);        // ← znovu, ne ta stará instance
    $fresh->changeNote($note);

    try {
        $store->save($fresh);
        break;
    } catch (ConcurrentModification) {
        // zkus to znovu
    }
}
```

Ke smyčce patří **strop na počet pokusů**. Bez něj se z ní při vysoké souběžnosti stane nekonečná.

### Verze patří na kořen agregátu

Praktické rozhodnutí s velkým dopadem: **verzi má [kořen agregátu](../../DDD/Aggregate/), ne každá entita zvlášť.**

```
Order            version   ← tady
  └ OrderItem              ← ne tady
  └ OrderItem              ← ani tady
```

Změna položky zvýší verzi objednávky. Kdyby měla verzi každá položka, mohli by dva lidé měnit dvě různé položky, oba by prošli — a **součet by přesáhl limit, aniž by kdokoli dostal konflikt**. Invariant celku by padl přesně v situaci, kvůli které agregát existuje.

Fowler pro to má vlastní pattern: **Coarse-Grained Lock** — jeden zámek na celek, ne na každou část.

### Verze, ne časové razítko

Jako verze se občas používá `updated_at`. Nedělej to:

| | Celé číslo | Časové razítko |
| --- | --- | --- |
| Roste vždy | **Ano** | Ne — čas se může vrátit (NTP, letní čas) |
| Rozlišení | Vždy stačí | Dvě změny ve stejné milisekundě se neliší |
| Napříč servery | Bez problému | Rozjeté hodiny = tiché chyby |
| Čitelnost | Nižší | Vyšší |

Ta poslední řádka je jediná výhoda razítka a nevyváží zbytek. **Když chceš obojí, měj obojí** — `version` na zamykání, `updated_at` pro lidi.

---

## Optimistické, nebo pesimistické?

Fowler popsal i protipól — **Pessimistic Offline Lock**: záznam se při otevření zamkne a nikdo jiný ho nesmí editovat, dokud ho první nepustí.

| | **Optimistické** | **Pesimistické** |
| --- | --- | --- |
| Přístup | Konflikt **poznat** | Konflikt **nepřipustit** |
| Kdy se to řeší | Při zápisu | Při načtení |
| Kdo čeká | Nikdo | Ostatní |
| Cena za běžný případ | **Žádná** | Zámek u každého otevření |
| Cena za konflikt | Práce se zahodí | Žádná |
| Riziko | Ztracená práce uživatele | **Uvíznuté zámky** — někdo zavře prohlížeč a záznam zůstane zamčený |
| Potřebuje navíc | Sloupec `version` | Tabulku zámků, expiraci, uklízeč |
| Vhodné pro | Souběh je vzácný | Souběh je běžný, konflikt drahý |

**Výchozí volba je optimistické.** Je levnější, nemá provozní režii a v drtivé většině aplikací jsou konflikty vzácné. Pesimistické se vyplatí, až když se ukáže, že si lidé pravidelně přepisují práci a přepsat půlhodinu editace je dražší než čekání.

A pozor na past pesimistického zamykání: **zámek si musíš uklidit sám.** Uživatel zavře prohlížeč, spadne worker, vyprší session — a záznam zůstane zamčený, dokud se někdo nedovtípí. Proto k němu vždycky patří expirace a možnost zámek násilím uvolnit.

---

## Účastníci

| Role | V příkladu | Odpovědnost |
| ---- | ---------- | ----------- |
| **Verzovaný záznam** | `Order` s `version` | Nese verzi, kterou nikdy sám nemění |
| **Úložiště** | `VersionedOrderStore` | Ověří verzi při zápisu, zvýší ji |
| **Konflikt** | `ConcurrentModification` | Výjimka nesoucí očekávanou i skutečnou verzi |
| **Volající** | use-case | Rozhodne, jestli opakovat, nebo předat uživateli |

---

## Implementace v PHP

```php
public function save(Order $order): void
{
    $statement = $this->connection->prepare(
        'UPDATE orders_versioned
            SET note = :n, priority = :p, version = version + 1
          WHERE cislo = :c AND version = :v',
    );

    $statement->execute([
        'c' => $order->number,
        'n' => $order->note(),
        'p' => $order->priority(),
        'v' => $order->version,
    ]);

    if ($statement->rowCount() === 0) {
        throw ConcurrentModification::of($order->number, $order->version, $this->currentVersion($order->number));
    }
}
```

Dvě věci, na kterých to celé stojí a snadno se pokazí:

1. **`AND version = :v` v `WHERE`** — bez toho se `UPDATE` prostě provede.
2. **Kontrola `rowCount()`** — bez ní se konflikt tiše ztratí, protože `UPDATE`, který nic nezměnil, není chyba.

### Doctrine to umí za tebe

```php
#[ORM\Version]
#[ORM\Column(type: 'integer')]
private int $version;
```

Doctrine pak sama přidá podmínku do `UPDATE` a při nesouladu vyhodí `OptimisticLockException`. Umí i explicitní zamknutí při načtení:

```php
$order = $entityManager->find(Order::class, $id, LockMode::OPTIMISTIC, $expectedVersion);
```

Zbytek — tedy **co s konfliktem udělat** — zůstává na tobě. Doctrine ti řekne, že nastal; rozhodnutí ne.

### Verzi musí projít i klient

Detail, který se v praxi zapomíná: **verze musí doputovat až k uživateli a zpátky.** Jinak nemáš co porovnávat.

```html
<input type="hidden" name="version" value="{{ order.version }}">
```

U API totéž přes `ETag` a `If-Match` — a je to jeho standardní použití, ne exotika. Server odpoví `412 Precondition Failed`, když se verze rozešla.

---

## Kdy použít

- ✅ Záznam **edituje víc lidí** a editace trvá déle než jeden request.
- ✅ Ztracená změna by byla **problém**, ne kosmetická vada.
- ✅ Konflikty jsou **vzácné** — což je skoro vždycky.
- ✅ Souběžně běží workery nebo importy nad týmiž daty.
- ✅ Máš API, kde klienti posílají aktualizace nezávisle na sobě.

## Kdy nepoužít

- ❌ **Záznam mění jen jeden proces.** Není s čím kolidovat.
- ❌ **Data jsou append-only.** Nové řádky se nepřepisují.
- ❌ **Poslední zápis má vyhrát a je to v pořádku.** Někdy to tak fakt je — ale ať je to rozhodnutí, ne nedopatření.
- ❌ **Konflikty jsou běžné a práce drahá.** Tam je [pesimistické zamykání](#optimistické-nebo-pesimistické) poctivější než pravidelně zahazovat půlhodinu editace.
- ❌ **Souběh je uvnitř jedné krátké transakce.** Na to stačí databázová transakce a případně `SELECT … FOR UPDATE`.

---

## Časté chyby

| Chyba | Proč vadí | Jak správně |
| ----- | --------- | ----------- |
| **Chybí kontrola `rowCount()`** | `UPDATE`, který nic nezměnil, není chyba — konflikt se ztratí | Nula změněných řádků = výjimka |
| Verze se do formuláře nebo API nedostane | Není co porovnat; zámek je jen na papíře | Skryté pole, `ETag`/`If-Match` |
| Verze na každé entitě agregátu | Dva lidé změní dvě položky, oba projdou, invariant celku padne | Verze na [kořeni agregátu](../../DDD/Aggregate/) |
| `updated_at` místo čísla | Rozjeté hodiny a shodná razítka dělají tiché chyby | Celé číslo; razítko klidně navíc, ale pro lidi |
| Konflikt se jen zaloguje a pokračuje se | Ztracená aktualizace, jen s poznámkou v logu | Konflikt musí operaci zastavit |
| Automatické opakování u všeho | Přepíše rozhodnutí, které uživatel udělal podle starých dat | Opakuj jen operace nezávislé na viděném stavu |
| Opakovací smyčka bez stropu | Při vysoké souběžnosti se z ní stane nekonečná | Maximálně tři pokusy, pak předej výš |
| Změna se při opakování aplikuje na starou instanci | Zopakuješ konflikt donekonečna | Načti znovu a aplikuj na čerstvý stav |
| Hláška „došlo ke konfliktu, zkuste to znovu“ | Uživatel neví, co se změnilo, ani co přijde o práci | Ukaž, co se mezitím změnilo a kým |

---

## V praxi

- **Doctrine** — `#[ORM\Version]` na celočíselné vlastnosti, `OptimisticLockException` při konfliktu. Nejjednodušší cesta v PHP.
- **HTTP `ETag` + `If-Match`** — optimistické zamykání zabudované do protokolu. Server vrací `412 Precondition Failed`.
- **`UPDATE … WHERE` bez ORM** — pattern nepotřebuje nic víc než jednu podmínku navíc a kontrolu `rowCount()`.
- **Fronty a workery** — souběžné zpracování téže entity je nejčastější místo, kde se konflikt objeví bez uživatele.
- **Uživatelské rozhraní** — nejlepší hláška o konfliktu ukáže **co konkrétně** se změnilo, ne jen že se nepovedlo uložit.

---

## Související patterny

| Pattern | Vztah |
| ------- | ----- |
| [Aggregate](../../DDD/Aggregate/) | **Určuje, kam verze patří** — na kořen, ne na části. Agregát je jednotka souběžnosti. |
| **Pessimistic Offline Lock** (PoEAA) | Protipól: konfliktu předchází místo toho, aby ho poznal. [Srovnání výše](#optimistické-nebo-pesimistické). |
| **Coarse-Grained Lock** (PoEAA) | Fowlerovo jméno pro „jeden zámek na celý agregát“. |
| [Repository](../Repository/) | Místo, kde se verze kontroluje — `save()` buď projde, nebo vyhodí konflikt. |
| [Data Mapper](../DataMapper/) | Verze je sloupec navíc; mapper ji drží mimo doménový model. |
| [Saga](../../Architecture/Saga/) | Souběh **napříč službami** tohle neřeší. Tam jsou kompenzace. |
| [Entity](../../DDD/Entity/) | Verze není doménový pojem — je to technický údaj přilepený k entitě. |

---

## Vztah k principům

| Princip | Jak souvisí |
| ------- | ----------- |
| [Fail Fast](../../Principles/ObjectDesign.md#fail-fast) | Podstata patternu: tichá ztráta dat se změní v hlasitou výjimku. |
| [Invariant](../../Glossary.md#invariant) | Verze na kořeni agregátu chrání invarianty celku i při souběžných změnách. |
| [SRP](../../Principles/SOLID.md#single-responsibility-principle-srp) | Detekce konfliktu patří do persistence, rozhodnutí o reakci do aplikační vrstvy. |

---

## Demo

```bash
php PoEAA/OptimisticOfflineLock/demo/run.php
```

Nechá dva lidi načíst tutéž objednávku a každého změnit něco jiného. **Bez zámku jedna změna beze stopy zmizí**; se zámkem druhý zápis skončí konfliktem. Pak ukáže automatické opakování nad čerstvým stavem (obě změny se zachovají), tabulku kdy opakovat a kdy ne, a proč verze patří na kořen agregátu.

---

## Původ

|               |                                                   |
| ------------- | ------------------------------------------------- |
| **Zdroj**     | *Patterns of Enterprise Application Architecture*  |
| **Autor**     | Martin Fowler                                      |
| **Rok**       | 2002                                               |
| **Kategorie** | — (PoEAA kategorie nemá)                           |
| **Obtížnost** | ●●○○○                                              |

Fowler pattern zařadil do kapitoly o **offline souběžnosti** — mezi vzory, které řeší situace, kdy jedna byznysová operace přesahuje jednu databázovou transakci. To slovo *offline* v názvu se často vypouští, a je to škoda: nese celé vymezení. Uvnitř transakce si se souběhem poradí databáze sama; problém začíná tam, kde transakce skončí a uživatel pořád edituje.

Sám pattern je starší než kniha — v databázovém světě se optimistická kontrola souběžnosti používala od 80. let a **Kung a Robinson** ji popsali už v roce **1981**. Fowlerův přínos byl v tom, že ji přenesl z databáze do aplikační vrstvy a dal jí jméno, které říká, kdy ji potřebuješ.

Vedle ní popsal i **Pessimistic Offline Lock** a **Coarse-Grained Lock** — tři vzory, které se v praxi používají dohromady: optimistický zámek na hrubozrnné jednotce, pesimistický jen tam, kde by ztráta práce bolela.

---

## Zdroje

- Martin Fowler: *Patterns of Enterprise Application Architecture*, Addison-Wesley, 2002 — Optimistic Offline Lock, str. 416
- H. T. Kung, John T. Robinson: *On Optimistic Methods for Concurrency Control*, ACM TODS, 1981
- [Doctrine ORM: Transactions and Concurrency](https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/transactions-and-concurrency.html)
- [RFC 9110 — `If-Match`](https://www.rfc-editor.org/rfc/rfc9110#field.if-match)

---

<details>
<summary>Metadata patternu</summary>

```yaml
name: OptimisticOfflineLock
name_cs: Optimistické zamykání
category: —
source: PoEAA – Patterns of Enterprise Application Architecture
authors: Martin Fowler
year: 2002
difficulty: 2
tags: [souběžnost, ztracená aktualizace, verze, konflikt, persistence]
principles: [FailFast, SRP]
related: [Aggregate, PessimisticOfflineLock, CoarseGrainedLock, Repository, DataMapper, Saga, Entity]
status: done
```

</details>
