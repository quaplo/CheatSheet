# GoF — Creational

> [← zpět na GoF](../)

**Vzory vytváření** řeší, jak objekty vznikají — a hlavně jak oddělit kód, který objekt *používá*, od kódu, který ví, jaká konkrétní třída se má vytvořit. Bez toho se `new ConcreteClass()` rozleze po celé aplikaci a jakákoli změna implementace znamená zásah na deseti místech.

V moderním PHP část téhle práce přebírá **DI kontejner**. U každého patternu proto řešíme, jestli ho ještě potřebuješ psát ručně, nebo ti ho framework dává zadarmo.

## Patterny

| Pattern | K čemu | Obtížnost | Stav |
| ------- | ------ | --------- | ---- |
| Abstract Factory | Rodiny souvisejících objektů bez vazby na konkrétní třídy | | ⬜ |
| Builder | Postupné sestavení složitého objektu | | ⬜ |
| [**Factory Method**](FactoryMethod/) | Pojmenované konstruktory + vytvoření delegované na potomka | ●○○○○ | ✅ |
| Prototype | Nový objekt klonováním existujícího | | ⬜ |
| Singleton | Jediná instance v aplikaci | | ⬜ |

<sub>⬜ plánováno · 🚧 rozpracováno · ✅ hotovo</sub>
