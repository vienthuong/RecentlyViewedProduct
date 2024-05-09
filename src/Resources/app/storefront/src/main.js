const PluginManager = window.PluginManager;

PluginManager.register('RecentProductSlider', () => import('./plugin/recent-product-slider.plugin'), '[data-recent-product-slider]');
