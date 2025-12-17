<?php

namespace App\Form;

use App\Entity\Customer;
use App\Entity\Payment;
use App\Entity\Rental;
use App\Entity\Staff;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class PaymentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // 1. Desplegable para elegir CLIENTE
            ->add('customer', EntityType::class, [
                'class' => Customer::class,
                'choice_label' => function (Customer $customer) {
                    return $customer->getFirstName() . ' ' . $customer->getLastName();
                },
                'label' => 'Cliente',
                'placeholder' => 'Seleccione un cliente...',
                'attr' => ['class' => 'form-control']
            ])
            
            // 2. Desplegable para elegir STAFF
            ->add('staff', EntityType::class, [
                'class' => Staff::class,
                'choice_label' => function (Staff $staff) {
                    return $staff->getFirstName() . ' ' . $staff->getLastName();
                },
                'label' => 'Atendido por (Staff)',
                'attr' => ['class' => 'form-control']
            ])

            // 3. Desplegable para elegir ALQUILER (Opcional)
            ->add('rental', IntegerType::class, [
            'mapped' => false, // IMPORTANTE: Le decimos a Symfony "no trates de meter esto directo al objeto"
            'label' => 'ID de Alquiler (Opcional)',
            'required' => false,
            'attr' => ['class' => 'form-control', 'placeholder' => 'Ej: 573']
])

            // 4. Monto y Fecha
            ->add('amount', MoneyType::class, [
                'currency' => 'USD',
                'label' => 'Monto ($)',
                'attr' => ['class' => 'form-control']
            ])
            ->add('paymentDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Fecha de Pago',
                'data' => new \DateTime(), // Pone la fecha de hoy por defecto
                'attr' => ['class' => 'form-control']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Payment::class,
        ]);
    }
}