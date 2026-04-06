<?php

namespace App\Form;

use App\Entity\Animal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AnimalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('codeAnimal', null, [
                'label' => "Code animal",
            ])
            ->add('espece', null, [
                'label' => "Espèce",
            ])
            ->add('race', null, [
                'label' => "Race",
            ])
            ->add('sexe', ChoiceType::class, [
                'label' => "Sexe",
                'choices' => [
                    'Mâle'    => 'Mâle',
                    'Femelle' => 'Femelle',
                ],
                'placeholder' => '-- Choisir --',
            ])
            ->add('dateNaissance', DateType::class, [
                'widget' => 'single_text',
                'label'  => "Date de naissance",
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Animal::class,
        ]);
    }
}