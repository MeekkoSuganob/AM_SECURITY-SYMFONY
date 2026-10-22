<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class CheckoutFormType extends AbstractType
{
    /** label => value. The form only accepts one of these values, anything else is rejected. */
    public const PAYMENT_METHODS = [
        'Credit/Debit Card' => 'Credit/Debit Card',
        'GCash' => 'GCash',
        'Maya' => 'Maya',
        'Bank Transfer' => 'Bank Transfer',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('paymentMethod', ChoiceType::class, [
            'choices' => self::PAYMENT_METHODS,
            'placeholder' => 'Select a payment method',
            'constraints' => [
                new NotBlank(message: 'Please choose a payment method.'),
            ],
        ]);
    }
}
