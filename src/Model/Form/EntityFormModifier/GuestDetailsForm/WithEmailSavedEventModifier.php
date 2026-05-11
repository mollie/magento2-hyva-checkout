<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\HyvaCheckout\Model\Form\EntityFormModifier\GuestDetailsForm;

use Hyva\Checkout\Model\Form\EntityField\AbstractEntityField;
use Hyva\Checkout\Model\Form\EntityForm\GuestDetailsForm;
use Hyva\Checkout\Model\Form\EntityFormInterface;
use Hyva\Checkout\Model\Form\EntityFormModifierInterface;

class WithEmailSavedEventModifier implements EntityFormModifierInterface
{
    public function apply(EntityFormInterface $form): EntityFormInterface
    {
        $form->modifyField(GuestDetailsForm::FIELD_EMAIL, function (AbstractEntityField $field) {
            if (!$field->hasAttributesStartingWith('wire:auto-save.self')) {
                $field->replaceAttribute('wire:auto-save', 'wire:auto-save.self');
            }
        });

        $form->registerModificationListener(
            'mollieEmitGuestEmailSavedEvent',
            'form:execute:submit:magewire',
            function (EntityFormInterface $form, $component, bool $result): void {
                if ($result) {
                    $component->emit('mollie_guest_email_saved');
                }
            }
        );

        return $form;
    }
}
