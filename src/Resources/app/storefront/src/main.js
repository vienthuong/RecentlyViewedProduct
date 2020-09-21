import RecentProductSliderPlugin from './plugin/recent-product-slider.plugin';

const PluginManager = window.PluginManager;

PluginManager.register('RecentProductSlider', RecentProductSliderPlugin, '[data-recent-product-slider]');
