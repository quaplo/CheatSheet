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
| Replace Conditional with Polymorphism | `switch` na typ, který se větví na víc místech | [Strategy](../../SoftwareDesign/GoF/Behavioral/Strategy/), [State](../../SoftwareDesign/GoF/Behavioral/State/) | ⬜ |
| Encapsulate Collection | veřejné pole a `array_map` nad ním na pěti místech | [First Class Collection](../../SoftwareDesign/ObjectCalisthenics/FirstClassCollection/) | ⬜ |
| Replace Primitive with Object | `string $email`, validace na třech místech | [Value Object](../../SoftwareDesign/DDD/ValueObject/) | ⬜ |
| Extract Class | třída, kterou nejde popsat jednou větou | [SRP](../../SoftwareDesign/Principles/SOLID.md#single-responsibility-principle-srp) | ⬜ |
| Replace Constructor with Factory Method | `new` s osmi parametry a validací kolem | [Factory](../../SoftwareDesign/DDD/Factory/) | ⬜ |

<sub>⬜ plánováno · 🚧 rozpracováno · ✅ hotovo</sub>

---

## Než začneš

**Testy jsou krok nula.** Refaktoring se od přepisu liší jedinou věcí — tím, že se chování nemění. Bez testů to nemáš jak vědět, takže to není refaktoring, ale změna s nadějí.

Když testy chybí, pořadí je: napsat test na současné chování → ověřit, že prochází → **teprve pak** měnit.

**Refaktoring a změna chování nikdy v jednom commitu.** V šumu přesunů se ztratí ta jedna řádka, na které záleží — a při [review](../../Processes/CodeReview/) to nikdo nenajde.
