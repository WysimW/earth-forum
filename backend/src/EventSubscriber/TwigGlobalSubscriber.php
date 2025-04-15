<?php

namespace App\EventSubscriber;

use App\Repository\UniversRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

class TwigGlobalSubscriber implements EventSubscriberInterface
{
    private $twig;
    private $universRepository;

    public function __construct(Environment $twig, UniversRepository $universRepository)
    {
        $this->twig = $twig;
        $this->universRepository = $universRepository;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        // Récupérer tous les univers
        $univers = $this->universRepository->findAll();
        
        // Ajouter les univers comme variable globale dans Twig
        $this->twig->addGlobal('all_univers', $univers);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
} 