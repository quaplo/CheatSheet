# Scrumban

> [← zpět na Procesy](../)

> **V jedné větě:** Označení pro spojení [Scrumu](../Scrum/) a [Kanbanu](../Kanban/) — původně ale mělo znamenat něco jiného a **žádný závazný popis neexistuje**.

> [!IMPORTANT]
> Na rozdíl od [Scrumu](../Scrum/) a [Kanbanu](../Kanban/) **nemá Scrumban žádný normativní dokument.** Neexistuje „Scrumban Guide", není co porušit a není podle čeho zkontrolovat, jestli to tým dělá správně. Autor termínu to říká sám: *„There is no canonical reference implementation of Scrumban."* Praktický důsledek: **když někdo řekne „děláme Scrumban", nevíš tím o jeho procesu nic** — musíš se doptat.

---

## Dva různé významy

Slovo se dnes používá jinak, než jak vzniklo. Obojí je legitimní, ale je potřeba je rozlišit.

| | **Původní význam** (Ladas, 2008) | **Dnešní běžné použití** |
| --- | --- | --- |
| Co to je | **cesta** ze Scrumu k něčemu dalšímu | **hybrid** Scrumu a Kanbanu |
| Cíl | průběžné dodávání a průběžné zlepšování | fungující kompromis mezi oběma |
| Je to sada praktik? | **ne** | v praxi ano, ale u každého jiná |
| Kde končí | u Lean produkce; Scrum se cestou postupně odbourá | zůstává natrvalo |

### Původní význam: přechodová strategie

Termín zavedl **Corey Ladas** v roce 2008 v eseji, ze které pak vznikla kniha *Scrumban: Essays on Kanban Systems for Lean Software Development*. Nemyslel jím metodu, ale **postup, jak se ze Scrumu posunout dál** — postupně uvolňovat pevnou kadenci místo skokové výměny procesu.

Sám to v poznámkách z roku **2021** upřesnil:

> „Scrumban is a deployment strategy for continuous delivery."

> „**Scrumban is not a set of practices.**"

> „There is no canonical reference implementation of Scrumban."

Myšlenka byla, že tým začne u Scrumu jako základu, přidá kanbanové praktiky, a jak se blíží k průběžnému dodávání, část Scrumu přestane potřebovat. Ladas k tomu dodává větu, která je jádrem celé úvahy:

> „Continuous improvement and continuous delivery are complementary practices."

Jinými slovy: **průběžné dodávání vytváří podmínky, ve kterých se dá průběžně zlepšovat.** Bez něj se zlepšování dělá naslepo.

### Dnešní použití: hybrid

Praxe si termín vzala jinak a Ladas to uznává jako platné. Scrum Alliance ho dnes popisuje jako

> „a hybrid approach to project and product management, combining the structure of scrum with the visualization of kanban"

a poznamenává, že vznikl

> „first coined in 2008 by Cory Ladas as a transitional method for teams moving from scrum to kanban."

