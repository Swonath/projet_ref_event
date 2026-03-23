<?php

namespace App\Form;

use App\Entity\Emplacement;
use App\Service\ReservationCalculatorService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ReservationType extends AbstractType
{
    public function __construct(
        private readonly ReservationCalculatorService $calculatorService
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateDebut', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de début',
                'attr' => [
                    'min' => (new \DateTime())->format('Y-m-d'),
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez sélectionner une date de début'
                    ]),
                    new Assert\GreaterThanOrEqual([
                        'value' => new \DateTime('today'),
                        'message' => 'La date de début doit être aujourd\'hui ou dans le futur'
                    ]),
                ],
            ])
            ->add('dateFin', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de fin',
                'attr' => [
                    'min' => (new \DateTime('+1 day'))->format('Y-m-d'),
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez sélectionner une date de fin'
                    ]),
                ],
            ]);

        // Validation personnalisée après soumission
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($options) {
            $form = $event->getForm();
            $dateDebut = $form->get('dateDebut')->getData();
            $dateFin = $form->get('dateFin')->getData();

            if (!$dateDebut || !$dateFin) {
                return;
            }

            // Vérifier que dateFin > dateDebut
            if ($dateFin <= $dateDebut) {
                $form->get('dateFin')->addError(new FormError(
                    'La date de fin doit être après la date de début'
                ));
                return;
            }

            // Récupérer l'emplacement depuis les options
            /** @var Emplacement $emplacement */
            $emplacement = $options['emplacement'];

            // Vérifier la durée minimum
            if ($emplacement->getDureeMinLocation() !== null) {
                $days = (int) $dateDebut->diff($dateFin)->days;
                if ($days < $emplacement->getDureeMinLocation()) {
                    $form->addError(new FormError(
                        sprintf('La durée minimum de location est de %d jours', $emplacement->getDureeMinLocation())
                    ));
                    return;
                }
            }

            // Vérifier la durée maximum
            if ($emplacement->getDureeMaxLocation() !== null) {
                $days = (int) $dateDebut->diff($dateFin)->days;
                if ($days > $emplacement->getDureeMaxLocation()) {
                    $form->addError(new FormError(
                        sprintf('La durée maximum de location est de %d jours', $emplacement->getDureeMaxLocation())
                    ));
                    return;
                }
            }

            // Vérifier la disponibilité des dates
            if (!$this->calculatorService->isDateRangeAvailable($emplacement, $dateDebut, $dateFin)) {
                $form->addError(new FormError(
                    'Ces dates ne sont pas disponibles. Veuillez choisir d\'autres dates.'
                ));
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'emplacement' => null,
        ]);

        $resolver->setRequired('emplacement');
        $resolver->setAllowedTypes('emplacement', Emplacement::class);
    }
}
