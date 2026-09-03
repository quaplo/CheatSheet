<?php

declare(strict_types=1);

/**
 * Článek řetězu.
 *
 * Klíčové jsou dvě věci:
 *
 *  1. handle() je `final`. Potomek rozhoduje jen o tom, JESTLI umí žádost
 *     vyřídit — ne o tom, jak se řetěz prochází. Kdyby si každý článek
 *     řídil předávání sám, jeden zapomenutý `return` řetěz tiše utne.
 *
 *  2. Konec řetězu je ošetřený. Původní GoF popis připouští, že žádost
 *     nikdo nevyřídí; v praxi je to ta nejčastější chyba, protože se to
 *     projeví až u zákazníka. Tady se z toho stane výslovné zamítnutí.
 */
abstract class Approver
{
    private ?self $next = null;

    public static function chain(self ...$approvers): self
    {
        if ($approvers === []) {
            throw new InvalidArgumentException('Řetěz musí mít alespoň jeden článek.');
        }

        for ($i = 0; $i < count($approvers) - 1; $i++) {
            $approvers[$i]->next = $approvers[$i + 1];
        }

        return $approvers[0];
    }

    final public function handle(ApprovalRequest $request): ApprovalDecision
    {
        if ($this->canApprove($request)) {
            return ApprovalDecision::approvedBy($this->name());
        }

        $decision = $this->next?->handle($request)
            ?? ApprovalDecision::rejected('Žádný schvalovatel nemá dostatečnou pravomoc.');

        return $decision->afterConsulting($this->name());
    }

    abstract public function name(): string;

    abstract protected function canApprove(ApprovalRequest $request): bool;
}
