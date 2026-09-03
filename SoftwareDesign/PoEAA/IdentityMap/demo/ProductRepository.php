<?php

declare(strict_types=1);

/**
 * Repository BEZ Identity Map — každé načtení vyrobí novou instanci.
 */
final class ProductRepositoryWithoutMap
{
    public function __construct(private readonly ProductStorage $storage)
    {
    }

    public function find(string $sku): Product
    {
        $row = $this->storage->fetchRow($sku);

        return new Product($sku, $row['name'], $row['price']);
    }

    public function save(Product $product): void
    {
        $this->storage->update($product);
    }
}

/**
 * Repository S Identity Map — druhé načtení vrátí tutéž instanci.
 *
 * Pozor na pořadí: do mapy se objekt vkládá HNED po vytvoření,
 * ještě před načtením vazeb. Jinak by cyklická vazba (produkt →
 * kategorie → produkt) skončila nekonečnou rekurzí.
 */
final class ProductRepository
{
    public function __construct(
        private readonly ProductStorage $storage,
        private readonly IdentityMap $identityMap,
    ) {
    }

    public function find(string $sku): Product
    {
        $known = $this->identityMap->get(Product::class, $sku);

        if ($known instanceof Product) {
            return $known;
        }

        $row = $this->storage->fetchRow($sku);
        $product = new Product($sku, $row['name'], $row['price']);

        $this->identityMap->add(Product::class, $sku, $product);

        return $product;
    }

    public function save(Product $product): void
    {
        $this->storage->update($product);
    }
}
