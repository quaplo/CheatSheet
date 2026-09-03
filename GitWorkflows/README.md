# Git Workflows — modely větvení

> [← zpět na rozcestník](../)

Jak tým pracuje s větvemi, kdy co slučuje a odkud nasazuje. **Žádný z těchhle modelů není ten správný** — každý je odpovědí na jinou situaci a liší se hlavně tím, co si vyžaduje.

**Pro koho:** juniorům jako vysvětlení, proč u nás vypadá práce s větvemi tak, jak vypadá; celému týmu jako podklad, když se model vybírá nebo mění.

---

## Jak vybrat

Tři otázky rozhodnou skoro všechno. Odpověz si na ně dřív, než začneš srovnávat diagramy:

**1. Kolik verzí máš současně v produkci?**
Když víc než jednu, potřebuješ větev, ze které se dá vydat oprava pro tu starší — a tím ti vypadne polovina modelů.

**2. Je mezi „hotovo“ a „v produkci“ testovací cyklus?**
Když ano, musí hotový kód někde počkat. To jsou release větve, nebo větve pro prostředí.

**3. Jak rychle umíš vrátit rozbité nasazení?**
Modely s krátkými větvemi stojí na tom, že se chyba najde a opraví rychle. Bez toho je jejich rychlost riziko, ne výhoda.

Metodika (agile, waterfall) je až důsledek. **Tým může dělat Scrum a vydávat jednou za dva měsíce** — a pak mu sedí model, který vypadá „neagilně“.

---

## Srovnání

| | [GitHub Flow](GitHubFlow/) | [GitFlow](GitFlow/) | [Trunk-Based](TrunkBasedDevelopment/) | [GitLab Flow](GitLabFlow/) | [OneFlow](OneFlow/) |
| --- | --- | --- | --- | --- | --- |
| Trvalých větví | 1 | 2 | 1 | 1 + prostředí | 1 |
| Jak dlouho žije větev | dny | **týdny** | **hodiny** | dny | dny |
| Release větve | ne | ano | jen k vydání | **jen v podobě B** | ano |
| Podpora víc verzí | ne | **ano** | ne | jen v podobě B | omezeně |
| Stabilizační fáze | ne | **ano** | ne | ano | ano |
| Vyžaduje CI | ano | doporučeno | **nutně** | ano | doporučeno |
| Vyžaduje feature flagy | doporučeno | ne | **nutně** | doporučeno | ne |
| Frekvence nasazení | denně až týdně | plánovaně | **několikrát denně** | podle prostředí | plánovaně |
| Provozní náročnost | ●●○○○ | ●●●●○ | ●●●○○ | ●●●○○ | ●●●○○ |

**Jak číst řádek „provozní náročnost“:** není to počet větví, ale kolik toho musí tým dodržet a jak snadno se to dá udělat tiše špatně. Trunk-Based má jednu větev a přesto trojku — protože bez zralé CI a feature flagů rozbije produkci.

---

## Katalog

| Workflow | Autor, rok | Čím se vyznačuje | Náročnost | Stav |
| -------- | ---------- | ---------------- | --------- | ---- |
| [**GitHub Flow**](GitHubFlow/) | Scott Chacon, 2011 | Jedna trvalá větev, krátké větve, nasazení po každém merge | ●●○○○ | ✅ |
| [**GitFlow**](GitFlow/) | Vincent Driessen, 2010 | Pět typů větví, oddělený vývoj a vydání; autor ho dnes pro web nedoporučuje | ●●●●○ | ✅ |
| [**Trunk-Based Development**](TrunkBasedDevelopment/) | Paul Hammant (sepsání), praxe starší | Integrace každý den; nedokončená práce za přepínačem, ne ve větvi | ●●●○○ | ✅ |
| [**GitLab Flow**](GitLabFlow/) | GitLab, 2014 | GitHub Flow doplněný o větve pro prostředí, nebo o release větve | ●●●○○ | ✅ |
| [**OneFlow**](OneFlow/) | Adam Ruka, 2017 | GitFlow bez `develop`; release a hotfix větve zůstávají | ●●●○○ | ✅ |

<sub>⬜ plánováno · 🚧 rozpracováno · ✅ hotovo</sub>

---

## Společné pojmy

Merge, rebase, squash, fast-forward, feature flag, protected branch a další věci, které se opakují napříč modely: **[`Glossary.md`](Glossary.md)**.

Vyplatí se ho přečíst dřív než jednotlivé workflow — rozdíl mezi [merge a rebase](Glossary.md#rebase) je věc, kterou každý model řeší, ale žádný nevysvětluje.

---

## Přidání nového workflow

Postup, šablona a checklist: **[`_template/README.md`](_template/README.md)**.
