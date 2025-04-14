<?php

namespace App\Controller;

use App\Entity\Character;
use App\Entity\Npc;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/dialogue-style')]
class DialogueStyleController extends AbstractController
{
    #[Route('/character/{id}', name: 'app_dialogue_style_character', methods: ['GET', 'POST'])]
    public function editCharacterStyle(Request $request, Character $character, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur est bien le propriétaire du personnage
        if ($character->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier ce personnage');
        }

        // Traiter le formulaire de style
        if ($request->isMethod('POST')) {
            $styleData = [
                'nameColor' => $request->request->get('nameColor', '#2c3e50'),
                'nameBold' => $request->request->has('nameBold'),
                'nameItalic' => $request->request->has('nameItalic'),
                'textColor' => $request->request->get('textColor', '#6c757d'),
                'textItalic' => $request->request->has('textItalic'),
                'textBold' => $request->request->has('textBold'),
                'showName' => $request->request->has('showName'),
                'quoteType' => $request->request->get('quoteType', 'none'),
                'fontFamily' => $request->request->get('fontFamily', 'inherit'),
            ];
            
            // Débogage
            $this->addFlash('info', 'Police enregistrée: ' . $styleData['fontFamily']);
            
            $encodedStyle = json_encode($styleData);
            $character->setDialogueStyle($encodedStyle);
            $entityManager->flush();

            $this->addFlash('success', 'Le style de dialogue a été enregistré avec succès');
            return $this->redirectToRoute('app_roleplay_character_show', ['id' => $character->getId()]);
        }

        // Récupérer le style actuel ou définir un style par défaut
        $currentStyle = $character->getDialogueStyle() 
            ? json_decode($character->getDialogueStyle(), true) 
            : [
                'nameColor' => '#2c3e50',
                'nameBold' => true,
                'nameItalic' => false,
                'textColor' => '#6c757d',
                'textItalic' => true,
                'textBold' => false,
                'showName' => true,
                'quoteType' => 'none',
                'fontFamily' => 'inherit',
            ];

        return $this->render('dialogue_style/edit.html.twig', [
            'character' => $character,
            'entity_type' => 'character',
            'style' => $currentStyle,
        ]);
    }

    #[Route('/npc/{id}', name: 'app_dialogue_style_npc', methods: ['GET', 'POST'])]
    public function editNpcStyle(Request $request, Npc $npc, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur est bien le propriétaire du PNJ
        if ($npc->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier ce PNJ');
        }

        // Traiter le formulaire de style
        if ($request->isMethod('POST')) {
            $styleData = [
                'nameColor' => $request->request->get('nameColor', '#2c3e50'),
                'nameBold' => $request->request->has('nameBold'),
                'nameItalic' => $request->request->has('nameItalic'),
                'textColor' => $request->request->get('textColor', '#6c757d'),
                'textItalic' => $request->request->has('textItalic'),
                'textBold' => $request->request->has('textBold'),
                'showName' => $request->request->has('showName'),
                'quoteType' => $request->request->get('quoteType', 'none'),
                'fontFamily' => $request->request->get('fontFamily', 'inherit'),
            ];
            
            // Débogage
            $this->addFlash('info', 'Police enregistrée: ' . $styleData['fontFamily']);
            
            $encodedStyle = json_encode($styleData);
            $npc->setDialogueStyle($encodedStyle);
            $entityManager->flush();

            $this->addFlash('success', 'Le style de dialogue a été enregistré avec succès');
            return $this->redirectToRoute('app_npc_show', ['id' => $npc->getId()]);
        }

        // Récupérer le style actuel ou définir un style par défaut
        $currentStyle = $npc->getDialogueStyle() 
            ? json_decode($npc->getDialogueStyle(), true) 
            : [
                'nameColor' => '#2c3e50',
                'nameBold' => true,
                'nameItalic' => false,
                'textColor' => '#6c757d',
                'textItalic' => true,
                'textBold' => false,
                'showName' => true,
                'quoteType' => 'none',
                'fontFamily' => 'inherit',
            ];

        return $this->render('dialogue_style/edit.html.twig', [
            'npc' => $npc,
            'entity_type' => 'npc',
            'style' => $currentStyle,
        ]);
    }
} 