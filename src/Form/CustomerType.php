<?php

namespace App\Form;

use App\Entity\Customer;
use App\Entity\Address;
use App\Entity\Store;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Nombre',
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Apellido',
            ])
            ->add('email', EmailType::class, [
                'label'    => 'Email',
                'required' => false,
            ])
            ->add('address', EntityType::class, [
                'class'       => Address::class,
                'choice_label'=> fn(Address $a) => $a->getAddress() . ' - ' . $a->getCity(),
                'label'       => 'Dirección (address)',
            ])
            ->add('store', EntityType::class, [
                'class'       => Store::class,
                'choice_label'=> fn(Store $s) => 'Tienda ' . $s->getStoreId(),
                'label'       => 'Tienda',
            ])
            ->add('active', CheckboxType::class, [
                'label'    => 'Activo',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Customer::class,
        ]);
    }
}
