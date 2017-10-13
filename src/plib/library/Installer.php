<?php
// Copyright 1999-2017. Plesk International GmbH. All rights reserved.
namespace PleskExt\SecurityAdvisor;

class Installer
{
    const API_QUERY_GET_BUTTONS = <<<GET_BUTTONS
<ui>
  <get-custombutton>
    <filter><owner><admin/></owner></filter>
  </get-custombutton>
</ui>
GET_BUTTONS;

    const API_QUERY_CREATE_BUTTON = <<<CREATE_BUTTONS
<ui>
  <create-custombutton>
    <owner><admin/></owner>
    <properties>
      <conhelp/>
      <file/>
      <internal>true</internal>
      <place>admin</place>
      <url/>
      <text/>
    </properties>
  </create-custombutton>
</ui>
CREATE_BUTTONS;

    const API_QUERY_DELETE_BUTTON = <<<DELETE_BUTTONS
<ui>
  <delete-custombutton>
    <filter><custombutton-id/></filter>
  </delete-custombutton>
</ui>
DELETE_BUTTONS;

    public function installHomeAdminCustomButton()
    {
        if (!$this->_hasAnyCustomButton()) {
            $createButtonQuery = new \SimpleXMLElement(static::API_QUERY_CREATE_BUTTON);
            $createButtonQuery->{'create-custombutton'}->properties->conhelp = \pm_Locale::lmsg('custom.button.home.description');
            $createButtonQuery->{'create-custombutton'}->properties->file = \pm_Context::getHtdocsDir() . 'images/home-promo.png';
            $createButtonQuery->{'create-custombutton'}->properties->url = \pm_Context::getBaseUrl();
            $createButtonQuery->{'create-custombutton'}->properties->text = \pm_Locale::lmsg('custom.button.title');
            \pm_ApiRpc::getService()->call($createButtonQuery);
        }
    }

    protected function _hasAnyCustomButton()
    {
        foreach (\pm_ApiRpc::getService()->call(static::API_QUERY_GET_BUTTONS)->ui->{'get-custombutton'}->result as $result) {
            if ($result->properties->url == \pm_Context::getBaseUrl()) {
                return true;
            }
        }

        return false;
    }

    public function removeHomeAdminCustomButtons()
    {
        foreach (\pm_ApiRpc::getService()->call(static::API_QUERY_GET_BUTTONS)->ui->{'get-custombutton'}->result as $result) {
            if ($result->properties->url == \pm_Context::getBaseUrl()) {
                $deleteButtonQuery = new \SimpleXMLElement(static::API_QUERY_DELETE_BUTTON);
                $deleteButtonQuery->{'delete-custombutton'}->filter->{'custombutton-id'} = $result->id;
                \pm_ApiRpc::getService()->call($deleteButtonQuery);
            }
        }
    }
}
