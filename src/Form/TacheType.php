<?php

namespace App\Form;

use App\Entity\Tache;
use App\Entity\Maintenance;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TacheType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomTache', TextType::class, [
                'label' => 'Nom de la tâche',
                'required' => true,
            ])
            ->add('id_maintenance', EntityType::class, [
                'class' => Maintenance::class,
                'choice_label' => 'nomMaintenance',
                'label' => 'Maintenance associée',
                'placeholder' => 'Choisir une maintenance',
                'required' => true,
                'disabled' => true, // Désactiver si pré-rempli
            ])
         ->add('id_technicien', EntityType::class, [
    'class' => User::class,
    'choice_label' => function (User $user) {
        return sprintf('%s %s', $user->getNom(), $user->getPrenom());
    },
    'label' => 'Technicien',
    'placeholder' => 'Choisir un technicien',
    'required' => false,
    // 'data' => ... ici il faudrait injecter l'objet User(4) via les options, 
    // c'est pour ça que le faire dans le Contrôleur est beaucoup plus simple.
])
            ->add('date_prevue', DateType::class, [
                'label' => 'Date prévue',
                'widget' => 'single_text',
                'required' => true,
            ])
            ->add('cout_estimee', TextType::class, [
                'label' => 'Coût estimé',
                'required' => true,
            ])
            ->add('evaluation', IntegerType::class, [
                'label' => 'Évaluation',
                'required' => false,
                'data' => 0, // Valeur par défaut
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => true,
                'attr' => ['rows' => 4],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tache::class,
        ]);
    }
}
