<?php

namespace App\Form;

use App\Entity\Evennementagricole;
use Symfony\Component\Form\AbstractType;
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
                'required' => false,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le titre est obligatoire.']),
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
                'required' => false,
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
                'required' => false,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le lieu est obligatoire.']),
                ],
            ])

            ->add('date_debut', DateTimeType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'required' => false,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date de début est obligatoire.']),
                    new Assert\Type(\DateTimeInterface::class),
                ],
            ])

            ->add('date_fin', DateTimeType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'required' => false,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date de fin est obligatoire.']),
                    new Assert\Type(\DateTimeInterface::class),
                    new Assert\GreaterThan([
                        'propertyPath' => 'parent.all[date_debut].data',
                        'message' => 'La date de fin doit être après la date de début.',
                    ]),
                ],
            ])

            ->add('frais_inscription', IntegerType::class, [
                'label' => "Frais d'inscription (DT)",
                'required' => false,
                'constraints' => [
                    new Assert\NotNull(['message' => 'Les frais sont obligatoires.']),
                    new Assert\GreaterThanOrEqual([
                        'value' => 0,
                        'message' => 'Les frais ne peuvent pas être négatifs.',
                    ]),
                ],
            ])

            ->add('capacite_max', IntegerType::class, [
                'label' => 'Capacité maximale',
                'required' => false,
                'constraints' => [
                    new Assert\NotNull(['message' => 'La capacité est obligatoire.']),
                    new Assert\GreaterThan([
                        'value' => 0,
                        'message' => 'La capacité doit être supérieure à 0.',
                    ]),
                ],
            ])


        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evennementagricole::class,
            'csrf_protection' => true,
        ]);
    }
}
