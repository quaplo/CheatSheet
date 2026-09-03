# Nástroje

> [← zpět na rozcestník](../)

Nástroje a konvence kolem vývoje — to, co není ani návrhový vzor, ani proces, ale co si každý musí nastavit, aby mohl začít pracovat.

**Pro koho:** juniorům jako vysvětlení, proč u nás věci vypadají tak, jak vypadají; celému týmu jako podklad, když se nastavení mění.

---

## Nástroje

| Nástroj | K čemu | Stav |
| ------- | ------ | ---- |
| [**Makefile**](Makefile/) | Jednotný vstupní bod do projektu — `make test` místo dlouhého docker příkazu | ✅ |
| Docker Compose | Jak je poskládané vývojové prostředí | ⬜ |
| Statická analýza | PHPStan, jeho úrovně a jak s ním začít na starém projektu | ⬜ |
| CI pipeline | Co běží při každém pushi a proč | ⬜ |

<sub>⬜ plánováno · 🚧 rozpracováno · ✅ hotovo</sub>

---

## Čím se tyhle dokumenty řídí

**Ukázky musí jít spustit.** Konfigurace, kterou nikdo nevyzkoušel, je hypotéza. U každého nástroje je funkční příklad a způsob, jak ho ověřit.

**Píše se o nástroji, ne o naší konfiguraci.** Konkrétní nastavení projektu se mění rychleji než dokumentace a patří do repozitáře projektu. Tady je to, co je potřeba pochopit, aby se v tom nastavení dalo vyznat.

**Zmiňuje se, kdy nástroj nepotřebuješ.** Nejlevnější nástroj je ten, který sis nemusel pořídit.
