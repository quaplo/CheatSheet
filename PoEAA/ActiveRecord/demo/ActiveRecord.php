<?php

declare(strict_types=1);

/**
 * Minimální Active Record — jádro celého patternu na jedné stránce.
 *
 * Objekt odpovídá řádku tabulky a umí se sám najít, uložit a smazat.
 * Přesně tohle dělá Eloquent, jen s mnohem víc pohodlí okolo.
 *
 * Pozor na jednu vlastnost, která je pro pattern určující: spojení
 * do databáze je STATICKÉ. Objekt ho nedostane, sáhne si pro něj sám.
 * Odtud plyne pohodlí (`Product::find('X')` funguje odkudkoli)
 * i většina pozdějších potíží.
 */
abstract class ActiveRecord
{
    private static ?PDO $connection = null;

    /** @var array<string, mixed> hodnoty sloupců */
    protected array $attributes = [];

    private bool $exists = false;

    /** @var array<string, mixed> stav při načtení — kvůli počítání zápisů */
    private array $original = [];

    public static int $queryCount = 0;

    abstract protected static function table(): string;

    abstract protected static function primaryKey(): string;

    public static function useConnection(PDO $connection): void
    {
        self::$connection = $connection;
    }

    protected static function connection(): PDO
    {
        if (self::$connection === null) {
            throw new RuntimeException(
                'Active Record potřebuje spojení do databáze. Bez něj neexistuje ani prázdný objekt.',
            );
        }

        return self::$connection;
    }

    public function __get(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    public function __set(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }

    public function __isset(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    /** @param array<string, mixed> $attributes */
    public static function create(array $attributes): static
    {
        $record = new static();
        $record->attributes = $attributes;

        return $record;
    }

    public static function find(int|string $id): ?static
    {
        ++self::$queryCount;

        $statement = static::connection()->prepare(
            sprintf('SELECT * FROM %s WHERE %s = ?', static::table(), static::primaryKey()),
        );
        $statement->execute([$id]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : static::hydrate($row);
    }

    /** @return list<static> */
    public static function all(): array
    {
        ++self::$queryCount;

        $statement = static::connection()->query(sprintf('SELECT * FROM %s', static::table()));

        return array_map(
            static fn (array $row): static => static::hydrate($row),
            $statement->fetchAll(PDO::FETCH_ASSOC),
        );
    }

    /** @return list<static> */
    public static function where(string $column, mixed $value): array
    {
        ++self::$queryCount;

        $statement = static::connection()->prepare(
            sprintf('SELECT * FROM %s WHERE %s = ?', static::table(), $column),
        );
        $statement->execute([$value]);

        return array_map(
            static fn (array $row): static => static::hydrate($row),
            $statement->fetchAll(PDO::FETCH_ASSOC),
        );
    }

    public function save(): void
    {
        ++self::$queryCount;

        $columns = array_keys($this->attributes);

        if ($this->exists) {
            $assignments = implode(', ', array_map(static fn (string $c): string => "$c = :$c", $columns));

            $sql = sprintf(
                'UPDATE %s SET %s WHERE %s = :pk',
                static::table(),
                $assignments,
                static::primaryKey(),
            );

            $parameters = $this->attributes;
            $parameters['pk'] = $this->original[static::primaryKey()];
        } else {
            $sql = sprintf(
                'INSERT INTO %s (%s) VALUES (%s)',
                static::table(),
                implode(', ', $columns),
                implode(', ', array_map(static fn (string $c): string => ':' . $c, $columns)),
            );

            $parameters = $this->attributes;
        }

        static::connection()->prepare($sql)->execute($parameters);

        $this->exists = true;
        $this->original = $this->attributes;
    }

    public function delete(): void
    {
        ++self::$queryCount;

        static::connection()
            ->prepare(sprintf('DELETE FROM %s WHERE %s = ?', static::table(), static::primaryKey()))
            ->execute([$this->attributes[static::primaryKey()]]);

        $this->exists = false;
    }

    /** @param array<string, mixed> $row */
    protected static function hydrate(array $row): static
    {
        $record = new static();
        $record->attributes = $row;
        $record->original = $row;
        $record->exists = true;

        return $record;
    }

    public static function resetQueryCount(): void
    {
        self::$queryCount = 0;
    }
}
