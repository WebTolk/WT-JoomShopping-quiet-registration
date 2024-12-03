<?php
/**
 * @package    WT JoomShopping quiet registration
 * @version       1.0.0
 * @Author        Andrey Smirnikov, Sergey Tolkachyov, https://web-tolk.ru
 * @copyright     Copyright (C) 2024 Andrey Smirnikov
 * @license       GNU/GPL http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.0.0
 */

defined('_JEXEC') || die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\Jshopping\Wt_jshopping_quiet_registration\Extension\Wt_jshopping_quiet_registration;

return new class () implements ServiceProviderInterface {

    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            function (Container $container)
            {
                $config  = (array)PluginHelper::getPlugin('jshopping', 'wt_jshopping_quiet_registration');
                $subject = $container->get(DispatcherInterface::class);
                /** @var \Joomla\CMS\Plugin\CMSPlugin $plugin */
                $plugin = new Wt_jshopping_quiet_registration($subject, $config);
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            }
        );
    }
};

