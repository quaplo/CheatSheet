# CLAUDE.md — sekce Procesy

Pravidla pro tuhle sekci. Společná pravidla celého repozitáře jsou v [kořenovém `CLAUDE.md`](../CLAUDE.md).

## O sekci

Popisy týmových postupů — code review, onboarding, incidenty, vydávání. Cílem je, aby junior věděl, **co se od něj čeká a proč**, v momentě, kdy do procesu poprvé vstupuje.

## Čím se tahle sekce liší od ostatních

U [vzorů](../SoftwareDesign/) a [modelů větvení](../GitWorkflows/) se vybírá mezi variantami. **Tady se popisuje, jak něco funguje** — a to má dva důsledky pro psaní:

- **Hrozí, že dokument sklouzne k názorům.** Proto: kde existuje veřejně ověřitelný podklad (výzkum, příručka velké firmy, zavedená konvence), opři se o něj a odkaž na něj. Kde není, napiš to nahlas — „na tohle neznám podklad, je to zvyklost“ je poctivější než tón autority.
- **Hrozí, že se do dokumentace dostanou pravidla, na kterých se tým nedohodl.** Popisuj **možnosti a jejich cenu**, ne „u nás platí“. Konkrétní nastavení (počet schvalovatelů, lhůty) patří do samostatné, výslovně odsouhlasené části.

## Struktura

Cesty níž jsou relativní k `Processes/`.

```
README.md                      # rozcestník procesů
CLAUDE.md                      # tenhle soubor
<ProcessName>/
    README.md                  # popis procesu; u rozsáhlejšího i rozcestník
    <Role>/README.md           # u rozsáhlého procesu členění podle rolí
```

Složka se jmenuje **anglicky a bez mezer** (`CodeReview`, `Onboarding`, `Incident`).

**U rozsáhlejšího procesu členěj podle rolí, ne podle fází.** Autor a recenzent čtou v jiný moment a hledají jinou věc; člověk, který zrovna něco dělá, nemá listovat přes to, co se ho netýká.

## Tvar dokumentu

Šablona zatím není — vznikne s druhým procesem, až bude vidět, co mají společného. Do té doby drž tenhle tvar:

1. Nadpis + odkaz zpět + shrnutí *V jedné větě*
2. **K čemu proces je** — a čím se pozná, že nefunguje
3. **Jak probíhá** — kroky, role, kdo za co odpovídá
4. **Co se od tebe čeká** — konkrétně, podle role čtenáře
5. **Časté chyby** — s následkem, ne jen se zákazem
6. **Nastavení v nástrojích**, pokud proces něco vynucuje
7. **Související** — odkazy na navazující procesy a dokumenty
8. **Zdroje** — podklady, o které se dokument opírá

## Psaní obsahu

Platí [pravidla psaní pro celý repozitář](../CLAUDE.md#psaní-obsahu--platí-v-celém-repozitáři). Navíc pro tuhle sekci:

- **Odděl doložené od zvyklosti.** Když se opíráš o výzkum nebo příručku, cituj a odkaž. Když ne, přiznej to. Čtenář musí poznat, co je měřené a co je názor.
- **Čísla uváděj s kontextem, ne jako pravidlo.** „Studie z roku 2006 naměřila…“ je něco jiného než „PR nesmí mít víc než 400 řádků“. To první se dá vzít v úvahu, to druhé vyvolá hádku o výjimkách.
- **Piš v druhé osobě, když popisuješ, co má člověk dělat.** Proces se čte v momentě, kdy ho někdo koná.
- **Popisuj chování, ne povahové vlastnosti.** „Komentář bez odůvodnění“ je konkrétní věc, kterou lze změnit; „nekonstruktivní recenzent“ není.
- **U každého pravidla napiš, co se stane, když se poruší.** Bez následku je to zákaz, s následkem poučení.
- **Nepiš nic o konkrétních lidech ani o konkrétních minulých případech.** Dokument čte i ten, o kom by to bylo.
