<?php

namespace App\Form;

use App\Entity\Activity;
use App\Entity\Place;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class ActivityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'required' => true
            ])
            ->add('activityDate', DateTimeType::class, [
                'required' => true,
                'widget' => 'single_text',
                'html5' => true,
                'constraints' => [
                    new GreaterThan([
                        'value' => new \DateTime()
                    ])
                ]
            ])
            ->add('duration', NumberType::class, [
                'required' => true,
                'constraints' => [
                    new GreaterThanOrEqual([
                        'value' => 60
                    ])
                ]
            ])
            ->add('subLimitDate', DateTimeType::class, [
                'required' => true,
                'html5' => true,
                'widget' => 'single_text',
                'constraints' => [
                    new GreaterThan([
                        'value' => new \DateTime()
                    ])
                ]
            ])
            ->add('placeLimit', NumberType::class, [
                'required' => true,
                'constraints' => [
                    new GreaterThanOrEqual([
                        'value' => 1
                    ])
                ]
            ])
            ->add('informations', TextareaType::class)
            ->add('place', EntityType::class, [
                'class' => Place::class,
                'choice_label' => 'name'
            ])
            ->add('save', SubmitType::class, ['label' => 'Enregister'])
            ->add('publish', SubmitType::class, ['label' => 'Publier'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Activity::class,
        ]);
    }
}
