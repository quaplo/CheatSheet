<?php

declare(strict_types=1);

/**
 * Značka jádra — druhá forma zvýrazněného jádra podle Evanse:
 * „Flag the elements of the core domain within the primary repository
 * of the model, without particularly trying to elucidate its role."
 *
 * Záměrně nic nedělá. Jejím jediným úkolem je, aby šlo na jeden
 * pohled poznat, co do jádra patří — a aby se to dalo vypsat.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class CoreDomain
{
    public function __construct(
        /** Krátce proč — ne co třída dělá, ale proč je klíčová. */
        public string $why,
    ) {
    }
}
