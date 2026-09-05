# Principy

Principy nejsou patterny. **Pattern je konkrétní řešení konkrétního problému; princip je měřítko, podle kterého se pozná, jestli je návrh dobrý.** Většina patternů v tomhle katalogu existuje proto, že řeší porušení nějakého principu — proto na ně patterny odkazují a nevysvětlují si je pokaždé znovu.

| Soubor | Co obsahuje | Stav |
| ------ | ----------- | ---- |
| [**Soudržnost a provázanost**](CohesionAndCoupling.md) | Měřítko, které je pod vším ostatním — [soudržnost](CohesionAndCoupling.md#stupnice-soudržnosti), [provázanost](CohesionAndCoupling.md#stupnice-provázanosti) a jejich stupnice | ✅ |
| [**SOLID**](SOLID.md) | Pět principů objektového návrhu — [SRP](SOLID.md#single-responsibility-principle-srp), [OCP](SOLID.md#openclosed-principle-ocp), [LSP](SOLID.md#liskov-substitution-principle-lsp), [ISP](SOLID.md#interface-segregation-principle-isp), [DIP](SOLID.md#dependency-inversion-principle-dip) | ✅ |
| [**Jednoduchost**](Simplicity.md) | Kolik kódu psát a kdy — [KISS](Simplicity.md#kiss--keep-it-simple), [YAGNI](Simplicity.md#yagni--you-arent-gonna-need-it), [DRY](Simplicity.md#dry--dont-repeat-yourself), [pravidlo tří](Simplicity.md#pravidlo-tří) | ✅ |
| [**Conwayův zákon**](ConwaysLaw.md) | Proč architektura kopíruje organizaci — a co s tím jde a nejde dělat | ✅ |
| [**Objektový návrh**](ObjectDesign.md) | Jak spolu objekty mluví — [Tell Don't Ask](ObjectDesign.md#tell-dont-ask), [Demeter](ObjectDesign.md#zákon-demeter-law-of-demeter), [kompozice před dědičností](ObjectDesign.md#kompozice-před-dědičností), [CQS](ObjectDesign.md#cqs--command-query-separation), [Fail Fast](ObjectDesign.md#fail-fast), [zviditelni implicitní](ObjectDesign.md#zviditelni-implicitní) | ✅ |

<sub>⬜ plánováno · 🚧 rozpracováno · ✅ hotovo</sub>

## Proč jsou seskupené, a ne po jednom souboru

Většina principů je na samostatný dokument příliš krátká, ale do rozcestníku se nevejdou — jedna věta z nich dělá slogan, ne návod. Řešení je stejné jako u SOLID: **jeden soubor na téma, jedna kotva na princip.** Odkazuje se pak vždycky na kotvu, ne na soubor.

Témata odpovídají na různé otázky a jedno z nich je pod těmi ostatními:

- **Soudržnost a provázanost** — **cíl**, ke kterému ostatní principy vedou. Nejstarší (1974) a nejobecnější: platí na funkce, třídy, moduly, služby i týmy.
- **SOLID** — jak rozdělit odpovědnosti mezi třídy
- **Jednoduchost** — kolik toho vůbec napsat a kdy
- **Objektový návrh** — jak spolu mají objekty mluvit
- **Conwayův zákon** — proč to všechno naráží na to, jak je uspořádaný tým

Ta hierarchie stojí za zapamatování: **soudržnost a provázanost jsou cíl, SOLID jsou taktiky, patterny jsou konkrétní řešení.** Když si nejsi jistý, jestli má nějaké pravidlo v konkrétní situaci smysl, ptej se o úroveň výš.

[Conwayův zákon](ConwaysLaw.md) v téhle hierarchii nestojí — **stojí vedle ní.** Není to pravidlo, podle kterého se navrhuje, ale pozorování o tom, co se s návrhem stane, ať se rozhodneš jakkoli. Vyplatí se ho znát dřív než ostatní, protože vysvětluje, proč se dobrý návrh někdy neujme.

## Odkazování z patternů

Každý pattern má sekci **Vztah k principům** s odkazem na konkrétní princip — vždy na kotvu, ne na celý dokument:

```markdown
| [OCP](../../../Principles/SOLID.md#openclosed-principle-ocp) | Nová varianta = nová třída, kontext se nemění. |
```

Princip se vysvětluje **jen tady**. V README patternu se na něj odkazuje jednou větou, která říká, jak spolu souvisejí — ne co princip znamená.
