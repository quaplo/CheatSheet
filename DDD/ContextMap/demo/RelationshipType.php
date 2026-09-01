<?php

declare(strict_types=1);

/**
 * Katalog vztahů mezi kontexty podle Evanse.
 *
 * Není to seznam technologií. Každý z těch vztahů je odpověď na otázku
 * **kdo se komu musí přizpůsobit** — tedy otázka o moci a o vlastnictví
 * kontraktu, ne o tom, jestli se posílá JSON nebo zpráva do fronty.
 */
enum RelationshipType: string
{
    /** Dva týmy stojí a padají spolu; koordinují releasy. */
    case Partnership = 'Partnership';

    /** Sdílený kus modelu ve společném vlastnictví. Nejnebezpečnější vztah. */
    case SharedKernel = 'Shared Kernel';

    /** Nadřazený se plánovaně přizpůsobuje potřebám podřízeného. */
    case CustomerSupplier = 'Customer/Supplier';

    /** Podřízený přebírá cizí model tak, jak je — nemá páku na vyjednávání. */
    case Conformist = 'Conformist';

    /** Podřízený se aktivně brání překladem, aby cizí model nepronikl dovnitř. */
    case AnticorruptionLayer = 'Anticorruption Layer';

    /** Nadřazený publikuje obecný kontrakt pro mnoho konzumentů. */
    case OpenHostService = 'Open Host Service';

    /** Žádná integrace. Někdy je to správná odpověď. */
    case SeparateWays = 'Separate Ways';

    public function question(): string
    {
        return match ($this) {
            self::Partnership => 'Selžeme nebo uspějeme spolu?',
            self::SharedKernel => 'Kdo vlastní ten sdílený kus?',
            self::CustomerSupplier => 'Bude nám nadřazený tým vycházet vstříc?',
            self::Conformist => 'Unesu jejich model uvnitř svého?',
            self::AnticorruptionLayer => 'Kolik mě stojí bránit se?',
            self::OpenHostService => 'Mám dost konzumentů na to, abych publikoval kontrakt?',
            self::SeparateWays => 'Opravdu tu integraci potřebujeme?',
        };
    }
}
