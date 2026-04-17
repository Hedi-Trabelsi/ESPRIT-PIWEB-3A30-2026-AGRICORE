<?php

namespace App\Form;

use App\Entity\Equipement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class EquipementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('type', TextType::class, [
                'label' => 'Type',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('prix', NumberType::class, [
                'label' => 'Prix (TND)',
                'attr' => ['class' => 'form-control', 'step' => '0.01'],
                'invalid_message' => 'Veuillez entrer un nombre valide.',
            ])
            ->add('quantite', NumberType::class, [
                'label' => 'Quantite',
                'attr' => ['class' => 'form-control'],
                'invalid_message' => 'Veuillez entrer un nombre valide.',
            ])
            ->add('imageFile', VichImageType::class, [
                'label' => 'Image (JPG/PNG)',
                'required' => false,
                'allow_delete' => false,
                'download_uri' => false,
                'image_uri' => false,
                'asset_helper' => true,
                'attr' => ['class' => 'form-control'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Equipement::class,
        ]);
    }
}
