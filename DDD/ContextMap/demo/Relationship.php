<?php

declare(strict_types=1);

/**
 * Vztah dvou kontextů.
 *
 * Směr je vždycky od NADŘAZENÉHO (upstream) k PODŘÍZENÉMU (downstream).
 * Nadřazený je ten, jehož rozhodnutí toho druhého ovlivňují — ne ten,
 * odkud tečou data.
 */
final readonly class Relationship
{
    public function __construct(
        public string $upstream,
        public string $downstream,
        public RelationshipType $type,
        public string $note,
    ) {
    }
}
