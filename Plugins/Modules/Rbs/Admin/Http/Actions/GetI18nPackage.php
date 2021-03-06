<?php
namespace Rbs\Admin\Http\Actions;

use Change\Http\Event;

/**
 * @name \Rbs\Admin\Http\Actions\GetI18nPackage
 */
class GetI18nPackage
{
	/**
	 * Use Required Event Params: resourcePath
	 * @param Event $event
	 */
	public function execute($event)
	{
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$LCID = $event->getParam('LCID', $event->getRequest()->getQuery('LCID'));

		if ($i18nManager->isSupportedLCID($LCID))
		{
			$result = null;
			$packageName = $event->getParam('package');
			$packages = array();
			if ($packageName)
			{
				$keys = $i18nManager->getTranslationsForPackage($packageName, $LCID);
				$package = array();
				if (is_array($keys))
				{
					foreach ($keys as $key => $value)
					{
						$package[$key] = $value;
					}
				}
				$packages[$packageName] = $package;

				$result = new \Change\Http\Rest\Result\ArrayResult();
				$result->setArray($packages);
			}
			else
			{
				$modules = $event->getApplicationServices()->getPluginManager()->getModules();
				foreach ($modules as $module)
				{
					if ($module->isAvailable())
					{
						$packageName = implode('.', ['m', strtolower($module->getVendor()), strtolower($module->getShortName()), 'adminjs']);
						$keys = $i18nManager->getTranslationsForPackage($packageName, $LCID);
						if (is_array($keys))
						{
							$package = array();
							foreach ($keys as $key => $value)
							{
								$package[$key] = $value;
							}
							$packages[$packageName] = $package;
						}
					}
				}

				$result = new \Rbs\Admin\Http\Result\Renderer();
				$result->setHeaderContentType('application/javascript');
				$result->setRenderer(function() use ($packages)
				{
					if (count($packages))
					{
						return '__change.i18n = ' . json_encode($packages) . ';' . PHP_EOL;
					}
					else
					{
						return '__change.i18n = {};' . PHP_EOL;
					}
				});
			}
			$event->setResult($result);
		}
	}
}