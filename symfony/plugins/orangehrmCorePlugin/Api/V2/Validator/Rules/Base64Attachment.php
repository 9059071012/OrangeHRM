<?php
/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software; you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation; either
 * version 2 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program;
 * if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor,
 * Boston, MA  02110-1301, USA
 */

namespace OrangeHRM\Core\Api\V2\Validator\Rules;

use OrangeHRM\Core\Traits\Service\ConfigServiceTrait;

class Base64Attachment extends AbstractRule
{
    use ConfigServiceTrait;

    /**
     * @var array
     */
    protected array $allowedTypes;
    /**
     * @var int|null
     */
    protected ?int $fileNameMaxLength;
    /**
     * @var int|null
     */
    protected ?int $fileNameMinLength;
    /**
     * @var int
     */
    protected int $maxFileSize;
    /**
     * @var bool
     */
    private bool $checkSizeStrictly;

    public function __construct(
        array $allowedTypes,
        ?int $fileNameMaxLength = null,
        ?int $fileNameMinLength = null,
        bool $checkSizeStrictly = true,
        ?int $maxFileSize = null
    ) {
        $this->allowedTypes = $allowedTypes;
        $this->fileNameMaxLength = $fileNameMaxLength;
        $this->fileNameMinLength = $fileNameMinLength;
        $this->checkSizeStrictly = $checkSizeStrictly;
        $this->maxFileSize = $maxFileSize ?? $this->getConfigService()->getMaxAttachmentSize();
    }

    public function validate($input): bool
    {
        if (!(isset($input['name']) && isset($input['type']) && isset($input['base64']) && isset($input['size']))) {
            return false;
        }
        if (!in_array($input['type'], $this->allowedTypes)) {
            return false;
        }

        if (!is_null($this->fileNameMinLength) || !is_null($this->fileNameMaxLength)) {
            if (is_string($input['name'])) {
                if (function_exists('mb_strlen')) {
                    $fileNameLength = (int)mb_strlen($input['name']);
                } else {
                    $fileNameLength = strlen($input['name']);
                }

                if (!($this->validateMin($fileNameLength) && $this->validateMax($fileNameLength))) {
                    return false;
                }
            } else {
                return false;
            }
        }

        $size = intval($input['size']);
        if ($this->checkSizeStrictly) {
            $content = base64_decode($input['base64']);
            if (function_exists('mb_strlen')) {
                $contentSize = mb_strlen($content, '8bit');
            } else {
                $contentSize = strlen($content);
            }
            $content = null;
            if ($size !== $contentSize) {
                return false;
            }
        }

        if ($size > $this->maxFileSize) {
            return false;
        }

        return true;
    }

    /**
     * @param int $length
     * @return bool
     */
    private function validateMin(int $length): bool
    {
        if ($this->fileNameMinLength === null) {
            return true;
        }

        return $length >= $this->fileNameMinLength;
    }

    /**
     * @param int $length
     * @return bool
     */
    private function validateMax(int $length): bool
    {
        if ($this->fileNameMaxLength === null) {
            return true;
        }

        return $length <= $this->fileNameMaxLength;
    }
}
