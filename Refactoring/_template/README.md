# Šablona — jak přidat techniku

> [← zpět na Refaktoring](../)

## Postup

1. **Rozhodni úroveň.** Běží to v jednom procesu → [`Code/`](../Code/). Běží to za provozu a musí jít vrátit → [`System/`](../System/).
2. **Založ složku** pojmenovanou anglicky podle zavedeného názvu.
3. **Zkopíruj šablonu:**
   ```bash
   cp _template/REFACTORING.md <Úroveň>/<Název>/README.md
   ```
4. **Vyplň ji** — pořadí sekcí neměň, komentáře `<!-- … -->` po vyplnění smaž.
5. **Projdi checklist** níž.

---

## Na co si dát pozor

**Mechanika je jádro, ne dodatek.** Čtenář sem chodí pro postup. Popis cíle si přečte u vzoru; tady chce vědět, co udělat jako první a jak pozná, že může pokračovat.

**U každého kroku napiš, co po něm platí.** Bez toho nikdo nepozná, kdy je bezpečné jít dál — a refaktoring se buď nedokončí, nebo se udělá naráz a rozbije se.

**Každý krok musí být bezpečný sám o sobě.** Když technika má mezistav, ve kterém nic nefunguje, není to refaktoring, ale přepis s nadějí. Takový mezistav je hlavní nevýhoda techniky a patří do *Co to stojí*.

**Napiš, kde se dá zastavit.** Velká část refaktoringů se nedokončí — a je rozdíl mezi tím zůstat v kroku 3, který je sám o sobě zlepšením, a uvíznout v kroku 2, který zhoršil všechno.

**Testy jsou krok nula.** Kde nejsou, doplní se dřív, než se něco změní. Dokument to musí říct nahlas, ne předpokládat.

**Piš i cenu.** Abstrakce navíc, dvojí implementace, půl roku ošklivějšího kódu. Technika bez uvedené ceny vypadá lákavěji, než je — a pak se zavede tam, kam nepatří.

---

## Checklist

- [ ] Správná úroveň — `Code/` (jeden proces) vs. `System/` (za provozu)
- [ ] *Kdy po tom sáhnout* popisuje **situaci**, ne definici
- [ ] *Předtím* obsahuje **kód**, ne jen popis
- [ ] Mechanika má **očíslované kroky**
- [ ] U každého kroku je napsané, **co po něm platí**
- [ ] Je uvedené, **kde se dá zastavit**
- [ ] **`System/`:** vyplněná sekce *Průběh a návratová cesta* včetně toho, **jak se vrátit**
- [ ] Sekce *Jak ověřit* říká konkrétně co, ne „napiš testy"
- [ ] *Co to stojí* je vyplněné poctivě
- [ ] *Kdy to nedělat* obsahuje aspoň dva reálné důvody
- [ ] **`Code/`:** dokument končí odkazem na cílový vzor
- [ ] **`Code/`:** cílový vzor má **zpětný odkaz** — zmínku, jak se k němu dá dojít
- [ ] **[`README.md`](../README.md) sekce** → tabulka doplněná, stav `⬜` → `✅`
- [ ] **[`Code/README.md`](../Code/) nebo [`System/README.md`](../System/)** → totéž, včetně náročnosti
- [ ] *Související* — odkazy **obousměrné**
- [ ] Na nehotové techniky odkazuješ **tučným textem, ne odkazem**
- [ ] Demo (je-li) **spuštěné a ověřené**
- [ ] Metadata v `<details>` **na konci souboru**
