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

namespace OrangeHRM\LDAP\Api;

use OrangeHRM\Core\Api\V2\CollectionEndpoint;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Traits\Service\ConfigServiceTrait;
use OrangeHRM\Core\Traits\ValidatorTrait;
use OrangeHRM\LDAP\Api\Model\LDAPTestConnectionModel;
use OrangeHRM\LDAP\Api\Traits\LDAPCommonParamRuleCollection;
use OrangeHRM\LDAP\Dto\LDAPSetting;
use OrangeHRM\LDAP\Dto\LDAPUserLookupSetting;

class LDAPTestConnectionAPI extends Endpoint implements CollectionEndpoint
{
    use ConfigServiceTrait;
    use ValidatorTrait;
    use LDAPCommonParamRuleCollection;

    /**
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function create(): EndpointResult
    {
        $dataMapping = $this->getRequestParams()->getArray(
            RequestParams::PARAM_TYPE_BODY,
            LDAPConfigAPI::PARAMETER_DATA_MAPPING
        );
        $this->validate($dataMapping, $this->getParamRuleCollectionForDataMapping());

        $userLookupSettings = $this->getRequestParams()->getArray(
            RequestParams::PARAM_TYPE_BODY,
            LDAPConfigAPI::PARAMETER_USER_LOOKUP_SETTINGS
        );
        foreach ($userLookupSettings as $userLookupSetting) {
            $this->validate($userLookupSetting, $this->getParamRuleCollectionForUserLookupSettings());
        }

        $ldapSettings = new LDAPSetting(
            $this->getRequestParams()->getString(
                RequestParams::PARAM_TYPE_BODY,
                LDAPConfigAPI::PARAMETER_HOSTNAME
            ),
            $this->getRequestParams()->getInt(
                RequestParams::PARAM_TYPE_BODY,
                LDAPConfigAPI::PARAMETER_PORT
            ),
            $this->getRequestParams()->getString(
                RequestParams::PARAM_TYPE_BODY,
                LDAPConfigAPI::PARAMETER_LDAP_IMPLEMENTATION
            ),
            $this->getRequestParams()->getString(
                RequestParams::PARAM_TYPE_BODY,
                LDAPConfigAPI::PARAMETER_ENCRYPTION
            ),
        );

        $this->setConfigAttributes($ldapSettings);
        $this->setDataMappingAttributes($ldapSettings, $dataMapping);
        $this->setUserLookupSettings($ldapSettings, $userLookupSettings);
        return new EndpointResourceResult(LDAPTestConnectionModel::class, $ldapSettings);
    }

    /**
     * @param LDAPSetting $ldapSetting
     */
    private function setConfigAttributes(LDAPSetting $ldapSetting): void
    {
        $bindAnonymously = $this->getRequestParams()->getBoolean(
            RequestParams::PARAM_TYPE_BODY,
            LDAPConfigAPI::PARAMETER_BIND_ANONYMOUSLY
        );
        $ldapSetting->setBindAnonymously($bindAnonymously);
        if (!$bindAnonymously) {
            $ldapSetting->setBindUserDN(
                $this->getRequestParams()->getString(
                    RequestParams::PARAM_TYPE_BODY,
                    LDAPConfigAPI::PARAMETER_BIND_USER_DISTINGUISHED_NAME
                )
            );

            $password = $this->getRequestParams()->getStringOrNull(
                RequestParams::PARAM_TYPE_BODY,
                LDAPConfigAPI::PARAMETER_BIND_USER_PASSWORD
            );
            $ldapSettings = $this->getConfigService()->getLDAPSetting();
            if ($ldapSettings instanceof LDAPSetting && $password === null) {
                $ldapSetting->setBindUserPassword($ldapSettings->getBindUserPassword());
            } else {
                $ldapSetting->setBindUserPassword($password);
            }
        } else {
            $ldapSetting->setBindUserDN(null);
            $ldapSetting->setBindUserPassword(null);
        }
    }

    /**
     * @param LDAPSetting $ldapSetting
     * @param array $dataMapping
     */
    private function setDataMappingAttributes(LDAPSetting $ldapSetting, array $dataMapping): void
    {
        $ldapSetting->getDataMapping()->setAttributeNames($dataMapping);
    }

    /**
     * @param LDAPSetting $ldapSetting
     * @param array $userLookupSettings
     */
    private function setUserLookupSettings(LDAPSetting $ldapSetting, array $userLookupSettings): void
    {
        foreach ($userLookupSettings as $userLookupSetting) {
            $ldapSetting->addUserLookupSetting(LDAPUserLookupSetting::createFromArray($userLookupSetting));
        }
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        $paramRules = $this->getParamRuleCollection();
        $paramRules->removeParamValidation(LDAPConfigAPI::PARAMETER_ENABLED);
        $paramRules->removeParamValidation(LDAPConfigAPI::PARAMETER_SYNC_INTERVAL);
        return $paramRules;
    }

    /**
     * @inheritDoc
     */
    public function delete(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }
}
