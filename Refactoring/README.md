# Refaktoring

> [← zpět na rozcestník](../)

Jak změnit kód, který už běží — a nerozbít ho při tom. Od přepsání jedné metody až po výměnu celé části systému za provozu.

**Pro koho:** juniorům jako postup krok za krokem v situaci, kdy „to prostě přepíšu" není možné; celému týmu jako společný slovník, když se plánuje velká změna.

---

## Dvě úrovně, dvě různé věci

Slovo „refaktoring" pokrývá dvě věci, které mají společný cíl a skoro nic jiného:

| | [**Kód**](Code/) | [**Systém**](System/) |
| --- | --- | --- |
| Trvá | minuty až hodiny | týdny až měsíce |
| Kde běží | v jednom procesu | **za provozu, na produkci** |
| Co jistí správnost | testy | testy **a návratová cesta** |
| Mezistav vidí | nikdo | **zákazník** |
| Hlavní otázka | jak to přepsat bezpečně | **jak se vrátit, když to nevyjde** |
| Kdo o tom rozhoduje | vývojář | tým, někdy i byznys |

**Nepleť si je.** Postup, který funguje na jednu metodu, se nedá použít na výměnu platební brány — a naopak zavádět kolem `Extract Method` návratovou cestu je zbytečná ceremonie.

---

## Než začneš

