# CheatSheet — týmová příručka

Interní dokumentace věcí, které se v týmu opakovaně vysvětlují. **Juniorům** slouží jako výuka od problému k řešení, **seniorům** jako rychlé připomenutí toho, co funguje.

Není to knihovna ani balíček — je to **dokumentace se spustitelnými příklady**. Obsah přibývá postupně, podle toho, co zrovna potřebujeme vysvětlit. Repozitář proto nikdy není „hotový“ a nekompletnost není chyba.

---

## Sekce

| Sekce | Co obsahuje | Stav |
| ----- | ----------- | ---- |
| [**Software Design**](SoftwareDesign/) | Návrhové vzory a architektura — GoF, PoEAA, DDD, principy návrhu. Spustitelné PHP ukázky u každého vzoru. | ✅ 47 vzorů · 6 souborů principů |
| [**Git Workflows**](GitWorkflows/) | Modely větvení — jak tým pracuje s větvemi, kdy co slučuje a odkud nasazuje. | ✅ 5 workflow |
| [**Procesy**](Processes/) | Jak u nás probíhá práce — code review, a dál podle toho, co je potřeba vysvětlovat. | 🚧 7 dokumentů |
| [**Refaktoring**](Refactoring/) | Jak měnit kód, který už běží — od jedné metody po výměnu části systému za provozu. | ✅ 23 technik |
| [**Nástroje**](Tooling/) | Nástroje a konvence kolem vývoje — Makefile, Docker, statická analýza. | 🚧 1 nástroj |

<sub>Další sekce přibudou. Návod, jak založit novou, je [níž](#přidání-nové-sekce). Hledáš-li spíš inspiraci než odpověď, začni u [Myšlenek, ke kterým se vracím](#myšlenky-ke-kterým-se-vracím).</sub>

---

## Myšlenky, ke kterým se vracím

Osobní výběr — věci, které mě zrovna nejvíc baví a které stojí za přečtení, i když zrovna neřešíš problém, na který odpovídají. **Seznam poroste.** Každá z nich je pořádně rozebraná jinde v repozitáři, tady je jen důvod, proč tam jít.

### Luneta na potápěčských hodinkách

Dá se otáčet **jen jedním směrem** a předepisuje to norma. Když o ni potápěč zavadí, může ukázat jedině **víc** uplynulého času — takže vyplave dřív, než by musel. Opačný směr by znamenal zůstat pod hladinou déle, než stačí vzduch, a to se vrátit nedá.

Je v tom celý princip, který se v kódu skoro nikdy nenavrhuje vědomě: **chybu v jednom směru znemožnit a v druhém ji naklonit tak, aby nebolela.** Nespoléhá se na to, že si někdo dá pozor — pozornost selže vždycky, stačí dost pokusů.

→ [Poka-yoke](SoftwareDesign/Principles/ObjectDesign.md#poka-yoke--znemožni-chybu-nebo-ji-nakloň)

### Jen jednu ze dvou složitostí jde odstranit

Ve tvém kódu jsou vedle sebe dva druhy složitosti. **Akcidentální** je z toho, jak jsme to zrovna napsali — zmizí s jiným nástrojem, typem, rozvržením. **Esenciální** je z domény a nezmizí nikdy, leda by někdo zrušil požadavek.

Rozdíl není akademický, protože určuje, **kdo o tom rozhoduje**: o té první tým, o té druhé byznys. A vysvětluje, proč „přepíšeme to jednodušeji" tak často skončí u stejně velkého systému. Zkouška, která na to funguje: *zůstalo by tohle, kdyby ten systém psal někdo dokonalý v dokonalém jazyce?*

→ [Esenciální a akcidentální složitost](SoftwareDesign/Principles/Simplicity.md#esenciální-a-akcidentální-složitost)

### Vysoká soudržnost, nízká provázanost

Co spolu souvisí, ať je pohromadě; co spolu nesouvisí, ať na sobě nezávisí. Zní to jako fráze, dokud si člověk nevšimne data: **1974**, Constantine a Yourdon — dvacet let před GoF a třicet před SOLID.

A tohle je na tom to zajímavé: **není to jedno z pravidel, je to cíl.** SOLID i většina vzorů v tomhle repozitáři jsou způsoby, jak toho dosáhnout. Když si u nějakého vzoru nejsi jistý, proč vlastně existuje, odpověď bude skoro vždycky tady.

→ [Soudržnost a provázanost](SoftwareDesign/Principles/CohesionAndCoupling.md)

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