Tenhle posun významu je stejný jev jako u [GitHub Flow](../../GitWorkflows/GitHubFlow/#původ), kde se z popisu vytratilo pravidlo, ze kterého celý model plynul. Rozdíl je v tom, že tady **autor ten posun sám komentoval a nechal ho být.**

---

## Co se v praxi bere odkud

Podle Scrum Alliance se obvykle přebírá tohle — ale s výhradou, že

> teams „implement it in different ways"

a že to **není pevný rámec**.

| Ze [Scrumu](../Scrum/) | Z [Kanbanu](../Kanban/) |
| --- | --- |
| Sprinty pevné délky (typicky 2–4 týdny) | Vizualizace práce na nástěnce |
| Události — planning, denní setkání, review, retrospektiva | **WIP limity** |
| Důraz na průběžné zlepšování strukturovanou reflexí | Plynulý tok práce |

A co je volitelné:

> Týmy si mohou ponechat odpovědnosti Scrumu (Scrum Master, Product Owner, Developeři), nebo „distribute responsibilities more evenly across the team". Některé drží pevné Sprinty, jiné „prefer a more fluid approach, pulling work as needed".

**Tady je vidět, proč se o Scrumbanu špatně mluví:** dva týmy, které oba říkají, že dělají Scrumban, můžou mít proces, který nemá skoro nic společného.

---

## Na co si dát pozor

Následující není kritika Scrumbanu, ale **důsledek toho, že nemá kánon** — a obě věty, o které se opírá, jsou citace z dokumentů obou rodičů.

[Průvodce Scrum](../Scrum/) říká o částečném zavedení:

> „I když je možné implementovat pouze části Scrumu, **výsledkem není Scrum**."

[Kanban Guide](../Kanban/) říká o kombinování:

> „one can and likely should add other principles, methodologies, and techniques to the Kanban system. Still, **the minimum set of practices, metrics, and the spirit of optimizing value must be preserved**."

Z toho plyne praktické vodítko. Kanban se přidávat **má** — počítá s tím a jen si klade podmínku, že si zachováš jeho minimum (tři praktiky a čtyři metriky). Scrum se naopak zavádí celý, jinak to Scrum není. **Proto je poctivější Scrumban popisovat jako „Kanban přidaný ke Scrumu" než jako „Scrum, ze kterého jsme něco ubrali".**

Riziko, kterému se tím vyhneš, je konkrétní: protože není podle čeho měřit správnost, může se pod hlavičkou Scrumbanu schovat vypuštění retrospektiv, rozvolnění Definice Hotovo nebo Sprint, který se prodlužuje podle potřeby. Ne proto, že by to Scrumban doporučoval — ale protože **nemá jak říct, že ne**.

Praktická obrana: **napište si, co u vás Scrumban znamená.** Konkrétně které události držíte, jaké máte WIP limity, co je Definice Hotovo a jak dlouhý je Sprint. Kanban to má jako povinnou součást ([Definition of Workflow](../Kanban/#definition-of-workflow)) a je to přesně ten dokument, který tady jinak chybí.

---

## Srovnání všech tří

| | [**Scrum**](../Scrum/) | [**Kanban**](../Kanban/) | **Scrumban** |
| --- | --- | --- | --- |
| Normativní dokument | **Průvodce Scrum 2020** | **Kanban Guide v2025.5** | **žádný** |
| Rytmus | Sprinty pevné délky | plynulý tok | podle dohody týmu |
| Role | tři předepsané | žádné předepsané | **volitelné** |
| Události | pět předepsaných | žádné předepsané | obvykle scrumové, ale volitelné |
| Regulace zátěže | závazek na Sprint | WIP limity | **obojí** |
| Metriky | pokrok k Cíli Sprintu | WIP, Throughput, Age, Cycle Time | podle dohody |
| Změna priorit | mimo Sprint | kdykoli | podle dohody |
| Jak poznáš, že to děláš správně | podle Průvodce | podle minimální sady praktik a metrik | **nijak** |

Poslední řádek je celý rozdíl. **Není to nedostatek, který někdo opomněl doplnit** — plyne to z toho, že Scrumban měl původně být cestou, ne cílem. Cesta se nedá standardizovat.

---

## Kdy to dává smysl

Vychází to z původního významu a z toho, co oba dokumenty připouštějí:

- ✅ **Máte Scrum a chcete zlepšit tok.** Přidat vizualizaci, WIP limity a metriky toku je přesně to, s čím Kanban počítá.
- ✅ **Přecházíte ze Scrumu ke Kanbanu** a nechcete to udělat skokem — to je Ladasův původní záměr.
- ✅ **Sprinty vám sedí, ale práce přichází i mimo ně** (podpora, incidenty) a potřebujete pro ni řízený tok.
- ❌ **Hledáte jméno pro to, že Scrum úplně nedržíte.** Tohle je nejčastější použití a nejméně užitečné — problém nezmizí tím, že se přejmenuje.
- ❌ **Chcete něco, co se dá nastudovat a zavést podle příručky.** Není podle čeho; zvolte [Scrum](../Scrum/) nebo [Kanban](../Kanban/) a odchylky si pojmenujte sami.

---

## Souvislost s naší prací

| Dokument | Souvislost |
| -------- | ---------- |
| [Scrum](../Scrum/) | Jeden z rodičů. Jeho Průvodce je jediný normativní text, který u Scrumbanu můžeš použít jako měřítko. |
| [Kanban](../Kanban/) | Druhý rodič a ten, který se k něčemu přidávat **má** — počítá s tím výslovně. |
| [Kanban → Definition of Workflow](../Kanban/#definition-of-workflow) | Dokument, který u Scrumbanu chybí nejvíc. Když si ho napíšete, většina nejasností zmizí. |
| [Agile Manifesto](../AgileManifesto/) | Společný myšlenkový základ obou rodičů. |
| [Code review](../CodeReview/) | Bývá součástí Definice Hotovo i sloupcem s WIP limitem — v obou podobách je stejně důležité. |
| [Trunk-Based Development](../../GitWorkflows/TrunkBasedDevelopment/) | „Průběžné dodávání", o kterém Ladas mluví, má na straně vývoje tuhle podobu. |

---

## Zdroje

- Corey Ladas: [*Remarks on the Original Scrumban Essay*](https://agilealliance.org/remarks-on-the-original-scrumban-essay/), Agile Alliance, 25. 8. 2021 — autorovo vlastní upřesnění
- Corey Ladas: *Scrumban: Essays on Kanban Systems for Lean Software Development*, Modus Cooperandi Press — původní esej z roku 2008
- Scrum Alliance: [*What is Scrumban?*](https://resources.scrumalliance.org/Article/scrumban)
- Ajay Reddy: *The Scrumban [R]Evolution*, Addison-Wesley, 2015 — nejrozsáhlejší knižní zpracování
- [Průvodce Scrum](https://scrumguides.org/) a [Kanban Guide](https://kanbanguides.org/english/) — normativní texty obou rodičů
