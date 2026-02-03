<?php

namespace App\Form;

use App\Entity\Emplacement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmplacementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titreAnnonce', TextType::class, [
                'label' => 'Titre de l\'annonce',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ex: Emplacement pour pop-up store 50m²'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => true,
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Décrivez votre emplacement en détail...'
                ],
            ])
            ->add('surface', NumberType::class, [
                'label' => 'Surface (m²)',
                'required' => true,
                'attr' => [
                    'placeholder' => '50'
                ]
            ])
            ->add('typeEmplacement', TextType::class, [
                'label' => 'Type d\'emplacement',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Boutique, Stand, Local commercial...'
                ]
            ])
            ->add('localisationPrecise', TextType::class, [
                'label' => 'Localisation précise',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ex: Rez-de-chaussée, entrée principale'
                ]
            ])
            ->add('equipements', TextareaType::class, [
                'label' => 'Équipements',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Ex: Vitrine, éclairage LED, climatisation, Wi-Fi...'
                ],
            ])
            ->add('tarifJour', NumberType::class, [
                'label' => 'Tarif journalier (€)',
                'required' => true,
                'attr' => [
                    'placeholder' => '100.00'
                ]
            ])
            ->add('tarifSemaine', NumberType::class, [
                'label' => 'Tarif hebdomadaire (€)',
                'required' => false,
                'attr' => [
                    'placeholder' => '600.00'
                ]
            ])
            ->add('tarifMois', NumberType::class, [
                'label' => 'Tarif mensuel (€)',
                'required' => false,
                'attr' => [
                    'placeholder' => '2000.00'
                ]
            ])
            ->add('caution', NumberType::class, [
                'label' => 'Caution (€)',
                'required' => true,
                'attr' => [
                    'placeholder' => '500.00'
                ]
            ])
            ->add('dureeMinLocation', IntegerType::class, [
                'label' => 'Durée minimum de location (jours)',
                'required' => false,
                'attr' => [
                    'placeholder' => '1'
                ]
            ])
            ->add('dureeMaxLocation', IntegerType::class, [
                'label' => 'Durée maximum de location (jours)',
                'required' => false,
                'attr' => [
                    'placeholder' => '90'
                ]
            ])
            ->add('statutAnnonce', ChoiceType::class, [
                'label' => 'Statut de l\'annonce',
                'choices' => [
                    'Active (visible par les locataires)' => 'active',
                    'Inactive (masquée)' => 'inactive',
                ],
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Emplacement::class,
        ]);
    }
}
