<?php

namespace App\Form;

use App\Entity\Maintenance;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MaintenanceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('Nom_maintenance', TextType::class, [
                'property_path' => 'nom_maintenance',
            ])
            ->add('Equipement', TextType::class, [
                'property_path' => 'equipement',
            ])
            ->add('Lieu', TextType::class, [
                'property_path' => 'lieu',
            ])
            ->add('Date_declaration', DateType::class, [
                'widget' => 'single_text',
                'property_path' => 'date_declaration',
            ])
            ->add('Type', ChoiceType::class, [
                'choices' => [
                    'Corrective' => 'Corrective',
                    'Prédictive' => 'Prédictive',
                    'Préventive' => 'Préventive',
                ],
                'property_path' => 'type',
            ])
            ->add('Priorite', ChoiceType::class, [
                'choices' => [
                    'Urgente' => 'Urgente',
                    'Normale' => 'Normale',
                    'Faible' => 'Faible',
                ],
                'property_path' => 'priorite',
            ])
            ->add('Description', TextareaType::class, [
                'property_path' => 'description',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Maintenance::class,
        ]);
    }
}