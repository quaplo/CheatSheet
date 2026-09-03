# Slovníček — práce s Gitem

> [← zpět na Git Workflows](../)

Pojmy, které se opakují ve víc workflow a nemají vlastní dokument. Vysvětlené jednou, odkazuje se na ně odjinud.

---

### Merge

Sloučení dvou větví. Git najde jejich společného předka, spojí změny a vytvoří **nový commit se dvěma rodiči** — takzvaný *merge commit*.

```bash
git switch main
git merge feature/dph-ve-fakturach
```

Podstatné je, že **merge nic nepřepisuje**. Původní commity zůstávají tak, jak byly, a v historii je vidět, že větev existovala a kdy se vrátila. Za to platíš tím, že se historie větví a při pohledu na `git log` je hůř čitelná.

---

### Fast-forward

Zvláštní případ merge: když se `main` od odbočení větve **vůbec nezměnil**, není co slučovat. Git jen posune ukazatel `main` dopředu na poslední commit větve a **žádný merge commit nevznikne**.

```
před:   main → A → B        feature → C → D
po:     main ────────────────────────→ D
```

Výsledkem je rovná historie bez odboček. Někdy je to žádoucí, jindy se ztratí informace, že tam nějaká větev byla — proto jde vynutit obojí (`--ff-only`, `--no-ff`).

---

### Rebase

Přesazení větve na jiný základ. Git vezme commity tvé větve, **odloží je stranou**, posune větev na aktuální konec `main` a commity na ni vyskládá znovu.

```bash
git switch feature/dph-ve-fakturach
git rebase main
```

Výsledek vypadá, jako bys začal pracovat až dnes. Historie je rovná a čitelná, ale za cenu, která se často podceňuje: **commity dostanou nové identifikátory** — jsou to jiné commity, jen se stejným obsahem.

> [!WARNING]
> **Nerebasuj větev, kterou už někdo jiný používá.** Když přepíšeš historii větve, kterou má kolega stažené, jeho Git ji nedokáže spojit s tou tvojí a vznikne z toho zmatek, který se řeší ručně. Pravidlo: rebase na vlastní větvi ano, na sdílené ne.

---

### Squash

Sloučení víc commitů do jednoho. Používá se při merge pull requestu: z pěti commitů typu „oprava překlepu“ a „ještě jednou“ zůstane v `main` **jeden commit na celý úkol**.

| | Zachová se | Historie `main` | Revert |
| --- | --- | --- | --- |
| **Merge commit** | všechny commity větve | větví se | vrací merge |
| **Squash** | jen výsledek | rovná, jeden commit na úkol | vrací jeden commit |
| **Rebase merge** | commity, ale přepsané | rovná, všechny commity | po jednom |

Squash se hodí u **krátkých větví**, kde postup práce nemá hodnotu. U dlouhé větve by se jím zahodilo něco užitečného — ale dlouhé větve jsou samy o sobě problém.

---

### Protected branch

Nastavení v GitHubu nebo GitLabu, které **zakáže přímý zápis do větve**. Do chráněné větve se dá dostat jen přes pull request, který splnil podmínky — prošlé testy, schválení od recenzenta, aktuálnost vůči cílové větvi.

Bez tohohle nastavení je jakýkoli workflow **jen dohoda**, kterou někdo dřív nebo později poruší — obvykle v pátek večer a bez zlého úmyslu.

---

### Feature flag

Přepínač v kódu, kterým se nedokončená funkce **skryje před uživatelem, i když je nasazená**.

```php
if ($features->enabled('nova-fakturace')) {
    return $this->novaFakturace->render();
}

return $this->staraFakturace->render();
```

Tím se rozpojí dvě věci, které spolu jinak souvisí: **nasazení** (kód je v produkci) a **vydání** (uživatel to vidí). Díky tomu může velký úkol putovat do hlavní větve po částech, aniž by na něj kdokoli musel čekat — a nemusí kvůli němu žít týdenní větev.

Cena: každý přepínač je větvení navíc, které se musí testovat v obou stavech. **Flag, který se po vydání nesmaže, je technický dluh** — a po roce jich bývá padesát a nikdo neví, které se ještě používají.

---

### Trunk

Hlavní vývojová větev — `main`, dřív `master`, v jiných systémech `trunk`. Označení pochází ze staršího názvosloví (*trunk* = kmen, *branch* = větev) a drží se hlavně v názvu [**Trunk-Based Development**](../).

---

### Tag (značka)

Pojmenovaný ukazatel na konkrétní commit, typicky vydanou verzi:

```bash
git tag -a v1.4.0 -m "Fakturace s DPH"
git push origin v1.4.0
```

Na rozdíl od větve se **nehýbe**. Značka `v1.4.0` bude ukazovat na tentýž commit i za tři roky, což z ní dělá spolehlivý způsob, jak se vrátit k tomu, co bylo vydáno.

---

### Sémantické verzování (semver)

Konvence číslování verzí ve tvaru `MAJOR.MINOR.PATCH`:

| Část | Zvyšuje se, když | Příklad |
| ---- | ---------------- | ------- |
| **MAJOR** | změna rozbije zpětnou kompatibilitu | `2.0.0` |
| **MINOR** | přibude funkce, ale staré volání funguje dál | `1.5.0` |
| **PATCH** | oprava chyby beze změny chování | `1.4.2` |

Má smysl tam, kde na tvé verzi někdo závisí — knihovny, API, instalovaný software. **U interní webové aplikace, která se nasazuje třikrát denně, je to obvykle zbytečné**; datum nebo číslo sestavení řekne víc.

---

### Cherry-pick

Přenesení **jednoho konkrétního commitu** z jedné větve do druhé:

```bash
git switch release/2.3
git cherry-pick 9a1f3d2
```

Typické použití: oprava vznikla na hlavní větvi a musí se dostat i do starší podporované verze. Vzniká tím **kopie commitu s jiným identifikátorem**, takže Git obě větve nadále považuje za rozdílné — a při každém dalším slučování to musí někdo hlídat.

Pravidelný cherry-pick je signál, že model větvení neodpovídá tomu, jak tým ve skutečnosti dodává.

---

### Code freeze (zmrazení kódu)

Období před vydáním, kdy se do vydávané větve **smějí dostat už jen opravy**, ne nové funkce. Účel je dát testování pevný cíl: kdyby během něj přibývaly změny, testovalo by se pokaždé něco jiného.

Má smysl tam, kde mezi „hotovo“ a „u zákazníka“ existuje testovací cyklus — typicky u [GitFlow](GitFlow/), kde tuhle roli plní `release/*` větev. Modely s průběžným nasazováním ho nemají a [Trunk-Based Development](TrunkBasedDevelopment/) ho výslovně nedoporučuje.

Za pozor stojí, že zmrazení **nezastaví práci** — vývoj pokračuje do vývojové větve a půjde v příštím vydání. Zmrazená je jen ta jedna větev, ze které se vydává.

---

### Merge queue (fronta na sloučení)

Nastavení, které u velkých týmů řeší situaci, kdy dva pull requesty projdou testy samostatně, ale **dohromady se rozbijí**. Místo okamžitého sloučení se změny zařadí do fronty, kde se otestují v tom pořadí, ve kterém se budou slučovat — a do hlavní větve se dostanou, jen když projdou i takhle.

Vyplatí se od desítek sloučení denně. U menších týmů je to režie, kterou vyváží prostě to, že se rozbitý stav rychle opraví.
