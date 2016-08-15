<?php

// Copyright 1999-2016. Parallels IP Holdings GmbH.
class Modules_SecurityAdvisor_GoogleAuthenticator
{
	const INSTALL_URL = 'https://ext.plesk.com/packages/d6d6e361-b55d-467f-8b97-6426174c77a1-google-authenticator/download';
	const NAME = 'google-authenticator';

	public static function isInstalled()
	{
		return Modules_SecurityAdvisor_Extension::isInstalled(self::NAME);
	}

	public static function isActive()
	{
		return true;
	}

	public static function install()
	{
		return Modules_SecurityAdvisor_Extension::install(self::INSTALL_URL);
	}

}
