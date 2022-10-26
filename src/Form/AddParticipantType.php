<?php

namespace App\Form;

use App\Entity\Participant;
use App\Entity\Site;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddParticipantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', TextType::class, [
                'required' => true
            ])
            ->add('lastname', TextType::class, [
                'required' => true
            ])
            ->add('firstname', TextType::class, [
                'required' => true
            ])
            ->add('phone', TextType::class, [
                'required' => true
            ])
            ->add('pseudo', TextType::class, [
                'required' => true
            ])
            ->add('site', EntityType::class, [
                'class' => Site::class,
                'choice_label' => 'name'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Participant::class,
        ]);
    }
}
