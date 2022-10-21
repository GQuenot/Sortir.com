<?php

namespace App\Utils;

use App\Repository\ParticipantRepository;
use Symfony\Component\Security\Core\Security;

class Filter
{
    public function __construct(private readonly Security $security, private readonly ParticipantRepository $participantRepository)
    {}

    public function buildFilters(array $filters)
    {
        $activeFilters = [];

        $user = $this->participantRepository->findOneBy(['email' => $this->security->getUser()->getUserIdentifier()]);

        foreach ($filters[0] as $filterKey => $filterValue) {

            switch ($filterKey) {

                // Select site
                case 'site':
                    if ($filterValue !== "") $activeFilters[] = 'a.site = ' . $filterValue;
                    break;

                // Name activity search
                case 'search':
                    if ($filterValue !== "") $activeFilters[] = "a.name LIKE '%" . $filterValue . "%'";
                    break;

                // Date activity
                case 'startDate':
                    if ($filterValue !== "") $activeFilters[] = "a.activityDate BETWEEN '" . $filterValue . "' AND '" . $filters[0]['endDate'] . "'";
                    break;

                // Sortie dont je suis l'organisateur/trice
                case 'isOrganizer':
                    $activeFilters[] = 'a.organizer = ' . $user->getId();
                    break;
            }
        }

        return $activeFilters;
    }
}