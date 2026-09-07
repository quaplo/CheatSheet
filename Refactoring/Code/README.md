# Refaktoring kódu

> [← zpět na Refaktoring](../)

Změny v jednom procesu, které **nemění chování**. Trvají minuty až hodiny, jistí je testy a mezistav nikdo zvenčí nevidí.

---

## Co tady je a co ne

Kompletní katalog refaktoringů vede [Martin Fowler](https://refactoring.com/catalog/), je online zadarmo a nemá smysl ho přepisovat. **Vybíráme jen ty, které vedou k některému ze vzorů v [`SoftwareDesign/`](../../SoftwareDesign/)** — a přidáváme k nim to, co ve Fowlerově katalogu není: PHP, náš doménový příklad a odkaz na cíl.

Každý dokument proto končí větou „a teď si přečti \<vzor\>". **Refaktoring je cesta, vzor je cíl.**

---

## Katalog

| Refaktoring | Poznáš podle | Kam vede | Stav |
| ----------- | ------------ | -------- | ---- |
| [**Replace Conditional with Polymorphism**](ReplaceConditionalWithPolymorphism/) | tentýž `switch` na typ na víc místech | [Strategy](../../SoftwareDesign/GoF/Behavioral/Strategy/), [State](../../SoftwareDesign/GoF/Behavioral/State/) | ✅ |
| [**Encapsulate Collection**](EncapsulateCollection/) | `array_map` nad týmž polem na pěti místech; pravidla skupiny nemají kde být | [First Class Collection](../../SoftwareDesign/ObjectCalisthenics/FirstClassCollection/) | ✅ |
| [**Replace Primitive with Object**](ReplacePrimitiveWithObject/) | tatáž hodnota se validuje na třech místech, pokaždé jinak | [Value Object](../../SoftwareDesign/DDD/ValueObject/) | ✅ |
| [**Extract Class**](ExtractClass/) | třída, kterou nejde popsat jednou větou bez „a taky“ | [SRP](../../SoftwareDesign/Principles/SOLID.md#single-responsibility-principle-srp) | ✅ |
| [**Replace Constructor with Factory Method**](ReplaceConstructorWithFactoryMethod/) | `new` s bool parametry a nully; z volání nepoznáš, co se děje | [Factory](../../SoftwareDesign/DDD/Factory/) | ✅ |

<sub>⬜ plánováno · 🚧 rozpracováno · ✅ hotovo</sub>

---

## Než začneš

**Testy jsou krok nula.** Refaktoring se od přepisu liší jedinou věcí — tím, že se chování nemění. Bez testů to nemáš jak vědět, takže to není refaktoring, ale změna s nadějí.

Když testy chybí, pořadí je: napsat test na současné chování → ověřit, že prochází → **teprve pak** měnit.

**Refaktoring a změna chování nikdy v jednom commitu.** V šumu přesunů se ztratí ta jedna řádka, na které záleží — a při [review](../../Processes/CodeReview/) to nikdo nenajde.
