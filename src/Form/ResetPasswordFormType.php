<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class ResetPasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email :',
                'attr' => ['placeholder' => 'Entrez votre email', 'class' => 'input-field'],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Envoyer le mot de passe temporaire',
                'attr' => ['class' => 'submit-button'],
            ]);
    }
}
