<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/** The form an admin uses to create a staff (or admin) account. */
class StaffAccountFormType extends AbstractType
{
    /** label => role. The form only accepts one of these values, anything else is rejected. */
    public const ROLES = [
        'Staff' => 'ROLE_STAFF',
        'Admin' => 'ROLE_ADMIN',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // the rules for these two live on the User entity (NotBlank, Email, UniqueEntity...)
            ->add('username', TextType::class)
            ->add('email', EmailType::class)
            ->add('role', ChoiceType::class, [
                'mapped' => false,
                'choices' => self::ROLES,
                'data' => 'ROLE_STAFF', // new accounts are staff unless the admin picks otherwise
                'expanded' => false,
                'multiple' => false,
                'placeholder' => false,
                'constraints' => [new NotBlank(message: 'Please choose a role.')],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'invalid_message' => 'Passwords do not match.',
                'first_options' => ['attr' => ['autocomplete' => 'new-password']],
                'second_options' => ['attr' => ['autocomplete' => 'new-password']],
                'constraints' => [
                    new NotBlank(message: 'Please enter a password.'),
                    new Length(
                        min: 8,
                        max: 4096,
                        minMessage: 'Password must be at least 8 characters long.',
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
