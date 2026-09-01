# Principy

Principy nejsou patterny. **Pattern je konkrétní řešení konkrétního problému; princip je měřítko, podle kterého se pozná, jestli je návrh dobrý.** Většina patternů v tomhle katalogu existuje proto, že řeší porušení nějakého principu — proto na ně patterny odkazují a nevysvětlují si je pokaždé znovu.

| Principy | O čem to je | Stav |
| -------- | ----------- | ---- |
| [**SOLID**](SOLID.md) | Pět principů objektového návrhu — SRP, OCP, LSP, ISP, DIP | ✅ |
| DRY | Každá znalost má v systému jediné vyjádření | ⬜ |
| KISS / YAGNI | Nejjednodušší řešení, které funguje; nedělej, co zatím nikdo nechce | ⬜ |
| Law of Demeter | Mluv jen se svými nejbližšími sousedy | ⬜ |
| Composition over Inheritance | Skládej objekty, neděď je | ⬜ |
| Tell, Don't Ask | Neptej se objektu na stav, řekni mu, co má udělat | ⬜ |

<sub>⬜ plánováno · 🚧 rozpracováno · ✅ hotovo</sub>

## Odkazování z patternů

Každý pattern má sekci **Vztah k principům** s odkazem na konkrétní princip — vždy na kotvu, ne na celý dokument:

```markdown
| [OCP](../../../Principles/SOLID.md#openclosed-principle-ocp) | Nová varianta = nová třída, kontext se nemění. |
```

Princip se vysvětluje **jen tady**. V README patternu se na něj odkazuje jednou větou, která říká, jak spolu souvisejí — ne co princip znamená.
