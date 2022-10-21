<?php

namespace App\Form;

use App\Entity\Place;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PlaceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
//        $options['data']->getCity();
        $builder
            ->add('name')
            ->add('street')
            ->add('latitude')
            ->add('longitude')
            ->add('city', ChoiceType::class, [
                'choices' => $options['data'],
                'mapped' => false,
                'data' => explode(' / ', $options['data']->getCity())
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Place::class,
        ]);
    }
}
