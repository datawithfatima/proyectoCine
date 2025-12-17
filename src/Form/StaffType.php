<?php

namespace App\Form;

use App\Entity\Address;
use App\Entity\Staff;
use App\Entity\Store;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StaffType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, ['label' => 'Nombre'])
            ->add('lastName', TextType::class, ['label' => 'Apellido'])
            ->add('email', TextType::class, ['required' => false])
            ->add('username', TextType::class, ['label' => 'Usuario'])
            ->add('password', PasswordType::class, [
                'label' => 'Contraseña',
                'required' => false, // Opcional al editar
                'empty_data' => '',
                'attr' => ['autocomplete' => 'new-password']
            ])
            
            // Elegir Tienda (Desplegable)
            ->add('store', EntityType::class, [
                'class' => Store::class,
                'choice_label' => 'storeId', // Muestra el ID de la tienda
                'label' => 'Tienda Asignada',
                'placeholder' => 'Seleccione una tienda'
            ])

            // Elegir Dirección (Desplegable)
            ->add('address', EntityType::class, [
                'class' => Address::class,
                'choice_label' => 'address', // Muestra la calle
                'label' => 'Dirección',
                'placeholder' => 'Seleccione dirección'
            ])

            ->add('active', CheckboxType::class, [
                'label' => 'Empleado Activo',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Staff::class,
        ]);
    }
}