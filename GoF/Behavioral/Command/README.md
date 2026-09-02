# Command (Příkaz)

> [← zpět na Behavioral](../)

> **V jedné větě:** Operace zabalená do objektu — a tím pádem něco, co jde předat dál, zařadit do fronty, zapsat do historie nebo vrátit zpět.

> [!IMPORTANT]
> **Slovo „command“ znamená v PHP světě dvě různé věci.** Tenhle dokument je o vzoru z GoF, kde příkaz **umí se provést sám**. Ve světě [CQRS](../../../Architecture/CQRS/) a command busů je command **jen data** a práci dělá handler. Rozdíl rozebírá sekce [Command v GoF a command v CQRS](#command-v-gof-a-command-v-cqrs) — a stojí za to si ho přečíst dřív než zbytek.

---

## Problém

Zavolat metodu je snadné. Problém nastane, když s tím voláním potřebuješ udělat ještě něco dalšího — a volání metody není věc, kterou bys mohl uchopit.

**Poznáš to podle:**

- uživatel chce **krok zpět** a nikde není zapsané, co se vlastně stalo
- operace se má provést **později** nebo **jiným procesem**, ale je zadrátovaná do metody
- pořadí operací se má dát **zopakovat** (log, audit, přehrání)
- `if`/`match` mapuje název akce na volání metody a s každou akcí roste
- tlačítko v UI zná konkrétní službu, konkrétní metodu i její parametry
- skupina operací se má provést jako **jeden celek** a jako celek i vrátit

```php
// Před: kdo operaci vyvolal, ví přesně, co se stane
match ($action) {
    'append'  => $document->append($text),
    'replace' => $document->replaceAll($search, $replacement),
    'export'  => $exporter->export($customerId, $format),
};
// …a teď k tomu doplň undo, frontu a audit
```

---

## Řešení

Udělej z operace **objekt**. Nese si příjemce i parametry, takže ho jde předat komukoli, kdo umí zavolat jedinou metodu.

```php
interface Command
{
    public function execute(): void;

    public function describe(): string;
}
```

**Podstatné je, že `execute()` nemá parametry.** Všechno, co operace potřebuje, si příkaz nese uvnitř. Právě proto s ním jde zacházet jako s daty.

```php
final class AppendText implements UndoableCommand
{
    public function __construct(
        private readonly TextDocument $document,
        private readonly string $text,
    ) {
    }

    public function execute(): void
    {
        $this->document->append($this->text);
    }

    public function undo(): void
    {
        $this->document->removeLast(mb_strlen($this->text));
    }
}
```

Spouštěč pak o operaci neví vůbec nic:

```php
final class History
{
    /** @var list<UndoableCommand> */
    private array $done = [];

    public function run(Command $command): void
    {
        $command->execute();

        if ($command instanceof UndoableCommand) {
            $this->done[] = $command;
        }
    }
}
```

`History` nezná text ani dokument. **Umí proto vrátit i operaci, která vznikne zítra** — a to je celý přínos.

### Co vrátit nejde, se nemá tvářit, že jde

Undo je nejznámější důvod pro Command, ale **ne každá operace jde vrátit**. Odeslaný e-mail zpátky nevezmeš, platbu u brány taky ne.

Řekni to typem, ne komentářem:

```php
interface UndoableCommand extends Command
{
    public function undo(): void;
}
```

```php
if ($command instanceof UndoableCommand) {
    $this->done[] = $command;
}
```

Příkaz, který vrátit nejde, se do zásobníku undo **vůbec nedostane**. Alternativa — `undo()` na všech příkazech, kde půlka hodí `LogicException` — je [porušení LSP](../../../Principles/SOLID.md#liskov-substitution-principle-lsp): typ slibuje něco, co implementace nesplní.

Praktická poznámka: u nevratných operací se v praxi nezavádí undo, ale **kompenzace** — nová operace, která efekt vyváží (dobropis místo zrušení platby). To už je téma pro [Sagu](../../../Architecture/Saga/).

### Dva způsoby, jak efekt vrátit

Demo ukazuje oba, protože volba mezi nimi je reálné rozhodnutí:

| | **Odečtení efektu** | **Uložený snímek** |
| --- | --- | --- |
| Jak | Příkaz ví, co přidal, a odečte to | Před provedením si uloží stav |
| Paměť | Minimální | Kopie dat na každý příkaz |
| Kdy funguje | Operace je **spolehlivě opačná** | Vždycky |
| Riziko | Když opačná není, tiše to rozbije data | Velký stav = velká paměť |

```php
// Odečtení: připsaný text stačí useknout
public function undo(): void
{
    $this->document->removeLast(mb_strlen($this->text));
}

// Snímek: zpětná záměna by přepsala i to, co v textu bylo předtím
public function execute(): void
{
    $this->contentBefore = $this->document->content();
    $this->document->replaceAll($this->search, $this->replacement);
}
```

**Výchozí volba je snímek**, protože je bezpečný. K odečtení sáhni, až když je operace prokazatelně reverzibilní a stav je velký. Vzoru s uloženým snímkem se říká **Memento** — je to samostatný vzor z GoF a s Command se potkává skoro vždycky.

### Makro: skupina příkazů jako jeden příkaz

Když `MacroCommand` sám implementuje `Command`, volající nepozná, jestli drží jednu operaci nebo dvacet. To je [Composite](../../Structural/Composite/) v učebnicové podobě:

```php
public function execute(): void
{
    foreach ($this->commands as $command) {
        $command->execute();
    }
}

public function undo(): void
{
    foreach (array_reverse($this->commands) as $command) {
        $command->undo();
    }
}
```

**`array_reverse` tam není náhodou.** Kroky na sobě závisí — poslední se musí vrátit první, jinak se odečítá z jiného stavu, než do kterého se přičítalo.

### Fronta: operace, kterou provede někdo jiný a jindy

Tohle je dnes v PHP **nejčastější použití Command** a málokdo mu tak říká. Když si příkaz nese všechna data, jde ho serializovat, uložit a provést v jiném procesu:

```php
$queue->push(new ExportOrders('alice', 'CSV'));
```

```
ve frontě:            3 příkazy (serializované)
worker zpracoval:     3
    ✓ export CSV pro alice
    ✓ export PDF pro bob
    ✓ export CSV pro carol
```

Worker nezná `ExportOrders` předem. Dostane objekt a zavolá `execute()`.

> [!WARNING]
> **Příkaz do fronty nesmí držet živé závislosti.** Připojení k databázi, mailer ani otevřený soubor serializaci nepřežijí. Do příkazu patří **identifikátory a hodnoty**, závislosti se hledají až při běhu. Tohle je nejčastější chyba při zavádění front.

Druhá past je čas. Mezi zařazením a provedením uběhne klidně hodina — a příkaz s celým objektem uvnitř provede práci nad **zastaralou kopií**. Proto se do fronty dává `customerId`, ne `Customer`.

### Command v GoF a command v CQRS

Obojí se jmenuje stejně, obojí je „operace jako objekt“ — a přesto jsou to dvě různé věci:

```
                           GoF Command           CQRS command
umí se provést sám         ano (execute)         ne
zná příjemce               ano                   ne
kdo dělá práci             příkaz sám            handler
výsledek                   undo/redo, fronta     objednávka pro alice za 1290 Kč
```

```php
// GoF: chování je uvnitř
$command = new AppendText($document, 'text');
$command->execute();

// CQRS: příkaz je jen data, práci dělá handler
final readonly class PlaceOrder
{
    public function __construct(
        public string $customerId,
        public int $totalInCents,
    ) {
    }
}

$bus->dispatch(new PlaceOrder('alice', 129000));
```

| | **GoF Command** | **CQRS command** |
| --- | --- | --- |
| Co to je | Objekt s chováním | [DTO](../../../Glossary.md#dto--data-transfer-object) bez chování |
| Závislosti | Nese si příjemce | Žádné — jen hodnoty |
| Kdo provede | Příkaz sám | Handler, kterého najde bus |
| Typický přínos | Undo, makro, historie | Oddělení záměru od provedení, jedno místo pro transakci a validaci |
| Kde je popsaný | Tenhle dokument | [CQRS](../../../Architecture/CQRS/), [Service Layer](../../../PoEAA/ServiceLayer/) |

**Která je „správná“?** Obě, na jiné úlohy. Chceš-li **undo v editoru nebo makro**, chceš GoF. Chceš-li **jedno místo, kudy tečou všechny zápisy v aplikaci**, chceš verzi s handlerem — příkaz bez závislostí se líp serializuje, testuje i posílá po síti.

V praxi to znamená, že když kolega řekne „pošli to přes command“, zeptej se, který myslí. Skoro vždycky ten druhý.

---

## Účastníci

| Role | V příkladu | Odpovědnost |
| ---- | ---------- | ----------- |
| **Command** | `Command`, `UndoableCommand` | Rozhraní operace — `execute()` bez parametrů |
| **Konkrétní příkaz** | `AppendText`, `ReplaceAll`, `ExportOrders` | Nese data i příjemce, provede operaci |
| **Příjemce (receiver)** | `TextDocument` | Umí skutečnou práci; o příkazech neví |
| **Spouštěč (invoker)** | `History`, `CommandQueue` | Spouští a eviduje; o obsahu operace neví nic |
| **Klient** | `run.php` | Sestaví příkaz a předá ho spouštěči |

Nejdůležitější je vztah **spouštěč ↔ příkaz**: `History` funguje pro každou operaci, protože nezná ani jednu.

---

## Implementace v PHP

### Uzavření (closure) místo třídy

Pro jednoduchou operaci bez undo je třída zbytečná — PHP má funkce jako hodnoty:

```php
$commands = [
    'export' => static fn () => $exporter->export($customerId, 'CSV'),
    'notify' => $notifier->send(...),   // first-class callable, PHP 8.1+
];

$commands[$action]();
```

| | **Closure** | **Třída** |
| --- | --- | --- |
| Kolik kódu | Jeden řádek | Soubor |
| Undo, popis, metadata | Nejde rozumně | Ano |
| Serializace do fronty | **Nejde** | Ano |
| Testovatelnost samostatně | Horší | Dobrá |
| Kdy | Jednorázová akce | Historie, fronta, audit |

**Sáhni po closure, dokud nepotřebuješ víc než `execute()`.** Ve chvíli, kdy chceš undo, popis do logu nebo frontu, závorky nestačí a přijde třída.

### Vrací příkaz hodnotu?

GoF má `execute(): void`. V praxi to často nestačí — chceš vědět, jaké ID vznikla objednávka.

| Přístup | Kdy |
| ------- | ---- |
| `void` a výsledek se zjistí dotazem | Čistá varianta, ladí s [CQS](../../../Principles/ObjectDesign.md#cqs--command-query-separation) |
| Návratová hodnota | Pragmatické; u handlerů běžné |
| Identita vzniká **před** operací | Nejlepší, když jde použít — viz `nextIdentity()` v [Repository](../../../PoEAA/Repository/) |

Třetí možnost je nejelegantnější: když si ID vyrobíš dopředu, `execute()` může zůstat `void` a volající přesto ví, co vzniklo.

### Co do příkazu nepatří

| Nepatří tam | Proč | Kam to patří |
| ----------- | ---- | ------------ |
| Připojení k databázi, mailer | Nepřežije serializaci | Injektovat při provedení |
| Celý entitní objekt | Za hodinu ve frontě je zastaralý | Identifikátor |
| Validace vstupu z formuláře | Příkaz už má být platný | Vrstva nad ním |
| Rozhodnutí, **jestli** operaci provést | Příkaz je záměr, ne rozhodovací logika | Volající, nebo [Specification](../../../DDD/Specification/) |

---

## Kdy použít

- ✅ **Undo/redo** — editor, formulář s koncepty, administrace.
- ✅ **Fronta úloh** — operace se má provést jinde a jindy.
- ✅ **Historie a audit** — co se stalo a v jakém pořadí.
- ✅ **Makro** — skupina kroků jako jedna operace, včetně vrácení celku.
- ✅ **Naplánované a opakované spouštění** — příkaz jde uložit a spustit znovu.
- ✅ **UI oddělené od logiky** — tlačítko drží příkaz, ne službu a název metody.

## Kdy nepoužít

- ❌ **Operaci stačí zavolat.** Bez undo, fronty a historie je příkaz jen třída navíc kolem jednoho volání.
- ❌ **Máš jednu akci a nic k ní nepřibude.** Zavolej metodu.
- ❌ **Chceš zaměnitelné algoritmy, ne evidované operace.** To je [Strategy](../Strategy/).
- ❌ **Chceš oznámit, že se něco stalo.** To je [Domain Event](../../../DDD/DomainEvent/) — příkaz je rozkaz do budoucna, událost je fakt z minulosti.
- ❌ **Undo přes víc služeb nebo systémů.** Zásobník příkazů to neuhlídá; potřebuješ [Sagu](../../../Architecture/Saga/) a kompenzace.

---

## Časté chyby

| Chyba | Proč vadí | Jak správně |
| ----- | --------- | ----------- |
| `undo()` na každém příkazu, půlka hodí výjimku | Typ slibuje, co neplatí — porušení LSP | Samostatné `UndoableCommand` |
| Příkaz do fronty drží připojení nebo mailer | Serializace selže, nebo projde a spadne u workera | Jen data; závislost až při běhu |
| Ve frontě je celý objekt místo ID | Za hodinu je to zastaralá kopie | `customerId`, načíst až u workera |
| `MacroCommand::undo()` v původním pořadí | Kroky na sobě závisí, stav se rozpadne | `array_reverse` |
| Příkaz sám rozhoduje, jestli se má provést | Míchá záměr a pravidlo, nejde znovu spustit | Rozhodnutí nad příkazem |
| `execute()` s parametry | Přestane jít předat a uložit — celý přínos zmizí | Parametry do konstruktoru |
| Command na každé volání metody | Dvojnásobek tříd bez užitku | Až když je undo, fronta nebo historie |
| Záměna s CQRS commandem při návrhu | Jedni čekají chování, druzí data | [Vyjasnit, který](#command-v-gof-a-command-v-cqrs) |
| Příkaz ve frontě není [idempotentní](../../../Glossary.md#idempotence) | Opakované doručení provede operaci dvakrát | Klíč operace a kontrola u příjemce |

---

## V praxi

- **Symfony Messenger** — `dispatch($message)` a handler. Verze bez chování, sdílená s CQRS.
- **Fronty úloh obecně** — serializovaný objekt s daty, který provede worker, je Command; jméno se nepoužívá, myšlenka ano.
- **Symfony Console** — třída příkazu s `execute()` je Command doslova, i s parametry.
- **Doctrine migrace** — `up()` a `down()` je Command s undo v nejčistší podobě.
- **Undo v editorech** — původní motivace GoF a dodnes nejnázornější příklad.
- **Textové editory a IDE** — zásobník příkazů je standardní způsob, jak se undo implementuje.

---

## Související patterny

| Pattern | Vztah |
| ------- | ----- |
| [CQRS](../../../Architecture/CQRS/) | **Nejčastější zdroj nedorozumění.** Tamní command je data bez chování, práci dělá handler. Viz [srovnání](#command-v-gof-a-command-v-cqrs). |
| [Service Layer](../../../PoEAA/ServiceLayer/) | Use-case s příkazem na vstupu je verze bez chování; místo, kde se v aplikaci s příkazy nejčastěji potkáš. |
| [Composite](../../Structural/Composite/) | `MacroCommand` **je** Composite — skupina příkazů se chová jako jeden. |
| **Memento** (GoF) | Uložený snímek stavu pro undo. Command říká *co se má vrátit*, Memento *na co*. |
| [Strategy](../Strategy/) | Struktura stejná, záměr jiný: Strategy vybírá **jak** něco spočítat, Command eviduje **co se má stát**. |
| [Chain of Responsibility](../ChainOfResponsibility/) | Command bus bývá řetěz middleware — validace, transakce, logování kolem provedení. |
| [Domain Event](../../../DDD/DomainEvent/) | Zrcadlový pár: příkaz je **rozkaz do budoucna** a smí být odmítnut, událost je **fakt z minulosti** a odmítnout nejde. |
| [Saga](../../../Architecture/Saga/) | Co dělat, když operaci přes víc služeb nejde vzít zpět: kompenzace místo undo. |
| [Iterator](../Iterator/) | Fronta příkazů se prochází jako každá jiná posloupnost. |
| [Unit of Work](../../../PoEAA/UnitOfWork/) | Také drží seznam operací a provede je najednou — jen odvozený ze změn objektů, ne sestavený volajícím. |

---

## Vztah k principům

| Princip | Jak souvisí |
| ------- | ----------- |
| [SRP](../../../Principles/SOLID.md#single-responsibility-principle-srp) | Spouštění a evidence patří spouštěči, obsah operace příkazu. |
| [OCP](../../../Principles/SOLID.md#openclosed-principle-ocp) | Nová operace = nová třída. `History` ani fronta se nemění. |
| [LSP](../../../Principles/SOLID.md#liskov-substitution-principle-lsp) | Proč `undo()` nepatří do společného rozhraní, když ho půlka příkazů nesplní. |
| [DIP](../../../Principles/SOLID.md#dependency-inversion-principle-dip) | Spouštěč závisí na rozhraní `Command`, ne na konkrétní operaci. |
| [CQS](../../../Principles/ObjectDesign.md#cqs--command-query-separation) | Odsud pochází samo slovo *command*: metoda buď mění stav, nebo vrací hodnotu. |
| [YAGNI](../../../Principles/Simplicity.md#yagni--you-arent-gonna-need-it) | Nejčastější důvod, proč Command nepsat: bez undo a fronty je to třída navíc. |

---

## Demo

```bash
php GoF/Behavioral/Command/demo/run.php
```

Sedm částí: operace jako objekt a spouštěč, který o ní nic neví; undo a redo nad historií; dva způsoby vrácení efektu vedle sebe (odečtení vs. snímek) a proč záměna vyžaduje ten druhý; nevratná operace, kterou typ do zásobníku undo nepustí; makro s vrácením celku v opačném pořadí; fronta, kde příkaz přežije serializaci a provede ho jiný proces; a nakonec srovnání GoF příkazu s tím z CQRS na stejné obrazovce.

---

## Původ

|               |                                                   |
| ------------- | ------------------------------------------------- |
| **Zdroj**     | *Design Patterns: Elements of Reusable Object-Oriented Software* |
| **Autoři**    | Gamma, Helm, Johnson, Vlissides („Gang of Four“)   |
| **Rok**       | 1994                                              |
| **Kategorie** | Behavioral                                        |
| **Obtížnost** | ●●○○○                                             |

GoF popsali Command jako **„požadavek zabalený do objektu“** a motivovali ho grafickými rozhraními: tlačítko a položka menu mají dělat totéž, aniž by kterékoli z nich vědělo, co ta akce obnáší. Undo přišlo jako důsledek — když je operace objekt, dá se jich držet seznam.

Dnešní hlavní použití je ale jinde. **Fronty úloh** stojí přesně na tom, že operace, která si nese svá data, jde uložit a provést jinde. Nikdo tomu neříká Command, ale je to on — a je to důvod, proč se v PHP s tímhle vzorem potkáš mnohem častěji než s undo.

Zajímavý je i osud toho jména. Slovo *command* v protikladu k *query* pochází od Bertranda Meyera ([CQS](../../../Principles/ObjectDesign.md#cqs--command-query-separation), 1988) a mluví o **metodách**, ne o objektech. Z něj později vyrostlo [CQRS](../../../Architecture/CQRS/) a s ním „command“ ve významu **datové zprávy**. Tři různé věci, jedno slovo — a proto se vyplatí u něj vždycky upřesnit, o kterou jde.

---

## Zdroje

- Gamma, Helm, Johnson, Vlissides: *Design Patterns*, Addison-Wesley, 1994 — kapitola 5, str. 233
- [Symfony Messenger](https://symfony.com/doc/current/messenger.html)
- [Symfony Console: vlastní příkaz](https://symfony.com/doc/current/console.html)
- Meyer, Bertrand: *Object-Oriented Software Construction*, 1988 — původ dvojice command/query

---

<details>
<summary>Metadata patternu</summary>

```yaml
name: Command
name_cs: Příkaz
category: Behavioral
source: GoF – Design Patterns
authors: Gamma, Helm, Johnson, Vlissides
year: 1994
difficulty: 2
tags: [operace jako objekt, undo, fronta, makro, historie]
principles: [SRP, OCP, LSP, DIP, CQS, YAGNI]
related: [CQRS, ServiceLayer, Composite, Memento, Strategy, ChainOfResponsibility, DomainEvent, Saga, Iterator, UnitOfWork]
status: done
```

</details>
