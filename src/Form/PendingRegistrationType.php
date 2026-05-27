<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\PendingRegistration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class PendingRegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, [
                'label' => 'First Name',
                'attr' => [
                    'placeholder' => 'Foulen',
                    'autocomplete' => 'given-name',
                ],
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Last Name',
                'attr' => [
                    'placeholder' => 'Foulena',
                    'autocomplete' => 'family-name',
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'attr' => [
                    'placeholder' => 'your@email.com',
                    'autocomplete' => 'email',
                ],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Phone Number',
                'required' => false,
                'attr' => [
                    'placeholder' => '+216 XX XXX XXX',
                    'autocomplete' => 'tel',
                ],
            ])
            ->add('birthdate', BirthdayType::class, [
                'label' => 'Date of Birth',
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'attr' => [
                    'autocomplete' => 'bday',
                ],
            ])
            ->add('yearOfStudy', IntegerType::class, [
                'label' => 'Year of Study',
                'required' => false,
                'attr' => [
                    'placeholder' => 'e.g. 2',
                    'min' => 1,
                    'max' => 6,
                ],
            ])
            ->add('fieldOfStudy', ChoiceType::class, [
                'label' => 'Field of Study',
                'placeholder' => 'Select your field of study',
                'choices' => [
                    'MPI' => 'MPI',
                    'CBA' => 'CBA',
                    'GL' => 'GL',
                    'RT' => 'RT',
                    'IIA' => 'IIA',
                    'IMI' => 'IMI',
                    'Other' => 'Other',
                ],
            ])
            ->add('department', ChoiceType::class, [
                'label' => 'Department',
                'placeholder' => 'Select your department',
                'choices' => [
                    'Acting' => 'Acting',
                    'Music' => 'Music',
                    'Dancing' => 'Dancing',
                    'Writing' => 'Writing',
                    'Media' => 'Media',
                ],
            ])
            ->add('picture', FileType::class, [
                'label' => 'Profile Picture',
                'mapped' => false,
                'required' => false,
                'help' => 'Optional - JPEG, PNG, GIF, or WebP under 5 MB.',
                'constraints' => [
                    new Assert\Image(
                        maxSize: '5M',
                        mimeTypes: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                        maxSizeMessage: 'Please upload an image under 5 MB.',
                        mimeTypesMessage: 'Please upload a valid JPEG, PNG, GIF, or WebP image.'
                    ),
                ],
            ])
            ->add('whyJoin', TextareaType::class, [
                'label' => 'Why do you want to join Theatro?',
                'attr' => [
                    'placeholder' => 'Tell us briefly why you want to join...',
                    'maxlength' => 1000,
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'invalid_message' => 'Passwords do not match.',
                'first_options' => [
                    'label' => 'Password',
                    'attr' => [
                        'autocomplete' => 'new-password',
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirm Password',
                    'attr' => [
                        'autocomplete' => 'new-password',
                    ],
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Please enter a password.'),
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
            'data_class' => PendingRegistration::class,
        ]);
    }
}
