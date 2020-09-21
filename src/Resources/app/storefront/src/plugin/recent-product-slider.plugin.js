import Plugin from 'src/plugin-system/plugin.class';
import PluginManager from 'src/plugin-system/plugin.manager';
import HttpClient from 'src/service/http-client.service';
import ElementLoadingIndicatorUtil from 'src/utility/loading-indicator/element-loading-indicator.util';

export default class RecentProductSliderPlugin extends Plugin {
    init() {
        this._client = new HttpClient();
        this.fetch();
    }

    fetch() {
        if (!this.options.id) {
            return;
        }
        ElementLoadingIndicatorUtil.create(this.el);

        let url = window.router['frontend.recent-product-slider.content'] + '?elementId=' + this.options.id;

        if(this.options.excludeProductId) {
            url += '&excludeProductId=' + this.options.excludeProductId;
        }

        this._client.get(url, (response) => {
            ElementLoadingIndicatorUtil.remove(this.el);

            if (!response || response.trim().length === 0) {
                const hrBar = this.el.closest('.product-detail').querySelector('.recently-viewed-product-bar');

                if (hrBar) {
                    hrBar.remove();
                }

                this.el.remove();
                return;
            }

            this.renderProductSlider(response);
        });
    }

    renderProductSlider(html) {
        this.el.innerHTML = html;
        PluginManager.initializePlugin('ProductSlider', '.product-slider');
    }
}
