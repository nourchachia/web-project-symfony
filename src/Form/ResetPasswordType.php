<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class ResetPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'constraints' => [
                    new Assert\NotBlank(message: 'Please enter your email address.'),
                    new Assert\Email(message: 'Please enter a valid email address.'),
                ],
                'attr' => [
                    'placeholder' => 'your@email.com',
                    'autocomplete' => 'email',
                ],
            ])
            ->add('code', TextType::class, [
                'label' => 'Reset Code',
                'constraints' => [
                    new Assert\NotBlank(message: 'Please enter the reset code.'),
                    new Assert\Regex(
                        pattern: '/^\d{6}$/',
                        message: 'The reset code must contain exactly 6 digits.'
                    ),
                ],
                'attr' => [
                    'placeholder' => '123456',
                    'autocomplete' => 'one-time-code',
                    'inputmode' => 'numeric',
                    'maxlength' => 6,
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Passwords do not match.',
                'first_options' => [
                    'label' => 'New Password',
                    'attr' => [
                        'autocomplete' => 'new-password',
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirm New Password',
                    'attr' => [
                        'autocomplete' => 'new-password',
                    ],
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Please enter a new password.'),
                    new Assert\Length(
                        min: 8,
                        minMessage: 'Password must be at least {{ limit }} characters.'
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
