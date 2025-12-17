<?php

namespace App\Form;

use App\Entity\Rental;
use App\Entity\Customer;
use App\Entity\Inventory;
use App\Entity\Staff;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RentalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customer', EntityType::class, [
                'class'        => Customer::class,
                'choice_label' => fn(Customer $c) => $c->getFullName() . ' (#' . $c->getId() . ')',
                'label'        => 'Cliente',
            ])
            ->add('inventory', EntityType::class, [
                'class'        => Inventory::class,
                'choice_label' => function (Inventory $inv) {
                    $film  = $inv->getFilm();
                    $store = $inv->getStore();
                    return sprintf('%s (Inv #%d, Tienda %d)',
                        $film->getTitle(),
                        $inv->getId(),
                        $store->getStoreId()
                    );
                },
                'label'        => 'Copia / Película',
            ])
            ->add('staff', EntityType::class, [
                'class'        => Staff::class,
                'choice_label' => fn(Staff $s) => $s->getUsername(),
                'label'        => 'Empleado que atiende',
            ])
            ->add('rentalDate', DateTimeType::class, [
                'widget'   => 'single_text',
                'label'    => 'Fecha de alquiler',
                'data'     => new \DateTime(),
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Rental::class,
        ]);
    }
}
