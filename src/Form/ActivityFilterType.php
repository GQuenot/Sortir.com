<?php

namespace App\Form;

use App\Entity\Site;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActivityFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('site', EntityType::class, [
                'class' => Site::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Site'
            ])
            ->add('search', TextType::class, [
                'required' => false
            ])
            ->add('startDate', DateType::class, [
                'html5' => true,
                'widget' => 'single_text',
                'required' => false
            ])
            ->add('endDate', DateType::class, [
                'html5' => true,
                'widget' => 'single_text',
                'required' => false
            ])
            ->add('isOrganizer', CheckboxType::class, [
                'required' => false
            ])
            ->add('isParticipant', CheckboxType::class, [
                'required' => false
            ])
            ->add('isNotParticipant', CheckboxType::class, [
                'required' => false
            ])
            ->add('pastActivities', CheckboxType::class, [
                'required' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
