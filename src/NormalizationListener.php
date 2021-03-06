<?php

namespace Fabstract\Component\REST;

use Fabstract\Component\Event\ListenerInterface;
use Fabstract\Component\Http\Injectable;
use Fabstract\Component\LINQ\LINQ;
use Fabstract\Component\Serializer\Event\NormalizationWillStartEvent;
use Fabstract\Component\REST\Exception\ResponseValidationException;
use Fabstract\Component\REST\Model\ResponseModel;
use Fabstract\Component\REST\Model\ValidationErrorModel;

class NormalizationListener extends Injectable implements ListenerInterface, ServiceAware
{

    /**
     * @param NormalizationWillStartEvent $event
     * @return void
     * @throws ResponseValidationException
     */
    public function onEvent($event)
    {
        if ($event->getDepth() !== 0) {
            // Validations should start running from depth 0
            return;
        }

        $normalized = $event->getObjectToNormalize();
        if ($normalized instanceof ResponseModel) {
            $validation_error_list = $this->validator->validate($normalized);
            if (count($validation_error_list) > 0) {
                $validation_error_model_list = LINQ::from($validation_error_list)
                    ->select(function ($validation_error) {
                        /** @var \Fabstract\Component\Validator\ValidationError $validation_error */
                        return ValidationErrorModel::create($validation_error);
                    })
                    ->toArray();

                throw new ResponseValidationException($validation_error_model_list);
            }
        }
    }

    /**
     * @return string
     */
    public function getChannel()
    {
        return NormalizationWillStartEvent::class;
    }
}
