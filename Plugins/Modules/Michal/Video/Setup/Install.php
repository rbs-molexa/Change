<?php
namespace Michal\Video\Setup;

/**
 * @name \Michal\Video\Setup\Install
 */
class Install extends \Change\Plugins\InstallBase
{

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Application $application
	 * @param \Change\Configuration\EditableConfiguration $configuration
	 * @throws \RuntimeException
	 */
	public function executeApplication($plugin, $application, $configuration)
	{
		$configuration->addPersistentEntry('Rbs/Admin/Events/Manager/Michal_Video', '\Michal\Video\Events\Admin\Listeners');
		$configuration->addPersistentEntry('Change/Events/BlockManager/Michal_Video', '\Michal\Video\Events\BlockManager\Listeners');

		$video = $configuration->getEntry('Change/Storage/videos', array());
		$video = array_merge( array(
			'class' => '\\Change\\Storage\\Engines\\LocalStorage',
			'basePath' => 'App/Storage/Michal_Video/video',
			'useDBStat' => true,
			'baseURL' => "/index.php"
		), $video);
		$configuration->addPersistentEntry('Change/Storage/Michal_Video', $video);
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}
}
