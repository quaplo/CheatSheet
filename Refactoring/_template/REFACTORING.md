# <Název techniky>

> [← zpět na <Úroveň>](../)

> **V jedné větě:** <co technika dělá a čím se liší od prostého přepsání>

<!--
Volitelně upozornění, když se technika běžně plete s jinou
nebo má častý omyl:

> [!IMPORTANT]
> <…>
-->

---

## Kdy po tom sáhnout

<!-- Situace, ne definice. Čtenář má poznat vlastní projekt. -->

**Poznáš to podle:**

- <konkrétní symptom>
- <…>
- <…>

---

## Předtím

<!-- Stav, ze kterého se vychází. S kódem — ať je vidět, o čem se mluví. -->

```php
// <výchozí stav>
```

---

## Mechanika

<!--
JÁDRO DOKUMENTU. Očíslované kroky a u každého to, co po něm platí.
Každý krok musí být bezpečný sám o sobě a dát se zastavit.
-->

### 1. <Název kroku>

```php
// <kód po tomhle kroku>
```

**Po tomhle kroku platí:** <co je pravda; podle čeho pozná, že může pokračovat>

### 2. <Název kroku>

**Po tomhle kroku platí:** <…>

<!-- …a tak dál -->

> [!NOTE]
> **Kde se dá zastavit:** <které kroky jsou samy o sobě zlepšením, i kdyby se dál nepokračovalo>

---

## Průběh a návratová cesta

<!--
POVINNÉ v System/, nepovinné v Code/.

Jak dlouho technika běží, co vidí zákazník, a hlavně:
co se udělá, když se to nepovede. U velkých změn je tahle otázka
důležitější než „jak to udělat".
-->

| | |
| --- | --- |
| **Jak dlouho to trvá** | <dny / týdny / měsíce> |
| **Co vidí uživatel** | <nic / postupné přepínání / …> |
| **Jak se vrátit** | <konkrétně> |
| **Jak dlouho žije mezistav** | <…> |

---

## Jak ověřit, že to funguje

<!-- Bez tohohle je to změna chování, o které nevíš. -->

---

## Co to stojí

<!--
Poctivě. Abstrakce navíc, dvojí implementace, doba do dokončení,
kód, který je půl roku ošklivější než na začátku.
-->

| Cena | Kdy se vyplatí |
| ---- | -------------- |
| <…> | <…> |

---

## Kdy to nedělat

- ❌ <…> — <proč>
- ❌ <…> — <proč>

---

## Časté chyby

| Chyba | Proč vadí | Jak správně |
| ----- | --------- | ----------- |
| <…> | <konkrétní následek> | <…> |

---

## Kam to vede

<!--
POVINNÉ v Code/. Refaktoring je cesta, vzor je cíl.
Vzor se vysvětluje jen jednou — v SoftwareDesign/.
-->

Po dokončení máš [<Vzor>](../../../SoftwareDesign/<cesta>/). <Jedna věta, co tím získáš.>

---

## Demo

<!-- Nepovinné. -->

```bash
php Refactoring/<Úroveň>/<Název>/demo/run.php
```

---

## Související

| Dokument | Vztah |
| -------- | ----- |
| [<Název>](<cesta>) | <v čem se liší / kdy sáhnout po něm> |

---

## Původ

|             |                    |
| ----------- | ------------------ |
| **Autor**   | <…>                |
| **Rok**     | <…>                |
| **Zdroj**   | <…>                |
| **Náročnost** | ●●●○○            |

<!-- Kontext vzniku. Náročnost se měří rizikem a délkou, ne počtem kroků. -->

---

## Zdroje

- <…>

---

<details>
<summary>Metadata techniky</summary>

```yaml
name: <Název>
level: <code | system | příprava | pravidlo>
author: <…>
year: <…>
duration: <hodiny | dny | týdny | měsíce>
reversible: <ano | částečně | ne>
requires_tests: <ano | doporučeno>
difficulty: <1–5>
tags: []
leads_to: []
related: []
status: draft
```

</details>
