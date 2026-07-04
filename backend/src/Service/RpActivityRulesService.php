<?php

namespace App\Service;

use App\Entity\Character;
use App\Entity\RpActivity;

class RpActivityRulesService
{
    /**
     * @return string[]
     */
    public function getAllowedKinds(): array
    {
        return [RpActivity::KIND_EVENT, RpActivity::KIND_MISSION];
    }

    /**
     * @return string[]
     */
    public function getAllowedStatuses(): array
    {
        return [RpActivity::STATUS_DRAFT, RpActivity::STATUS_OPEN, RpActivity::STATUS_CLOSED];
    }

    public function validateActivityConsistency(RpActivity $activity): void
    {
        if (!in_array($activity->getKind(), $this->getAllowedKinds(), true)) {
            throw new \InvalidArgumentException('Type d’activité invalide');
        }

        if (!in_array($activity->getStatus(), $this->getAllowedStatuses(), true)) {
            throw new \InvalidArgumentException('Statut d’activité invalide');
        }

        if ($activity->getUniverse() === null) {
            throw new \InvalidArgumentException('Univers requis');
        }

        if ($activity->getKind() === RpActivity::KIND_MISSION && $activity->getFaction() === null) {
            throw new \InvalidArgumentException('Une mission doit être associée à une faction');
        }

        if (
            $activity->getFaction() !== null
            && $activity->getFaction()->getUniverse()?->getId() !== $activity->getUniverse()?->getId()
        ) {
            throw new \InvalidArgumentException('La faction doit appartenir au même univers que l’activité');
        }
    }

    public function canCharacterRegister(Character $character, RpActivity $activity): bool
    {
        if ($activity->getStatus() !== RpActivity::STATUS_OPEN) {
            return false;
        }

        $registrationEndAt = $activity->getRegistrationEndAt();
        if ($registrationEndAt instanceof \DateTimeImmutable && $registrationEndAt < new \DateTimeImmutable()) {
            return false;
        }

        if ($character->getUniverse()?->getId() !== $activity->getUniverse()?->getId()) {
            return false;
        }

        if ($activity->getKind() === RpActivity::KIND_MISSION && $activity->getFaction() !== null) {
            return $activity->getFaction()->getCharacters()->contains($character);
        }

        return true;
    }
}
