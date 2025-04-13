<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WysiwygType extends AbstractType
{
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $attr = $view->vars['attr'] ?? [];
        
        // Assigner la classe CSS pour l'initialisation de l'éditeur
        $attr['class'] = isset($attr['class']) 
            ? $attr['class'] . ' wysiwyg-editor' 
            : 'wysiwyg-editor';
        
        // Assigner l'éditeur spécifique si défini (standard, full, quick)
        if ($options['editor_type'] !== 'standard') {
            $attr['class'] .= '-' . $options['editor_type'];
        }
        
        // Ajouter le mode RP ou HRP si défini
        if ($options['editor_mode'] === 'rp') {
            $attr['data-mode'] = 'roleplay';
        } else if ($options['editor_mode'] === 'hrp') {
            $attr['data-mode'] = 'hrp';
        }
        
        // Définir un placeholder personnalisé si fourni
        if ($options['placeholder']) {
            $attr['placeholder'] = $options['placeholder'];
        }
        
        $view->vars['attr'] = $attr;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'editor_type' => 'standard', // standard, full, quick
            'editor_mode' => '', // rp, hrp, ou vide
            'placeholder' => null,
            'attr' => [
                'rows' => 10,
            ],
        ]);

        $resolver->setAllowedValues('editor_type', ['standard', 'full', 'quick']);
        $resolver->setAllowedValues('editor_mode', ['', 'rp', 'hrp']);
    }

    public function getParent(): string
    {
        return TextareaType::class;
    }
}