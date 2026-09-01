# Šablony

Podklady pro přidání nového patternu. **Tahle složka se needituje při psaní patternů** — jen se z ní kopíruje.

## Jak přidat nový pattern

1. **Založ složku** podle původu patternu:
   - patří-li do sbírky s kategoriemi → `<Zdroj>/<Kategorie>/<PatternName>/`
     (např. `GoF/Structural/Adapter/`)
   - nemá-li sbírka kategorie → `<Zdroj>/<PatternName>/`
     (např. `DDD/Aggregate/`)
   - Názvy složek **anglicky**, přesně podle původního názvu patternu (PascalCase).

2. **Zkopíruj šablonu:**
   ```bash
   cp _template/PATTERN.md <cesta>/README.md
   ```

3. **Vyplň** všechny sekce a **neměň jejich pořadí** — dokument jde od problému
   k řešení, referenční věci (Původ, Zdroje, Metadata) jsou schválně až na konci.
   Povinné jsou: Problém, Řešení, Účastníci, Implementace, Kdy použít / Kdy nepoužít,
   Časté chyby, Související patterny, Vztah k principům, Původ.
   Nepovinné nevyplněné sekce **smaž**, nenechávej prázdné nadpisy.

4. **Demo** (jen když je implementace na víc než ~60 řádků nebo má víc spolupracujících tříd):
   ```bash
   cp -r _template/demo <cesta>/demo
   ```
   Demo musí být spustitelné bez závislostí: `php <cesta>/demo/run.php`.

5. **Aktualizuj nadřazené dokumenty** — bez tohohle kroku je pattern „neviditelný“:
   - [ ] `README.md` v kořeni → tabulka patternů **pod popisem sbírky** v sekci *Sbírky*
   - [ ] `README.md` v kořeni → index **Podle problému**
   - [ ] `<Zdroj>/README.md` → seznam patternů
   - [ ] `<Zdroj>/<Kategorie>/README.md` → seznam patternů (pokud kategorie existuje)
   - [ ] Sekce **Související patterny** u patternů, na které nový pattern odkazuje
         (odkazy dělej obousměrné)
   - [ ] Sekce **Vztah k principům** — odkazy na kotvy v [`Principles/SOLID.md`](../Principles/SOLID.md),
         princip se tam **nevysvětluje**, jen se řekne jednou větou, jak souvisí

## Konvence

| Co | Jak |
| -- | --- |
| Text, popisy, komentáře v kódu | **česky** |
| Názvy tříd, metod, proměnných, souborů, složek | **anglicky** |
| PHP | 8.3+, `declare(strict_types=1)`, bez frameworků |
| Doménový příklad | pokud to jde, **e-shop / objednávky** — juniorům odpadá přepínání kontextu |
| Diagramy | Mermaid přímo v Markdownu (GitHub je vykreslí), žádné obrázky |
| Obtížnost | `●○○○○` až `●●●●●` — jak náročné je pattern správně použít, ne pochopit |
