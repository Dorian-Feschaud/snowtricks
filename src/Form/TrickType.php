<?php

namespace App\Form;

use App\Entity\Group;
use App\Entity\Trick;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class TrickType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('description')
            ->add('groupe', EntityType::class, [
                'class' => Group::class,
                'choice_label' => 'name',
            ])
            ->add('medias', FileType::class, [
                'multiple' => true,
                'required' => false,
                'label' => 'Fichiers (Images, Vidéos)',
                'mapped' => false,
            ])
            ->get('medias')->addModelTransformer(new CallbackTransformer(
                function($medias) {
                    return null;
                },
                function($medias) {
                    return $medias;
                }
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Trick::class,
        ]);
    }
}
