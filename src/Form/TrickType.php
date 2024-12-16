<?php

namespace App\Form;

use App\Entity\Group;
use App\Entity\Trick;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;

class TrickType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'row_attr' => [
                    'class' => 'form-item'
                ]
            ])
            ->add('description', TextareaType::class, [
                'row_attr' => [
                    'class' => 'form-item'
                ],
                'attr' => [
                    'rows' => 10
                ]
            ])
            ->add('groupe', EntityType::class, [
                'class' => Group::class,
                'choice_label' => 'name',
                'row_attr' => [
                    'class' => 'form-item'
                ]
            ])
            ->add('thumbnail', FileType::class, [
                'row_attr' => [
                    'class' => 'form-item'
                ],
                'label' => 'Image principale',
                'mapped' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/png',
                            'image/jpg',
                            'image/jpeg',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid png, jpg or jpeg document',
                    ])
                ],
            ])
            ->add('medias', FileType::class, [
                'row_attr' => [
                    'class' => 'form-item'
                ],
                'multiple' => true,
                'required' => false,
                'label' => 'Fichiers (Images, Vidéos)',
                'mapped' => false,
                'constraints' => [
                    new All([
                        'constraints' => [
                            new File([
                                'maxSize' => '1024000k',
                                'mimeTypes' => [
                                    'image/png',
                                    'image/jpg',
                                    'image/jpeg',
                                    'video/mp4'
                                ],
                                'mimeTypesMessage' => 'Please upload a valid png, jpg, jpeg or mp4 document',
                            ])
                        ],
                    ])
                ]
            ]);

            $builder->get('thumbnail')->addModelTransformer(new CallbackTransformer(
                function($thumbnail) {
                    return null;
                },
                function($thumbnail) {
                    return $thumbnail;
                }
            ));

            $builder->get('medias')->addModelTransformer(new CallbackTransformer(
                function($medias) {
                    return null;
                },
                function($medias) {
                    return $medias;
                }
            ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Trick::class,
        ]);
    }
}
