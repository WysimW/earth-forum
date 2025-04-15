<?php

namespace App\Controller\Admin;

use App\Entity\Univers;
use App\Repository\UniversRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

#[Route('/admin/univers')]
#[IsGranted('ROLE_ADMIN')]
class AdminUniversController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private SluggerInterface $slugger;

    public function __construct(EntityManagerInterface $entityManager, SluggerInterface $slugger)
    {
        $this->entityManager = $entityManager;
        $this->slugger = $slugger;
    }

    #[Route('/', name: 'admin_univers_index', methods: ['GET'])]
    public function index(UniversRepository $universRepository): Response
    {
        return $this->render('admin/univers/index.html.twig', [
            'univers' => $universRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_univers_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $univers = new Univers();
        
        // Création du formulaire directement ici car nous n'avons pas encore de UniversType
        $form = $this->createFormBuilder($univers)
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Nom de l\'univers'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'Description de l\'univers'
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Créer',
                'attr' => ['class' => 'btn btn-primary mt-3']
            ])
            ->getForm();
            
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $slug = $this->slugger->slug(strtolower($univers->getName()));
            $univers->setSlug($slug);
            
            $this->entityManager->persist($univers);
            $this->entityManager->flush();

            $this->addFlash('success', 'L\'univers a été créé avec succès.');
            return $this->redirectToRoute('admin_univers_index');
        }

        return $this->render('admin/univers/new.html.twig', [
            'univers' => $univers,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_univers_show', methods: ['GET'])]
    public function show(Univers $univers): Response
    {
        return $this->render('admin/univers/show.html.twig', [
            'univers' => $univers,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_univers_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Univers $univers): Response
    {
        // Création du formulaire directement ici car nous n'avons pas encore de UniversType
        $form = $this->createFormBuilder($univers)
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Nom de l\'univers'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'Description de l\'univers'
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Mettre à jour',
                'attr' => ['class' => 'btn btn-primary mt-3']
            ])
            ->getForm();
            
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $slug = $this->slugger->slug(strtolower($univers->getName()));
            $univers->setSlug($slug);
            
            $this->entityManager->flush();

            $this->addFlash('success', 'L\'univers a été mis à jour avec succès.');
            return $this->redirectToRoute('admin_univers_index');
        }

        return $this->render('admin/univers/edit.html.twig', [
            'univers' => $univers,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_univers_delete', methods: ['POST'])]
    public function delete(Request $request, Univers $univers): Response
    {
        if ($this->isCsrfTokenValid('delete'.$univers->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($univers);
            $this->entityManager->flush();

            $this->addFlash('success', 'L\'univers a été supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_univers_index');
    }
} 