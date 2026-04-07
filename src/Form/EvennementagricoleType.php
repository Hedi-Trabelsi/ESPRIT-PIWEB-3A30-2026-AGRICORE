<?php

namespace App\Form;

use App\Entity\Evennementagricole;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class EvennementagricoleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le titre est obligatoireoooooo.']),
                    new Assert\Length([
                        'min' => 5,
                        'minMessage' => 'Le titre doit contenir au moins {{ limit }} caractères.',
                        'max' => 255,
                        'maxMessage' => 'Le titre ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La description est obligatoire.']),
                    new Assert\Length([
                        'min' => 10,
                        'minMessage' => 'La description doit contenir au moins {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('lieu', TextType::class, [
                'label' => 'Lieu',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le lieu est obligatoire.']),
                ],
            ])
            ->add('date_debut', DateTimeType::class, [
                'label'  => 'Date de début',
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date de début est obligatoire.']),
                ],
            ])
            ->add('date_fin', DateTimeType::class, [
                'label'  => 'Date de fin',
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date de fin est obligatoire.']),
                ],
            ])
            ->add('frais_inscription', IntegerType::class, [
                'label' => 'Frais d\'inscription (DT)',
                'constraints' => [
                    new Assert\NotNull(['message' => 'Les frais d\'inscription sont obligatoires.']),
                    new Assert\GreaterThanOrEqual([
                        'value' => 0,
                        'message' => 'Les frais ne peuvent pas être négatifs.',
                    ]),
                ],
            ])
            ->add('capacite_max', IntegerType::class, [
                'label' => 'Capacité maximale',
                'constraints' => [
                    new Assert\NotNull(['message' => 'La capacité maximale est obligatoire.']),
                    new Assert\GreaterThan([
                        'value' => 0,
                        'message' => 'La capacité doit être supérieure à 0.',
                    ]),
                ],
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Brouillon' => 'BROUILLON',
                    'À venir'   => 'COMING',
                    'En cours'  => 'EN_COURS',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le statut est obligatoire.']),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evennementagricole::class,
        ]);
    }
}
