<?php

namespace App\Form;

use App\Entity\Depense;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DepenseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'choices'  => [
                    'Main d\'oeuvre' => 'MAINDOEUVRE',
                    'Intrant' => 'INTRANT',
                    'Carburant' => 'CARBURANT',
                    'Réparation' => 'REPARATION',
                    'Autre' => 'AUTRE',
                ],
                'attr' => ['class' => 'form-control'],
                'label' => 'Type de dépense'
            ])
            ->add('montant', NumberType::class, [
                'attr' => ['class' => 'form-control', 'placeholder' => '0.00'],
                'label' => 'Montant (DT)'
            ])
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'label' => 'Date'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Depense::class,
        ]);
    }
}
