<?php

namespace App\Form;

use App\Entity\CustomerPlan;
use App\Entity\Plan;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomerPlanType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $customerPlan = $event->getData();

            if ($customerPlan === null) {
                $customerPlan = new CustomerPlan();
                $event->setData($customerPlan);
            }

            if (!$customerPlan instanceof CustomerPlan) {
                return;
            }

            if ($customerPlan->getStartedAt() === null) {
                $customerPlan->setStartedAt(new \DateTimeImmutable());
            }

            if ($customerPlan->isActive() === null) {
                $customerPlan->setIsActive(true);
            }
        });

        $builder
            ->add('plan', EntityType::class, [
                'class' => Plan::class,
                'choice_label' => 'name',
                'label' => 'Plan',
                'query_builder' => fn ($repository) => $repository
                    ->createQueryBuilder('p')
                    ->andWhere('p.isActive = :isActive')
                    ->setParameter('isActive', true),
            ])
            ->add('startedAt', DateTimeType::class, [
                'label' => 'Fecha de inicio',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Activo',
                'required' => false,
                'empty_data' => true,
            ])
            ->add('serviceIp', TextType::class, [
                'label' => 'IP de servicio',
                'required' => false,
                'empty_data' => null,
                'attr' => [
                    'placeholder' => '—',
                ],
            ])
            ->add('macAddress', TextType::class, [
                'label' => 'Dirección MAC',
                'required' => false,
                'empty_data' => null,
                'attr' => [
                    'placeholder' => '—',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CustomerPlan::class,
        ]);
    }
}