Pravidlo, které platí ve všech technikách níž, a dokumenty o tom, **kdy** se refaktoring do práce dostává — dohromady pokrývají [sedm workflow](TddRefactoring/#sedm-workflow-a-kde-je-najdeš), která popsal Fowler.

| Technika | K čemu | Náročnost | Stav |
| -------- | ------ | --------- | ---- |
| [**Dva klobouky**](TwoHats/) | Pravidlo nad všemi ostatními: buď funkce, nebo refaktoring | ●●○○○ | ✅ |
| [**Boy scout rule**](BoyScoutRule/) | Norma týmu: odejdi ze souboru čistší, než jsi přišel | ●○○○○ | ✅ |
| [**TDD refaktoring**](TddRefactoring/) | Třetí krok cyklu červená–zelená–refaktor, kde se dělá návrh | ●●○○○ | ✅ |
| [**Přípravný refaktoring**](PreparatoryRefactoring/) | Kdy a proč refaktorovat — a proč ve dvou commitech | ●●○○○ | ✅ |
| [**Charakterizační testy**](CharacterizationTests/) | Síť, která zapíše, co kód **dělá** — ne co má dělat | ●●○○○ | ✅ |
| [**Comprehension refactoring**](ComprehensionRefactoring/) | Jak porozumět cizímu kódu — a nechat to pochopení v něm | ●●○○○ | ✅ |
| [**Litter-pickup refactoring**](LitterPickupRefactoring/) | Co s nepořádkem, na který narazíš cestou za něčím jiným | ●○○○○ | ✅ |
| [**Plánovaný refaktoring**](PlannedRefactoring/) | Úklid, na který je vyhrazený čas — a proč je to zároveň špatná zpráva | ●●●○○ | ✅ |
| [**Dlouhodobý refaktoring**](LongTermRefactoring/) | Přestavba na měsíce, dělaná v hlavní větvi mezi běžnou prací | ●●●●○ | ✅ |
| [**Mikado metoda**](MikadoMethod/) | Jak najít cestu velkou přestavbou — pokus, chyba, revert, graf | ●●●○○ | ✅ |

---

## Refaktoring kódu

Změny v jednom procesu, které nemění chování. Vybíráme ty, které **vedou k některému ze vzorů**, co už v katalogu jsou — dokument tedy končí odkazem „a teď si přečti Strategy".

| Refaktoring | Kam vede | Stav |
| ----------- | -------- | ---- |
| [**Decompose Conditional**](Code/DecomposeConditional/) | [Specification](../SoftwareDesign/DDD/Specification/), [Replace Conditional with Polymorphism](Code/ReplaceConditionalWithPolymorphism/) | ✅ |
| [**Replace Conditional with Polymorphism**](Code/ReplaceConditionalWithPolymorphism/) | [Strategy](../SoftwareDesign/GoF/Behavioral/Strategy/), [State](../SoftwareDesign/GoF/Behavioral/State/) | ✅ |
| [**Encapsulate Collection**](Code/EncapsulateCollection/) | [First Class Collection](../SoftwareDesign/ObjectCalisthenics/FirstClassCollection/) | ✅ |
| [**Replace Primitive with Object**](Code/ReplacePrimitiveWithObject/) | [Value Object](../SoftwareDesign/DDD/ValueObject/) | ✅ |
| [**Extract Class**](Code/ExtractClass/) | [SRP](../SoftwareDesign/Principles/SOLID.md#single-responsibility-principle-srp), [Segregated Core](../SoftwareDesign/DDD/SegregatedCore/) | ✅ |
| [**Replace Constructor with Factory Method**](Code/ReplaceConstructorWithFactoryMethod/) | [Factory](../SoftwareDesign/DDD/Factory/), [Factory Method](../SoftwareDesign/GoF/Creational/FactoryMethod/) | ✅ |

<sub>Kompletní katalog refaktoringů kódu vede [Martin Fowler](https://refactoring.com/catalog/) a je online zadarmo. Tady je nepřepisujeme — přidáváme k nim to, co v něm není: cestu k vzoru a k našemu kontextu.</sub>

## Refaktoring systému

Změny, které běží za provozu a musí jít vrátit.

| Technika | K čemu | Náročnost | Stav |
| -------- | ------ | --------- | ---- |
| [**Branch by Abstraction**](System/BranchByAbstraction/) | Výměna implementace za běhu, přes abstrakci; obě verze existují vedle sebe | ●●●○○ | ✅ |
| [**Strangler Fig**](System/StranglerFig/) | Postupné obalení starého systému novým, dokud starý nezmizí | ●●●●○ | ✅ |
| [**Parallel Run**](System/ParallelRun/) | Obě verze běží současně a porovnávají se; nová nikdy neodpovídá | ●●○○○ | ✅ |
| [**Expand–Contract**](System/ExpandContract/) | Změna rozhraní nebo schématu bez výpadku, ve fázích | ●●●○○ | ✅ |

<sub>⬜ plánováno · 🚧 rozpracováno · ✅ hotovo</sub>

---

## Kdy vůbec refaktorovat

Refaktoring není hodnota sám o sobě — je to investice, která se musí vrátit. Čtyři situace, kdy se vyplatí, a jedna, kdy ne:

- ✅ **Chystáš se v tom místě něco měnit.** Nejlevnější okamžik: uklidíš to, čemu stejně musíš rozumět. To je [přípravný refaktoring](PreparatoryRefactoring/) a Kent Beck to shrnul jako *„make the change easy, then make the easy change"*.
- ✅ **Totéž místo tě zdrželo potřetí.** [Pravidlo tří](../SoftwareDesign/Principles/Simplicity.md#pravidlo-tří) platí i tady.
- ✅ **Nový člověk tomu nerozumí.** Kód, který nejde vysvětlit, se bude měnit špatně.
- ✅ **Chyba se vrátila podruhé na tomtéž místě.** Obvykle to není chyba, ale návrh.
- ❌ **„Uklidíme to, protože je to ošklivé."** Kód, kterého se nikdo nedotkne, může být ošklivý klidně dál. Refaktoring bez následné změny je práce bez protihodnoty.

---

## Čím se tyhle dokumenty řídí

**Mechanika je jádro dokumentu.** Ne popis cíle, ale kroky — a u každého to, co po něm musí platit. Refaktoring, u kterého není jasné, kdy je bezpečné přestat, se nedokončí.

**Každý krok musí být bezpečný sám o sobě.** Postup, který má mezistav „a teď hodinu nic nefunguje", není refaktoring, ale přepis s nadějí.

**Testy nejsou volitelné.** Refaktoring bez testů je změna chování, o které nevíš. Kde testy chybí, je jejich doplnění první krok — a dokument to musí říct.

**Piš i to, co technika stojí.** Abstrakce navíc, dvojí implementace, delší doba do dokončení. Bez toho vypadá každá technika lákavěji, než je.

---

## Přidání nové techniky

Postup, šablona a checklist: **[`_template/README.md`](_template/README.md)**.
