# CLAUDE.md — sekce Nástroje

Pravidla pro tuhle sekci. Společná pravidla celého repozitáře jsou v [kořenovém `CLAUDE.md`](../CLAUDE.md).

## O sekci

Nástroje a konvence kolem vývoje — Makefile, Docker, statická analýza, CI. Věci, které nejsou návrhový vzor ani proces, ale bez kterých se nedá začít pracovat.

## Struktura

```
README.md                      # rozcestník
CLAUDE.md                      # tenhle soubor
<NastrojName>/
    README.md                  # popis nástroje
    demo/                      # funkční ukázka + způsob, jak ji spustit
```

## Tvar dokumentu

Šablona zatím není — vznikne, až bude vidět, co mají dokumenty společného. Do té doby drž tenhle tvar:

1. Nadpis + odkaz zpět + shrnutí *V jedné větě*
2. **K čemu to je** — jaký problém to řeší; poznávací znaky, že ho máš
3. **Minimum, které musíš znát** — jen to, bez čeho se to nedá používat
4. **Použití v našem prostředí** — u PHP projektu typicky přes Docker
5. **Užitečné příklady** — konkrétní, zkopírovatelné
6. **Časté chyby** — s následkem
7. **Kdy to nepotřebuješ** / alternativy
8. *Demo* — spustitelná ukázka
9. **Zdroje**

## Psaní obsahu

Platí [pravidla psaní pro celý repozitář](../CLAUDE.md#psaní-obsahu--platí-v-celém-repozitáři). Navíc pro tuhle sekci:

- **Ukázka musí jít spustit a musíš ji spustit.** Konfigurace, kterou nikdo nezkusil, je hypotéza. Když ukázka potřebuje běžící infrastrukturu, najdi způsob, jak ji ověřit bez ní (`make -n`, `--dry-run`, `docker compose config`).
- **Uveď verzi.** Nástroje se chovají různě podle verze a prostředí. Zvlášť pozor na macOS: **GNU Make je tam 3.81** (Apple ho kvůli licenci neaktualizuje), zatímco na Linuxu bývá 4.x — a některé funkce tam prostě nejsou.
- **Piš o nástroji, ne o naší konfiguraci.** Konkrétní nastavení se mění rychleji než dokumentace a patří do repozitáře projektu.
- **Vysvětli, co dělá každý přepínač**, který v ukázce použiješ. Zkopírovaný příkaz, kterému nikdo nerozumí, je horší než žádný.
- **Řekni, kdy nástroj nepotřebuješ.** Nejlevnější nástroj je ten, který sis nemusel pořídit.
- **Nezakrývej cizí nástroj vlastní vrstvou bez užitku.** Když obal jen přejmenovává existující příkaz, napiš to.
