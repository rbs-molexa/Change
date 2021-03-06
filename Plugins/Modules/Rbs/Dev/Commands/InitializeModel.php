<?php
namespace Rbs\Dev\Commands;

use Change\Commands\Events\Event;

/**
 * @name \Rbs\Dev\Commands\InitializeModel
 */
class InitializeModel
{
	/**
	 * @param Event $event
	 */
	public function execute(Event $event)
	{
		$response = $event->getCommandResponse();

		$applicationServices = $event->getApplicationServices();
		$vendor = $event->getParam('vendor');
		$moduleName = $event->getParam('module');
		$shortName = $event->getParam('name');

		try
		{
			$path = $applicationServices->getModelManager()->initializeModel($vendor, $moduleName, $shortName);
			$response->addInfoMessage('Model definition written at path ' . $path);
			$path = $applicationServices->getModelManager()->initializeFinalDocumentPhpClass($vendor, $moduleName, $shortName);
			$response->addInfoMessage('Final php document class  written at path ' . $path);
		}
		catch (\Exception $e)
		{
			$applicationServices->getLogging()->exception($e);
			$response->addErrorMessage($e->getMessage());
		}
	}
}