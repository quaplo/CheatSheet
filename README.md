# CheatSheet — týmová příručka

Interní dokumentace věcí, které se v týmu opakovaně vysvětlují. **Juniorům** slouží jako výuka od problému k řešení, **seniorům** jako rychlé připomenutí toho, co funguje.

Není to knihovna ani balíček — je to **dokumentace se spustitelnými příklady**. Obsah přibývá postupně, podle toho, co zrovna potřebujeme vysvětlit. Repozitář proto nikdy není „hotový“ a nekompletnost není chyba.

---

## Sekce

| Sekce | Co obsahuje | Stav |
| ----- | ----------- | ---- |
| [**Software Design**](SoftwareDesign/) | Návrhové vzory a architektura — GoF, PoEAA, DDD, principy návrhu. Spustitelné PHP ukázky u každého vzoru. | ✅ 34 vzorů |
| [**Git Workflows**](GitWorkflows/) | Modely větvení — jak tým pracuje s větvemi, kdy co slučuje a odkud nasazuje. | ✅ 5 workflow |
| [**Procesy**](Processes/) | Jak u nás probíhá práce — code review, a dál podle toho, co je potřeba vysvětlovat. | 🚧 1 proces |

<sub>Další sekce přibudou. Návod, jak založit novou, je [níž](#přidání-nové-sekce).</sub>

---

## Proč to takhle

Každý dokument v repozitáři drží stejný tvar, protože se tím pozná, jestli je hotový:

- **Vysvětluje se od problému, ne od definice.** Junior musí nejdřív poznat situaci ve vlastní práci — teprve pak dává řešení smysl.
- **„Kdy to nepoužít“ je stejně důležité jako „jak to udělat“.** Bez toho se nový nástroj cpe všude.
- **Tvrzení se podkládají, ne odhadují.** Když někde stojí, že je něco pomalé nebo drahé, patří k tomu měření.
- **Píše se konkrétně.** Skutečné knihovny, skutečné příklady, žádné obecné fráze.

---

## Přidání nové sekce

Sekce je složka v kořeni repozitáře s vlastním `README.md` jako rozcestníkem.

1. Založ složku a v ní `README.md` — co sekce obsahuje, pro koho je a rozcestník na jednotlivé dokumenty.
2. Založ `<sekce>/CLAUDE.md` s pravidly, jak se v ní píše — tvar dokumentu, checklist, na co si dát pozor. Podle něj se řídí i Claude Code, když v té složce pracuje.
3. Zvaž vlastní `_template/` — šablona a checklist jsou to, co drží kvalitu, když dokumentů přibývá.
4. Přidej řádek do tabulky [Sekce](#sekce) výš.

Pravidla společná pro celý repozitář jsou v [`CLAUDE.md`](CLAUDE.md).

---

## Konvence

- **Obsah česky** — texty, popisy, vysvětlení, komentáře v kódu.
- **Kód anglicky** — názvy tříd, metod, proměnných, složek a souborů.
- **Dokumenty se prolinkovávají.** Pojem se vysvětluje na jednom místě a odjinud se na něj odkazuje.
- **Ukázky musí jít spustit.** Bez frameworků a bez závislostí, ať je lze zkopírovat a vyzkoušet.
