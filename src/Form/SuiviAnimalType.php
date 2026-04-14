<?php

namespace App\Form;

use App\Entity\Animal;
use App\Entity\SuiviAnimal;
use App\Repository\AnimalRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SuiviAnimalType extends AbstractType
{
    public function __construct(private AnimalRepository $animalRepository) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $idAgriculteur = $options['idAgriculteur'];
        $fixedAnimal   = $options['fixedAnimal'];

        // N'ajouter le champ animal que si aucun animal n'est fixé
        if ($fixedAnimal === null) {
            $builder->add('animal', EntityType::class, [
                'class'        => Animal::class,
                'choice_label' => 'codeAnimal',
                'label'        => 'Animal',
                'choices'      => $this->animalRepository->findBy(['idAgriculteur' => $idAgriculteur]),
                'placeholder'  => '-- Choisir un animal --',
            ]);
        }

        $builder
            ->add('dateSuivi', DateTimeType::class, [
                'widget' => 'single_text',
                'label'  => 'Date du suivi',
            ])
            ->add('temperature', null, ['label' => 'Température (°C)'])
            ->add('poids',       null, ['label' => 'Poids (kg)'])
            ->add('rythmeCardiaque', null, ['label' => 'Rythme cardiaque (bpm)'])
            ->add('niveauActivite', ChoiceType::class, [
                'label'       => "Niveau d'activité",
                'choices'     => ['Faible' => 'Faible', 'Modéré' => 'Modéré', 'Élevé' => 'Élevé'],
                'placeholder' => '-- Choisir --',
            ])
            ->add('etatSante', ChoiceType::class, [
                'label'       => 'État de santé',
                'choices'     => ['Bon' => 'Bon', 'Moyen' => 'Moyen', 'Mauvais' => 'Mauvais'],
                'placeholder' => '-- Choisir --',
            ])
            ->add('remarque', TextareaType::class, ['label' => 'Remarque'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'    => SuiviAnimal::class,
            'idAgriculteur' => null,
            'fixedAnimal'   => null,
        ]);
        $resolver->setAllowedTypes('idAgriculteur', ['null', 'int']);
        $resolver->setAllowedTypes('fixedAnimal', ['null', Animal::class]);
    }
}
