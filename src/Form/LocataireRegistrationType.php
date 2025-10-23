<?php

namespace App\Form;

use App\Entity\Locataire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class LocataireRegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // === SECTION : INFORMATIONS DE CONNEXION ===
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'exemple@email.fr'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir une adresse email',
                    ]),
                    new Email([
                        'message' => 'Veuillez saisir une adresse email valide',
                    ]),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                // Ce champ n'est pas mappé à l'entité (mapped: false)
                // car on va chiffrer le mot de passe avant de le sauvegarder
                'mapped' => false,
                'label' => 'Mot de passe',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Minimum 6 caractères'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir un mot de passe',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères',
                        'max' => 4096, // Limite de sécurité
                    ]),
                ],
            ])
            
            // === SECTION : INFORMATIONS PERSONNELLES ===
            ->add('nom', TextType::class, [
                'label' => 'Nom / Raison sociale',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Martin Sophie ou Nom de l\'entreprise'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir un nom',
                    ]),
                ],
            ])
            ->add('siret', TextType::class, [
                'label' => 'SIRET',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '12345678901234 (optionnel pour les particuliers)',
                    'maxlength' => 14
                ],
                'constraints' => [
                    new Regex([
                        'pattern' => '/^[0-9]{14}$/',
                        'message' => 'Le SIRET doit contenir exactement 14 chiffres',
                        // Ne s'applique que si le champ n'est pas vide
                        'match' => true,
                    ]),
                ],
                'help' => 'Obligatoire pour les entreprises, optionnel pour les particuliers',
            ])
            ->add('typeActivite', TextType::class, [
                'label' => 'Type d\'activité',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Artisanat, Restauration, Mode, etc.'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez préciser votre type d\'activité',
                    ]),
                ],
            ])
            
            // === SECTION : ADRESSE DE FACTURATION ===
            ->add('adresseFacturation', TextType::class, [
                'label' => 'Adresse',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '12 Rue de la Paix'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir votre adresse',
                    ]),
                ],
            ])
            ->add('codePostal', TextType::class, [
                'label' => 'Code postal',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '75000',
                    'maxlength' => 5
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir votre code postal',
                    ]),
                    new Regex([
                        'pattern' => '/^[0-9]{5}$/',
                        'message' => 'Le code postal doit contenir 5 chiffres',
                    ]),
                ],
            ])
            ->add('ville', TextType::class, [
                'label' => 'Ville',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Paris'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir votre ville',
                    ]),
                ],
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '0612345678'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir votre numéro de téléphone',
                    ]),
                    new Regex([
                        'pattern' => '/^0[1-9][0-9]{8}$/',
                        'message' => 'Le numéro de téléphone doit être au format français (10 chiffres commençant par 0)',
                    ]),
                ],
            ])
            
            // === BOUTON DE SOUMISSION ===
            ->add('submit', SubmitType::class, [
                'label' => 'Créer mon compte',
                'attr' => [
                    'class' => 'btn btn-primary btn-lg w-100'
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Locataire::class,
        ]);
    }
}