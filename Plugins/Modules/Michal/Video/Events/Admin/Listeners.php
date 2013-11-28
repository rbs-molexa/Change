<?php
namespace Michal\Video\Events\Admin;

use Change\Plugins\Plugin;
use Rbs\Admin\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Michal\Video\Events\Admin\Listeners
 */
class Listeners implements ListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the EventManager
	 * implementation will pass this to the aggregate.
	 * @param EventManagerInterface $events
	 */
	public function attach(EventManagerInterface $events)
	{
		$events->attach(Event::EVENT_RESOURCES, array($this, 'registerResources'));
	}

	/**
	 * @param Event $event
	 */
	public function registerResources(Event $event)
	{
		$manager = $event->getManager();
		$pm = $event->getApplicationServices()->getPluginManager();

		$plugin = $pm->getPlugin(Plugin::TYPE_MODULE, 'Michal', 'Video');
		if ($plugin && $plugin->isAvailable())
		{
			$manager->registerStandardPluginAssets($plugin);

			$i18nManager = $event->getApplicationServices()->getI18nManager();
			$menu = array(
				'entries' => array(
					array('label' => $i18nManager->trans('m.michal.video.admin.module_name', array('ucf')),
						'url' => 'Michal/Video', 'section' => 'cms',
						'keywords' => $i18nManager->trans('m.michal.video.admin.module_keywords'))
				)
			);
			$event->setParam('menu', \Zend\Stdlib\ArrayUtils::merge($event->getParam('menu', array()), $menu));
		}
	}

	/**
	 * Detach all previously attached listeners
	 * @param EventManagerInterface $events
	 */
	public function detach(EventManagerInterface $events)
	{
		// TODO: Implement detach() method.
	}
}
