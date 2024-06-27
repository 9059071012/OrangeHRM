<?php

namespace OrangeHRM\Admin\Api\Model;

use OrangeHRM\Core\Api\V2\Serializer\ModelTrait;
use OrangeHRM\Core\Api\V2\Serializer\Normalizable;
use OrangeHRM\Entity\I18NImportError;

/**
 * @OA\Schema(
 *     schema="Admin-I18NImportErrorModel",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="langStringId", type="string"),
 *     @OA\Property(property="source", type="string"),
 *     @OA\Property(
 *         property="error",
 *         type="object",
 *         @OA\Property(property="code", type="string"),
 *         @OA\Property(property="message", type="string")
 *     )
 * )
 */
class I18NImportErrorModel implements Normalizable
{
    use ModelTrait;

    public function __construct(I18NImportError $importError)
    {
        $this->setEntity($importError);
        $this->setFilters([
            'id',
            ['getLangString', 'getId'],
            ['getLangString', 'getValue'],
            ['getError', 'getName'],
            ['getError', 'getMessage']
        ]);
        $this->setAttributeNames([
            'id',
            'langStringId',
            'source',
            ['error', 'code'],
            ['error', 'message']
        ]);
    }
}