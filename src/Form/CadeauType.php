<?php

namespace App\Form;

use App\Entity\Cadeau;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class CadeauType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        if (!$options['is_edition']) {
            $builder
                ->add('designation', TextType::class, [
                    'label' => "Nom du cadeau",
                    'attr' => [
                        'readonly' => $options['is_edition'],
                    ]
                ]);
        }

        $builder
            ->add('age', IntegerType::class, [
                'label' => 'age du cadeau'
            ])
            ->add('prix', MoneyType::class, [
                'label' => 'Prix du cadeau'
            ])
            ->add('image', FileType::class, [
                'label' => 'image du cadeau',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '9000k',
                    ])
                ]
            ])
            ->add('categorie')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Cadeau::class,
            'is_edition' => false
        ]);

        $resolver->setAllowedTypes('is_edition', 'bool');
    }
}
