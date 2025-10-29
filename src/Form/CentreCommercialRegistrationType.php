<?php

namespace App\Form;

use App\Entity\CentreCommercial;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class CentreCommercialRegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // === SECTION : INFORMATIONS DE CONNEXION ===
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'contact@centrecommercial.fr'
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
                        'max' => 4096,
                    ]),
                ],
            ])
            
            // === SECTION : INFORMATIONS DU CENTRE ===
            ->add('nomCentre', TextType::class, [
                'label' => 'Nom du centre commercial',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Carrefour Belle Épine'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir le nom du centre commercial',
                    ]),
                ],
            ])
            ->add('siret', TextType::class, [
                'label' => 'SIRET',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '12345678901234',
                    'maxlength' => 14
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le SIRET est obligatoire',
                    ]),
                    new Regex([
                        'pattern' => '/^[0-9]{14}$/',
                        'message' => 'Le SIRET doit contenir exactement 14 chiffres',
                    ]),
                ],
            ])
            ->add('numeroTva', TextType::class, [
                'label' => 'Numéro de TVA intracommunautaire',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'FR12345678901'
                ],
                'help' => 'Optionnel',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description du centre',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Décrivez votre centre commercial, ses atouts, son emplacement...'
                ],
                'help' => 'Cette description sera visible par les locataires potentiels',
            ])
            
            // === SECTION : ADRESSE DU CENTRE ===
            ->add('adresse', TextType::class, [
                'label' => 'Adresse',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '2 Avenue du Luxembourg'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir l\'adresse du centre',
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
                        'message' => 'Veuillez saisir le code postal',
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
                        'message' => 'Veuillez saisir la ville',
                    ]),
                ],
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '0149806060'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir un numéro de téléphone',
                    ]),
                    new Regex([
                        'pattern' => '/^0[1-9][0-9]{8}$/',
                        'message' => 'Le numéro de téléphone doit être au format français (10 chiffres commençant par 0)',
                    ]),
                ],
            ])
            
            // === SECTION : INFORMATIONS BANCAIRES ===
            ->add('iban', TextType::class, [
                'label' => 'IBAN',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'FR7612345678901234567890123',
                    'maxlength' => 34
                ],
                'help' => 'Pour recevoir les paiements des réservations (peut être ajouté plus tard)',
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
            'data_class' => CentreCommercial::class,
        ]);
    }
}