<?php

namespace App\Form;

use App\Entity\Participants;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ParticipantsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $maxPlaces = $options['max_places'];

        $builder
            ->add('nom_participant', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom du participant est obligatoire.']),
                    new Assert\Length([
                        'min' => 3,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\NotBlank(['message' => "L'adresse email est obligatoire."]),
                    new Assert\Email(['message' => "L'adresse email n'est pas valide."]),
                ],
            ])
            ->add('nbr_places', IntegerType::class, [
                'constraints' => [
                    new Assert\NotNull(['message' => 'Le nombre de places est obligatoire.']),
                    new Assert\GreaterThanOrEqual(['value' => 1, 'message' => 'Minimum 1 place.']),
                    new Assert\LessThanOrEqual([
                        'value' => $maxPlaces,
                        'message' => "Seulement {{ compared_value }} place(s) disponible(s).",
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Participants::class,
            'max_places' => 999,
        ]);
    }
}
